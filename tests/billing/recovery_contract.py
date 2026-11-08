"""Proposed BILLING-RECOVERY-1 checks; synthetic/offline only, never a deployment gate.

No import of or changes to the qualified farm recovery contract. Inputs are private exact
snapshots, not trusted just because they parse. Custody/target/pins are separate prerequisites.
"""
import hashlib,json,re
TABLES=('guard','orders','certificates','commands','audit','throttle')
COLUMNS={
 'guard':'id schema_version',
 'orders':'id session_hash provider_order_id capture_id status document revision created_at',
 'certificates':'id order_id number_hash number_sealed value_json purchased redeemed status revision',
 'commands':'id payload_hash result created_at',
 'audit':'id kind actor object_id details created_at',
 'throttle':'id hits expires'}
def need(ok,code):
 if not ok:raise ValueError(code)
def digest(value):return hashlib.sha256(value.encode()).hexdigest()
# Reviewed schema-2 physical definitions, independent of the captured database.
# Compare complete column declarations, ordered indexes and table options. Only
# known integer display widths and the audit sequence counter are nonsemantic.
ASCII=' CHARACTER SET ascii COLLATE ascii_bin'
def ascii_column(kind,nullable=False):return kind+ASCII+(' DEFAULT NULL' if nullable else ' NOT NULL')
PHYSICAL={
 'guard':[('id','tinyint unsigned NOT NULL'),('schema_version','int unsigned NOT NULL')],
 'orders':[('id',ascii_column('char(36)')),('session_hash',ascii_column('char(64)')),('provider_order_id',ascii_column('varchar(32)',True)),('capture_id',ascii_column('varchar(32)',True)),('status','varchar(32) NOT NULL'),('document','longtext NOT NULL'),('revision','bigint unsigned NOT NULL'),('created_at','datetime NOT NULL')],
 'certificates':[('id',ascii_column('char(36)')),('order_id',ascii_column('char(36)')),('number_hash',ascii_column('char(64)')),('number_sealed','text NOT NULL'),('value_json','longtext NOT NULL'),('purchased','int unsigned NOT NULL'),('redeemed','int unsigned NOT NULL'),('status','varchar(32) NOT NULL'),('revision','bigint unsigned NOT NULL')],
 'commands':[('id',ascii_column('char(64)')),('payload_hash',ascii_column('char(64)')),('result','longtext NOT NULL'),('created_at','datetime NOT NULL')],
 'audit':[('id','bigint unsigned NOT NULL AUTO_INCREMENT'),('kind','varchar(64) NOT NULL'),('actor','bigint unsigned NOT NULL'),('object_id',ascii_column('char(36)')),('details','longtext NOT NULL'),('created_at','datetime NOT NULL')],
 'throttle':[('id',ascii_column('char(64)')),('hits','int unsigned NOT NULL'),('expires','bigint unsigned NOT NULL')]}
INDEXES={
 'orders':['UNIQUE KEY `provider_order` (`provider_order_id`)','UNIQUE KEY `capture` (`capture_id`)'],
 'certificates':['UNIQUE KEY `one_per_order` (`order_id`)','UNIQUE KEY `certificate_number` (`number_hash`)'],
 'throttle':['KEY `expires` (`expires`)']}
def physical_schema(ddl,prefix,name):
 need(re.fullmatch(r'[A-Za-z0-9_]+',prefix) is not None,'unsafe_prefix')
 match=re.fullmatch(r'CREATE TABLE `'+re.escape(prefix+'csf_billing_'+name)+r'` \(\n(.*)\n\) (.*)',ddl,re.S)
 need(match is not None,'physical_ddl_shape')
 columns=[]
 for line in match[1].splitlines():
  line=line.strip().removesuffix(',')
  for kind,width in [('tinyint','3'),('int','10'),('bigint','20')]:
   line=re.sub(r'(?<![a-z])'+kind+r'\('+width+r'\)',kind,line)
  columns.append(line)
 expected=['`'+col+'` '+definition for col,definition in PHYSICAL[name]]+['PRIMARY KEY (`id`)']+INDEXES.get(name,[])
 need(columns==expected,'physical_column_or_index_contract')
 options=match[2]
 if name=='audit':options=re.sub(r' AUTO_INCREMENT=[1-9][0-9]*','',options)
 need(options=='ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci','physical_table_options')
def rows(snapshot,prefix,name):
 item=snapshot[prefix+'csf_billing_'+name]
 physical_schema(item['ddl'],prefix,name)
 data=[json.loads(v) for v in item['rows']]
 for row in data:need(set(row)==set(COLUMNS[name].split()),'unknown_or_missing_columns')
 need(len({r['id'] for r in data})==len(data),'duplicate_primary_key')
 return data
