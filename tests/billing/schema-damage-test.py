"""Reject damaged capture itself, including identical damaged capture/restore pairs."""
import copy,re
from recovery_contract import PHYSICAL,qualify,compare

def schema_damage_checks(snapshot,prefix):
 checks=0
 def reject(table,old,new,label):
  nonlocal checks
  bad=copy.deepcopy(snapshot);entry=bad[prefix+'csf_billing_'+table]
  assert old in entry['ddl'],label+' mutation target missing'
  entry['ddl']=entry['ddl'].replace(old,new,1)
  # Both checks are necessary: equality must never qualify already-corrupt capture.
  for validate in [lambda:qualify(bad,prefix),lambda:compare(bad,bad,prefix)]:
   try:validate()
   except ValueError:checks+=1
   else:raise AssertionError('Damaged schema accepted: '+label)
 for table,columns in PHYSICAL.items():
  ddl=snapshot[prefix+'csf_billing_'+table]['ddl']
  for col,definition in columns:
   line=next(v.strip().removesuffix(',') for v in ddl.splitlines() if v.strip().startswith('`'+col+'` '))
   reject(table,line,'`'+col+'` varchar(1) NOT NULL','type '+table+'.'+col)
   if 'NOT NULL' in line:reject(table,line,line.replace('NOT NULL','DEFAULT NULL'),'nullable '+table+'.'+col)
   else:reject(table,line,line.replace('DEFAULT NULL','NOT NULL'),'required '+table+'.'+col)
   if 'DEFAULT NULL' in line:changed=line.replace('DEFAULT NULL',"DEFAULT 'unexpected'")
   else:changed=line.replace(' AUTO_INCREMENT','')+" DEFAULT '0'"
   reject(table,line,changed,'default '+table+'.'+col)
   if ' unsigned' in line:reject(table,line,line.replace(' unsigned',''),'signed '+table+'.'+col)
   if 'ascii_bin' in line:reject(table,line,line.replace('ascii_bin','ascii_general_ci'),'collation '+table+'.'+col)
  reject(table,'ENGINE=InnoDB','ENGINE=MyISAM','engine '+table)
  reject(table,'COLLATE=utf8mb4_unicode_ci','COLLATE=utf8mb4_general_ci','table collation '+table)
  reject(table,'PRIMARY KEY (`id`)','KEY `id` (`id`)','primary '+table)
 reject('audit',' AUTO_INCREMENT,',',','missing auto increment')
 reject('throttle','KEY `expires` (`expires`)','KEY `expires` (`hits`)','wrong secondary index')
 reject('orders','UNIQUE KEY `capture` (`capture_id`)','KEY `capture` (`capture_id`)','weakened unique index')
 reject('certificates','(`number_hash`)','(`number_hash`(10))','prefix unique index')
 # Cosmetic integer display widths alone differ between MySQL and MariaDB.
 normalized=copy.deepcopy(snapshot)
 for entry in normalized.values():entry['ddl']=re.sub(r'\b(tinyint|int|bigint)\((3|10|20)\)',r'\1',entry['ddl'])
 qualify(normalized,prefix);checks+=1
 return checks

if __name__=='__main__':
 import json,pathlib
 root=pathlib.Path('/tmp/csf-billing-fixture');e=json.loads((root/'recovery-evidence.json').read_text());capture=pathlib.Path(e['capture_directory'])
 assert capture.parent==root and capture.name.startswith('recovery-')
 snapshot=json.loads((capture/'before.json').read_text())
 print('PHYSICAL_SCHEMA_DAMAGE_ASSERTIONS='+str(schema_damage_checks(snapshot,'gift_fixture_')))
