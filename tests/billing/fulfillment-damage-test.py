"""Reject independently damaged captures, including identically damaged restore pairs."""
import copy,json
from recovery_contract import qualify,compare,digest

def fulfillment_damage_checks(snapshot,prefix):
 count=0;cmd=prefix+'csf_billing_commands';orders=prefix+'csf_billing_orders'
 def change(s,table,predicate,mutator):
  rows=[json.loads(x) for x in s[table]['rows']];row=next(x for x in rows if predicate(x));mutator(row);s[table]['rows']=[json.dumps(x) for x in rows]
 def mutate_result(s,predicate,mutator):
  def apply(row):
   r=json.loads(row['result']);mutator(r);row['result']=json.dumps(r)
  change(s,cmd,lambda row:predicate(json.loads(row['result'])),apply)
 def delete(s,predicate):s[cmd]['rows']=[x for x in s[cmd]['rows'] if not predicate(json.loads(json.loads(x)['result']))]
 def bad_contact(s):
  def apply(r):d=json.loads(r['document']);d['fulfillment']['contact_sealed']='plaintext@example.invalid';r['document']=json.dumps(d)
  change(s,orders,lambda r:True,apply)
 cases=[bad_contact,lambda s:delete(s,lambda r:r.get('reason')=='delivery_not_qualified'),lambda s:mutate_result(s,lambda r:r.get('reason')=='delivery_not_qualified',lambda r:r.update(audience='unknown')),lambda s:delete(s,lambda r:'sealed' in r and 'number_hash' in r),lambda s:mutate_result(s,lambda r:'sealed' in r and 'number_hash' in r,lambda r:r.update(sha256='bad')),lambda s:mutate_result(s,lambda r:'message_sealed' in r,lambda r:r.update(delivered=True)),lambda s:mutate_result(s,lambda r:'message_sealed' in r,lambda r:r.update(attempt='bad')),lambda s:mutate_result(s,lambda r:'message_sealed' in r,lambda r:r.update(number_hash='0'*64)),lambda s:delete(s,lambda r:r.get('sink_accepted') is True),lambda s:mutate_result(s,lambda r:r.get('sink_accepted') is True,lambda r:r.update(delivered=True))]
 cases += [lambda s:delete(s,lambda r:'replacement_hash' in r),lambda s:mutate_result(s,lambda r:'replacement_hash' in r,lambda r:r.update(replacement_hash='0'*64)),lambda s:delete(s,lambda r:any(isinstance(v,dict) and 'completion_command' in v for v in r.values())),lambda s:mutate_result(s,lambda r:any(isinstance(v,dict) and 'completion_command' in v for v in r.values()),lambda r:next(iter(r.values())).update(value_minor=1))]
 cases += [lambda s:delete(s,lambda r:set(r)=={'version','dispatch_id','sha256','sealed'}),lambda s:mutate_result(s,lambda r:set(r)=={'version','dispatch_id','sha256','sealed'},lambda r:r.update(sha256='bad')),lambda s:mutate_result(s,lambda r:set(r)=={'version','dispatch_id','sha256','sealed'},lambda r:r.update(dispatch_id='0'*64))]
 def wrong_cancel(s):
  def apply(r):d=json.loads(r['details']);d['initiator']='client';r['details']=json.dumps(d)
  change(s,prefix+'csf_billing_audit',lambda r:r['kind']=='booking_credit_cancel',apply)
 def missing_correction(s):
  key=prefix+'csf_billing_audit';s[key]['rows']=[r for r in s[key]['rows'] if json.loads(r)['kind']!='booking_credit_correct']
 def missing_scheduler_marker(s):
  def apply(r):d=json.loads(r['body_json']);d.pop('billing_certificate_id');r['body_json']=json.dumps(d)
  change(s,prefix+'csf_sched_details',lambda r:'billing_certificate_id' in json.loads(r['body_json']),apply)
 cases += [wrong_cancel,missing_correction,missing_scheduler_marker,lambda s:mutate_result(s,lambda r:'booking_uuid' in r and 'rides' in r,lambda r:r.update(booking_uuid='00000000-0000-4000-8000-000000000000'))]
 cases += [lambda s:delete(s,lambda r:'body_sealed' in r),lambda s:mutate_result(s,lambda r:'body_sealed' in r,lambda r:r.update(body_sha256='0'*64)),lambda s:delete(s,lambda r:'offer_id' in r and 'accepted_at' in r),lambda s:mutate_result(s,lambda r:'offer_id' in r and 'accepted_at' in r,lambda r:r.update(terms_sha256='0'*64)),lambda s:delete(s,lambda r:set(r)=={'offer_id','certificate_id','booking_uuid','command'}),lambda s:mutate_result(s,lambda r:set(r)=={'offer_id','certificate_id','booking_uuid','command'},lambda r:r.update(booking_uuid='00000000-0000-4000-8000-000000000000'))]
 for mutate in cases:
  bad=copy.deepcopy(snapshot);mutate(bad)
  for run in [lambda:qualify(bad,prefix),lambda:compare(bad,bad,prefix)]:
   try:run()
   except (ValueError,KeyError,TypeError):count+=1
   else:raise AssertionError('Damaged fulfillment capture accepted')
 return count
