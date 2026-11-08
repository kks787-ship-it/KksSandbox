"""Proposed BILLING-RECOVERY-1 checks; synthetic/offline only, never a deployment gate.

No import of or changes to the qualified farm recovery contract. Inputs are private exact
snapshots, not trusted just because they parse. Custody/target/pins are separate prerequisites.
"""
import hashlib,json,re
TABLES=('guard','orders','certificates','commands','audit','throttle')
COLUMNS={
 'guard':'id schema_version',
 'orders':'id session_hash provider_order_id capture_id status document revision created_at',
 'certificates':'id order_id number_hash number_sealed purchased redeemed status revision',
 'commands':'id payload_hash result created_at',
 'audit':'id kind actor object_id details created_at',
 'throttle':'id hits expires'}
def need(ok,code):
 if not ok:raise ValueError(code)
def digest(value):return hashlib.sha256(value.encode()).hexdigest()
def rows(snapshot,prefix,name):
 item=snapshot[prefix+'csf_billing_'+name]
 need('ENGINE=InnoDB' in item['ddl'],'nontransactional_table')
 need(set(re.findall(r'^\s*`([^`]+)`\s',item['ddl'],re.M))==set(COLUMNS[name].split()),'physical_column_set')
 need('PRIMARY KEY (`id`)' in item['ddl'],'primary_key')
 for key,column in {'orders':{'provider_order':'provider_order_id','capture':'capture_id'},'certificates':{'one_per_order':'order_id','certificate_number':'number_hash'}}.get(name,{}).items():
  need(f'UNIQUE KEY `{key}` (`{column}`)' in item['ddl'],'unique_binding_index')
 data=[json.loads(v) for v in item['rows']]
 for row in data:need(set(row)==set(COLUMNS[name].split()),'unknown_or_missing_columns')
 need(len({r['id'] for r in data})==len(data),'duplicate_primary_key')
 return data
def qualify(snapshot,prefix='gift_fixture_'):
 need(set(k for k in snapshot if k.startswith(prefix+'csf_billing_'))=={prefix+'csf_billing_'+v for v in TABLES},'billing_table_set')
 data={name:rows(snapshot,prefix,name) for name in TABLES}
 need(len(data['guard'])==1 and str(data['guard'][0]['id'])=='1' and str(data['guard'][0]['schema_version'])=='2','schema_marker')
 orders={r['id']:r for r in data['orders']};certs={r['id']:r for r in data['certificates']};commands={r['id']:r for r in data['commands']}
 for field in ['provider_order_id','capture_id']:
  values=[r[field] for r in orders.values() if r[field] is not None];need(len(values)==len(set(values)),'duplicate_provider_binding')
 for field in ['order_id','number_hash']:
  values=[r[field] for r in certs.values()];need(len(values)==len(set(values)),'duplicate_certificate_binding')
 for c in commands.values():
  need(re.fullmatch('[a-f0-9]{64}',c['id']) and re.fullmatch('[a-f0-9]{64}',c['payload_hash']),'command_shape');json.loads(c['result'])
 docs={}
 for oid,o in orders.items():
  need(o['status'] in ['prepared','create_uncertain','awaiting_approval','approved','capture_uncertain','issued','frozen'],'unknown_order_state')
  d=json.loads(o['document']);docs[oid]=d
  need(d['id']==oid and d['terms']['sha256']==digest(d['terms']['text']),'immutable_terms_binding')
  need(d['amount_minor']==d['product']['amount_minor'] and d['currency']==d['product']['currency'],'price_binding')
  need(d['product']['payment_mode']=='one_time','unsupported_payment_mode')
  if o['status'] in ['issued','capture_uncertain']:need(bool(o['provider_order_id']),'missing_provider_binding')
  if o['status']=='issued':need(sum(c['order_id']==oid for c in certs.values())==1,'missing_issued_certificate')
 debits={};corrected=set();net={cid:0 for cid in certs};issued=set()
 for a in sorted(data['audit'],key=lambda r:int(r['id'])):
  d=json.loads(a['details']);cid=a['object_id']
  if a['kind']=='certificate_issued':
   need(cid in certs and cid not in issued and d['order_id']==certs[cid]['order_id'],'issuance_audit');issued.add(cid)
  if a['kind']=='rides_redeemed':
   need(cid in certs and d['command'] not in debits and isinstance(d['rides'],int) and d['rides']>0,'debit_audit')
   receipt=commands.get(digest('redeem:'+d['command']));need(receipt is not None,'missing_debit_receipt')
   r=json.loads(receipt['result']);need(r['certificate_id']==cid and r['rides']==d['rides'] and r['command']==d['command'],'debit_receipt_binding')
   need(digest('ride:'+cid+':'+d['ride_reference']) in commands,'missing_ride_tombstone');debits[d['command']]=(cid,d['rides']);net[cid]+=d['rides']
  if a['kind']=='ride_debit_corrected':
   original=d['original_command'];need(original in debits and original not in corrected and debits[original]==(cid,d['rides']),'correction_audit')
   need(digest('reversed:'+original) in commands,'missing_correction_tombstone');corrected.add(original);net[cid]-=d['rides']
 for cid,c in certs.items():
  need(c['order_id'] in orders,'orphan_certificate');o=orders[c['order_id']];d=docs[c['order_id']]
  need(cid in issued and int(c['purchased'])==d['product']['rides'],'purchased_rides_binding')
  need(0<=int(c['redeemed'])<=int(c['purchased']) and net[cid]==int(c['redeemed']),'redemption_balance')
  need(o['status'] in ['issued','frozen'] and c['status']==('frozen' if o['status']=='frozen' else 'active'),'freeze_state_binding')
  need(o['capture_id'] and digest('issue:'+o['capture_id']) in commands,'missing_issuance_receipt')
  need(json.loads(commands[digest('issue:'+o['capture_id'])]['result'])['certificate_id']==cid,'issuance_receipt_binding')
  if c['status']=='active':need(digest('capture_hold:'+o['capture_id']) not in commands,'active_negative_payment_hold')
 # Restore alone cannot justify an external write. No age-based claim reset is allowed.
 plan={s:sum(o['status']==s for o in orders.values()) for s in ['prepared','create_uncertain','awaiting_approval','approved','capture_uncertain','issued','frozen']}
 return {'billing_tables':6,'orders':len(orders),'certificates':len(certs),'debits':len(debits),'corrections':len(corrected),'reconciliation_counts':plan,'provider_calls_permitted':False,'claim_resets_permitted':False,'automatic_unfreeze_permitted':False}
def compare(before,after,prefix='gift_fixture_'):
 qualify(before,prefix);qualify(after,prefix)
 for name in TABLES:need(before[prefix+'csf_billing_'+name]==after[prefix+'csf_billing_'+name],'exact_billing_restore_mismatch')
 return qualify(after,prefix)

def reconciliation_plan(snapshot,prefix='gift_fixture_'):
 qualify(snapshot,prefix)
 actions={'prepared':'retain_unpaid_no_provider_write','create_uncertain':'merchant_lookup_required_no_recreate','awaiting_approval':'verify_current_provider_state_no_capture','approved':'verify_current_provider_state_no_capture','capture_uncertain':'verify_order_and_capture_no_recapture','issued':'verify_existing_binding_no_reissue','frozen':'preserve_hold_manual_review_no_release'}
 return [{'order_id':row['id'],'status':row['status'],'next_review':actions[row['status']],'external_write_allowed':False} for row in rows(snapshot,prefix,'orders')]
