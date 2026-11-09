"""Monetary corruption must fail even when capture and restore are identically corrupted."""
import copy,json
from recovery_contract import qualify,compare,digest

def monetary_damage_checks(snapshot,prefix):
 count=0;table=prefix+'csf_billing_certificates'
 def value_change(s,change):
  rows=[json.loads(v) for v in s[table]['rows']];row=next(r for r in rows if json.loads(r['value_json'])['pending'] is not None);v=json.loads(row['value_json']);change(v);row['value_json']=json.dumps(v);s[table]['rows']=[json.dumps(r) for r in rows]
 cases=[lambda s:value_change(s,lambda v:v.update(paid_minor=v['paid_minor']+1)),lambda s:value_change(s,lambda v:v.update(allocation=[1]*len(v['allocation']))),lambda s:value_change(s,lambda v:v.update(cash_paid_minor=1)),lambda s:value_change(s,lambda v:v.update(used_slots=[])),lambda s:value_change(s,lambda v:v.update(pending=None)),lambda s:value_change(s,lambda v:v['pending'].update(amount_minor=1))]
 def lose_receipt(s,kind):
  audit=[json.loads(v) for v in s[prefix+'csf_billing_audit']['rows']];event=next(a for a in audit if a['kind']==kind);d=json.loads(event['details']);key=digest('cash:'+d['command']);s[prefix+'csf_billing_commands']['rows']=[v for v in s[prefix+'csf_billing_commands']['rows'] if json.loads(v)['id']!=key]
 cases.extend([lambda s:lose_receipt(s,'cash_record_paid'),lambda s:lose_receipt(s,'cash_uncertain')])
 def duplicate_audit(s):
  key=prefix+'csf_billing_audit';rows=[json.loads(v) for v in s[key]['rows']];row=copy.deepcopy(next(r for r in rows if r['kind']=='cash_uncertain'));row['id']=str(max(int(r['id']) for r in rows)+1);rows.append(row);s[key]['rows']=[json.dumps(r) for r in rows]
 cases.extend([duplicate_audit,lambda s:value_change(s,lambda v:v.update(version=True))])
 for change in cases:
  bad=copy.deepcopy(snapshot);change(bad)
  for validate in [lambda:qualify(bad,prefix),lambda:compare(bad,bad,prefix)]:
   try:validate()
   except ValueError:count+=1
   else:raise AssertionError('Monetary damage accepted')
 return count
