"""Candidate fulfillment/rotation/reservation invariants; no qualified-contract changes."""
import base64,hashlib,json,re

def check_fulfillment(data,docs,certs,commands,need,digest):
 def envelope(value):
  need(isinstance(value,str) and 40<=len(value)<=3000000,'private_envelope')
  try:raw=base64.b64decode(value,validate=True)
  except Exception:raise ValueError('private_envelope')
  need(len(raw)>28,'private_envelope')
 def result(key):
  need(key in commands,'missing_fulfillment_record');return json.loads(commands[key]['result'])
 artifacts={};states={};rotation={};booking={};retired=set();audited_retired=set();origins={}
 for oid,d in docs.items():
  if 'fulfillment' not in d:continue
  f=d['fulfillment'];need(set(f)=={'version','design','contact_sealed','signature_sealed','instructions'} and type(f['version']) is int and f['version']==1 and f['design'] in ['pasture-v1','sunset-v1','classic-v1'] and isinstance(f['instructions'],str) and len(f['instructions'].encode())<=4000,'presentation_contract')
  for field in ['contact_sealed','signature_sealed']:
   if f[field] is not None:envelope(f[field])
 for cid,c in certs.items():
  if 'fulfillment' in docs[c['order_id']]:
   for audience in ['buyer','administration']:
    r=result(digest('fulfillment:'+cid+':'+audience));need(r=={'version':1,'certificate_id':cid,'order_id':c['order_id'],'audience':audience,'state':'held','reason':'delivery_not_qualified'},'held_intent_binding')
 for key,row in commands.items():
  r=json.loads(row['result'])
  if not isinstance(r,dict):continue
  if set(r)=={'version','certificate_id','number_hash','sha256','sealed'}:
   cid=r['certificate_id'];need(cid in certs and type(r['version']) is int and r['version']==1 and re.fullmatch('[a-f0-9]{64}',r['number_hash']) and re.fullmatch('[a-f0-9]{64}',r['sha256']) and key==digest('pdf:'+cid+':'+r['number_hash']),'pdf_binding');envelope(r['sealed']);artifacts[key]=r
  if 'message_sealed' in r:
   cid=r.get('certificate_id');need(set(r)=={'version','dispatch_id','certificate_id','number_hash','order_id','audience','state','attempt','message_sealed','delivered'} and cid in certs and r['order_id']==certs[cid]['order_id'] and r['version']==1 and r['delivered'] is False and r['audience'] in ['buyer','administration'] and r['state'] in ['claimed','uncertain','sink_recorded','superseded'],'dispatch_contract')
   need(re.fullmatch('[a-f0-9-]{36}',r['attempt']) and key==digest('dispatch_state:'+r['dispatch_id']),'dispatch_identity');envelope(r['message_sealed']);states[r['dispatch_id']]=r
  if 'replacement_hash' in r:
   need(set(r)=={'certificate_id','number_hash','replacement_hash','command'} and r['certificate_id'] in certs and re.fullmatch('[a-f0-9]{64}',r['number_hash']) and re.fullmatch('[a-f0-9]{64}',r['replacement_hash']) and r['number_hash']!=r['replacement_hash'] and key==digest('retired_number:'+r['number_hash']),'rotation_tombstone')
   retired.add(r['number_hash'])
 for did,r in states.items():
  aid=digest('pdf:'+r['certificate_id']+':'+r['number_hash']);need(aid in artifacts and did==digest('dispatch:'+aid+':'+r['audience']),'dispatch_artifact_binding')
  receipt=commands.get(digest('dispatch_sink_receipt:'+did))
  if receipt:
   need(json.loads(receipt['result'])=={'dispatch_id':did,'attempt':r['attempt'],'sink_accepted':True,'delivered':False},'sink_receipt_binding')
  if r['state']=='sink_recorded':need(receipt is not None,'missing_sink_receipt')
 for event in sorted(data['audit'],key=lambda r:int(r['id'])):
  d=json.loads(event['details']);cid=event['object_id']
  if event['kind']=='certificate_issued' and 'number_hash' in d:origins[cid]=d['number_hash']
  if event['kind']=='certificate_pdf_persisted':
   need(d['artifact_id'] in artifacts and artifacts[d['artifact_id']]['certificate_id']==cid and artifacts[d['artifact_id']]['sha256']==d['sha256'] and artifacts[d['artifact_id']]['number_hash']==d['number_hash'],'pdf_audit_binding')
  if event['kind']=='certificate_number_rotated':
   need(cid in certs and rotation.get(cid,origins.get(cid))==d['previous_hash'],'rotation_chain')
   r=result(digest('retired_number:'+d['previous_hash']));need(r=={'certificate_id':cid,'number_hash':d['previous_hash'],'replacement_hash':d['number_hash'],'command':d['command']},'rotation_audit_binding');rotation[cid]=d['number_hash'];audited_retired.add(d['previous_hash'])
  if event['kind'].startswith('booking_credit_'):
   need(cid in certs and event['kind'] in ['booking_credit_reserve','booking_credit_cancel','booking_credit_complete','booking_credit_correct'],'booking_audit_kind')
   prior=booking.setdefault(cid,{}).get(d['booking_uuid']);r=d['reservation'];kind=event['kind'].removeprefix('booking_credit_')
   if kind=='reserve':need(prior is None and r['state']=='reserved' and r['revision']==1,'reservation_initial_state')
   else:
    need(prior is not None and r['revision']==prior['revision']+1 and all(r[k]==prior[k] for k in ['slots','value_minor','policy']),'reservation_history')
    expected={'cancel':('reserved','released'),'complete':('reserved','used'),'correct':('used','reserved')}[kind];need((prior['state'],r['state'])==expected,'reservation_transition')
    if kind=='cancel':need(d.get('initiator') in prior['policy']['release_initiators'],'reservation_release_policy')
    if kind=='complete':need(result(digest('redeem:'+r['completion_command'])).get('booking_uuid')==d['booking_uuid'],'booking_completion_receipt')
    if kind=='correct':need(digest('reversed:'+prior['completion_command']) in commands,'booking_correction_receipt')
   booking[cid][d['booking_uuid']]=r
 need(retired==audited_retired,'rotation_audit_set')
 for cid,last in rotation.items():need(certs[cid]['number_hash']==last,'rotated_current_number')
 for cid,c in certs.items():
  if cid in origins:need(c['number_hash']==rotation.get(cid,origins[cid]),'current_bearer_history')
  row=commands.get(digest('booking_reservations:'+cid));actual=json.loads(row['result']) if row else {}
  need(actual==booking.get(cid,{}),'booking_projection_audit')
  used=json.loads(c['value_json']);reserved=[]
  for bid,r in actual.items():
   need(re.fullmatch('[a-f0-9-]{36}',bid) and set(r)=={'state','slots','value_minor','policy','revision','completion_command'} and r['state'] in ['reserved','used','released'] and type(r['revision']) is int and r['revision']>0 and r['policy'].get('approved') is True,'reservation_contract')
   need(isinstance(r['slots'],list) and len(r['slots'])==1 and type(r['slots'][0]) is int and 0<=r['slots'][0]<len(used['allocation']) and r['value_minor']==sum(used['allocation'][i] for i in r['slots']),'reservation_value')
   if r['state']=='reserved':reserved+=r['slots']
   if r['state']=='used':need(set(r['slots'])<=set(used['used_slots']),'completed_booking_value')
  need(len(set(reserved))==len(reserved) and not set(reserved)&set(used['used_slots']) and (not reserved or used['pending'] is None),'reserved_double_spend')
  need(sum(used['allocation'][i] for i in reserved)<=used['paid_minor']-sum(used['allocation'][i] for i in used['used_slots'])-used['cash_paid_minor'],'reserved_cash_overspend')
 return {'artifacts':len(artifacts),'dispatches':len(states),'rotations':len(rotation),'booking_certificates':len(booking)}
