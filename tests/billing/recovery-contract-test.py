"""Adversarial checks against a real privately captured/restored synthetic billing snapshot."""
import copy,json,pathlib
from recovery_contract import qualify,compare,reconciliation_plan
root=pathlib.Path('/tmp/csf-billing-fixture');e=json.loads((root/'recovery-evidence.json').read_text());capture=pathlib.Path(e['capture_directory'])
assert capture.parent==root and capture.name.startswith('recovery-')
before=json.loads((capture/'before.json').read_text());after=json.loads((capture/'restored.json').read_text());checks=0
assert compare(before,after)['billing_tables']==6;checks+=1
prefix='gift_fixture_csf_billing_'
def reject(change,label):
 global checks
 bad=copy.deepcopy(after);change(bad)
 try:compare(before,bad)
 except (ValueError,KeyError,TypeError):checks+=1;print('PASS',label);return
 raise AssertionError(label)
def mutate(snapshot,table,change):
 values=[json.loads(v) for v in snapshot[prefix+table]['rows']];change(values);snapshot[prefix+table]['rows']=[json.dumps(v,sort_keys=True) for v in values]
for name in ['guard','orders','certificates','commands','audit','throttle']:reject(lambda s,n=name:s.pop(prefix+n),'missing '+name)
reject(lambda s:s.update({prefix+'unknown':copy.deepcopy(s[prefix+'guard'])}),'unknown billing table')
reject(lambda s:s[prefix+'audit'].update(ddl=s[prefix+'audit']['ddl'].replace('InnoDB','MyISAM')),'nontransactional audit')
reject(lambda s:mutate(s,'guard',lambda v:v[0].update(schema_version='1')),'schema marker mismatch')
reject(lambda s:mutate(s,'certificates',lambda v:v[0].update(redeemed=str(int(v[0]['purchased'])+1))),'overdrawn restored balance')
reject(lambda s:mutate(s,'certificates',lambda v:v[0].update(number_sealed='damaged')),'ciphertext bytes must match capture')
reject(lambda s:mutate(s,'certificates',lambda v:v[0].update(number_hash='0'*64)),'certificate number digest mismatch')
reject(lambda s:mutate(s,'certificates',lambda v:v[0].update(order_id='00000000-0000-4000-8000-000000000000')),'orphan restored certificate')
reject(lambda s:mutate(s,'orders',lambda v:v[0].update(status='approved')),'restored financial state cannot silently change')
reject(lambda s:mutate(s,'commands',lambda v:v.clear()),'missing replay and hold evidence')
reject(lambda s:mutate(s,'audit',lambda v:v.clear()),'missing audit evidence')
reject(lambda s:mutate(s,'throttle',lambda v:v.append({'id':'0'*64,'hits':'0','expires':'0'})),'throttle bytes preserved exactly')
reject(lambda s:s[prefix+'throttle'].update(ddl=s[prefix+'throttle']['ddl'].replace('`hits`','`unknown`')),'empty-table physical columns cannot drift')
reject(lambda s:s[prefix+'orders'].update(ddl=s[prefix+'orders']['ddl'].replace('UNIQUE KEY `provider_order`','KEY `provider_order`')),'provider unique index cannot weaken')
plan=qualify(after);assert not any(plan[k] for k in ['provider_calls_permitted','claim_resets_permitted','automatic_unfreeze_permitted']);checks+=1
review=reconciliation_plan(after);assert len(review)==plan['orders'] and all(not r['external_write_allowed'] for r in review);checks+=1
(capture/'reconciliation-plan.json').write_text(json.dumps(review,indent=2));(capture/'reconciliation-plan.json').chmod(0o600)
# The existing qualified contract must continue rejecting these new tables.
source=pathlib.Path('docs/pilot/baseline-select-plan.py.txt').read_bytes();import hashlib
assert hashlib.sha256(source).hexdigest()=='a31e60e2c232d07546412198ef4c19c47ac86a54d0682551c76d4696f514bf17'
ns={'__name__':'unchanged_contract_test'};exec(compile(source,'unchanged_contract','exec'),ns)
old={ns['PREFIX']+v for v in ns['NATIVE']|ns['SCHEDULING']};assert len(ns['table_set'](old))==27;checks+=1
try:ns['table_set'](old|{ns['PREFIX']+'csf_billing_'+v for v in ['guard','orders','certificates','commands','audit','throttle']})
except ValueError:checks+=1
else:raise AssertionError('Qualified contract unexpectedly accepts billing')
print('BILLING_RECOVERY_CONTRACT_ASSERTIONS='+str(checks));(capture/'contract-checks.json').write_text(json.dumps({'checks':checks,'plan':plan},indent=2))