def qualify(snapshot,prefix='gift_fixture_'):
 need(set(k for k in snapshot if k.startswith(prefix+'csf_billing_'))=={prefix+'csf_billing_'+v for v in TABLES},'billing_table_set')
 data={name:rows(snapshot,prefix,name) for name in TABLES}
 need(len(data['guard'])==1 and str(data['guard'][0]['id'])=='1' and str(data['guard'][0]['schema_version'])=='3','schema_marker')
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
 debits={};corrected=set();net={cid:0 for cid in certs};issued=set();slots={cid:set() for cid in certs};cash={cid:0 for cid in certs};pending={cid:None for cid in certs};values={};cash_commands=set();cash_requests=set();payout_evidence=set()
 for cid,c in certs.items():
  v=json.loads(c['value_json']);values[cid]=v;d=docs[c['order_id']];policy=v['policy'];n=int(c['purchased']);paid=v['paid_minor']
  need(set(v)=={'version','currency','paid_minor','allocation','policy','used_slots','cash_paid_minor','pending'} and type(v['version']) is int and v['version']==1 and v['currency']=='USD' and type(paid) is int and paid==d['amount_minor'] and 0<n<=100 and n<=paid<=100000000,'value_document')
  need(set(policy)=={'method','version','approved'} and isinstance(policy['version'],str) and re.fullmatch(r'[A-Za-z0-9._-]{1,64}',policy['version']) and policy==d['valuation'] and type(policy['approved']) is bool and policy['method'] in ['proportional_floor_v1','front_remainder_v1'],'value_policy')
  allocation=[paid*(i+1)//n-paid*i//n if policy['method']=='proportional_floor_v1' else paid//n+(1 if i<paid%n else 0) for i in range(n)]
  need(isinstance(v['allocation'],list) and all(type(i) is int for i in v['allocation']) and isinstance(v['used_slots'],list) and all(type(i) is int for i in v['used_slots']) and (not v['used_slots'] or policy['approved']) and v['allocation']==allocation and type(v['cash_paid_minor']) is int and v['cash_paid_minor']>=0,'value_allocation')
 for a in sorted(data['audit'],key=lambda r:int(r['id'])):
  d=json.loads(a['details']);cid=a['object_id']
  if a['kind']=='certificate_issued':
   need(cid in certs and cid not in issued and d['order_id']==certs[cid]['order_id'],'issuance_audit');issued.add(cid)
  if a['kind']=='rides_redeemed':
   need(cid in certs and d['command'] not in debits and isinstance(d['rides'],int) and d['rides']>0,'debit_audit')
   receipt=commands.get(digest('redeem:'+d['command']));need(receipt is not None,'missing_debit_receipt')
   r=json.loads(receipt['result']);need(r['certificate_id']==cid and r['rides']==d['rides'] and r['command']==d['command'],'debit_receipt_binding')
   need(digest('ride:'+cid+':'+d['ride_reference']) in commands,'missing_ride_tombstone');debits[d['command']]=(cid,d['rides'],d['slots'],d['value_minor']);net[cid]+=d['rides']
   used=d['slots'];need(isinstance(used,list) and len(used)==d['rides'] and len(set(used))==len(used) and all(type(i) is int and 0<=i<len(values[cid]['allocation']) and i not in slots[cid] for i in used),'debit_value_slots')
   need(d['value_minor']==sum(values[cid]['allocation'][i] for i in used) and r['slots']==used and r['value_minor']==d['value_minor'] and pending[cid] is None,'debit_value_receipt');slots[cid].update(used)
  if a['kind']=='ride_debit_corrected':
   original=d['original_command'];need(original in debits and original not in corrected and debits[original]==(cid,d['rides'],d['slots'],d['value_minor']),'correction_audit')
   need(digest('reversed:'+original) in commands,'missing_correction_tombstone');corrected.add(original);net[cid]-=d['rides'];need(set(d['slots'])<=slots[cid] and pending[cid] is None,'correction_value');slots[cid].difference_update(d['slots'])
  if a['kind'].startswith('cash_'):
   need(d['command'] not in cash_commands,'duplicate_cash_command_audit');cash_commands.add(d['command'])
   need(cid in certs and type(d['amount_minor']) is int and d['amount_minor']>0 and d['currency']=='USD','cash_audit')
   receipt=commands.get(digest('cash:'+d['command']));need(receipt is not None,'cash_receipt');r=json.loads(receipt['result']);need(r['request']==d['request'] and r['certificate_id']==cid and r['action']==a['kind'].removeprefix('cash_') and r['payment_executed'] is False,'cash_receipt_binding')
   if a['kind']=='cash_reserve':
    need(d['request'] not in cash_requests,'cash_request_reused');cash_requests.add(d['request'])
    need(pending[cid] is None and d['amount_minor']==values[cid]['paid_minor']-sum(values[cid]['allocation'][i] for i in slots[cid])-cash[cid] and digest('cash_request:'+d['request']) in commands,'cash_reservation')
    pending[cid]={'id':d['request'],'amount_minor':d['amount_minor'],'state':'reserved'}
   else:
    need(pending[cid] is not None and pending[cid]['id']==d['request'] and pending[cid]['amount_minor']==d['amount_minor'],'cash_transition')
    if a['kind']=='cash_uncertain':pending[cid]['state']='uncertain'
    elif a['kind']=='cash_record_paid':
     need(d['evidence'] not in payout_evidence,'payout_evidence_reused');payout_evidence.add(d['evidence'])
     need(digest('cash_evidence:'+d['evidence']) in commands,'cash_payout_evidence');cash[cid]+=d['amount_minor'];pending[cid]=None
    elif a['kind']=='cash_cancel_unpaid':pending[cid]=None
    else:raise ValueError('unknown_cash_transition')
 for cid,c in certs.items():
  v=values[cid];need(v['used_slots']==sorted(slots[cid]) and v['cash_paid_minor']==cash[cid] and v['pending']==pending[cid] and v['paid_minor']-sum(v['allocation'][i] for i in slots[cid])-cash[cid]>=0,'value_balance')
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
