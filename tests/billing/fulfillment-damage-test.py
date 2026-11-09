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
 for mutate in cases:
  bad=copy.deepcopy(snapshot);mutate(bad)
  for run in [lambda:qualify(bad,prefix),lambda:compare(bad,bad,prefix)]:
   try:run()
   except (ValueError,KeyError,TypeError):count+=1
   else:raise AssertionError('Damaged fulfillment capture accepted')
 return count
