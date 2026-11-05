"""Synthetic exact-contract and damaged-input checks; no farm access."""
import copy
import io
import json
from pathlib import Path
import unittest
import test_backup as fixture
import test_local_restore as restore_fixture

b=fixture.b
r=restore_fixture.r


class SchedulingRecovery(unittest.TestCase):
    def catalog(self,scheduled=15,version=3):
        c=fixture.FakeDB().catalog()
        names=sorted(b.schema_contract()['SCHEDULING'])[:scheduled]
        for name in names:c['columns'].append({'table':b.PREFIX+name,'column':'id','ordinal':1,'pk_order':1,'engine':'InnoDB'})
        if scheduled:c['scheduling']={'version':version,'settings_rows':1}
        return c

    def dump(self,names):
        return ''.join('CREATE TABLE `'+n+'` (`id` int PRIMARY KEY);INSERT INTO `'+n+'` VALUES(1);' for n in sorted(names))

    def test_names_come_from_reviewed_schema_three_inventory(self):
        inventory=json.loads((b.BASE/'docs/test-results/scheduling-security-install-inventory.json').read_text())
        self.assertEqual({x.removeprefix(b.PREFIX) for x in inventory['tables_source_review_only']},set(b.schema_contract()['SCHEDULING']))

    def test_native_full_and_partial_preserve_every_present_table(self):
        for size in (0,1,7,14,15):
            catalog=self.catalog(size);names={x['table'] for x in catalog['columns']}
            sql=b.load_generator()(catalog)
            self.assertEqual(12+size,sql.count("'key_hash'"))
            self.assertEqual(12+size,len(r.validate_sql(b,io.StringIO(self.dump(names)),names)))

    def test_legacy_twelve_table_receipt_remains_verifiable(self):
        f=fixture.Fixture();f.setUp()
        try:
            self.assertEqual('complete',f.capture()['state'])
            path=f.bundle()/'receipt.json';receipt=json.loads(path.read_bytes())
            receipt.pop('schema_phase');receipt.pop('expected_phase')
            path.write_bytes(b.canonical(receipt)+b'\n')
            self.assertTrue(b.verify_receipt(f.bundle(),b.sha(path.read_bytes())))
        finally:f.doCleanups()

    def test_missing_after_activation_is_not_preinstall_or_healthy(self):
        phase=b.schema_contract()['phase']
        self.assertEqual('pre_scheduling',phase(self.catalog(0)))
        self.assertEqual('scheduling_v3',phase(self.catalog()))
        self.assertEqual('damaged_scheduling_v3',phase(self.catalog(14)))
        self.assertEqual('partial_migration',phase(self.catalog(7,0)))
        self.assertEqual('partial_migration',phase(self.catalog(15,None)))

    def test_unexpected_table_prefix_or_missing_native_refused(self):
        for name in ('TXGEh_csf_sched_unknown','other_csf_sched_settings','TXGEh_unrelated'):
            c=self.catalog();x=copy.deepcopy(c['columns'][-1]);x['table']=name;c['columns'].append(x)
            with self.assertRaises(ValueError):b.load_generator()(c)
        c=self.catalog();c['columns']=[x for x in c['columns'] if x['table']!=b.PREFIX+'users']
        with self.assertRaises(ValueError):b.load_generator()(c)

    def test_sql_must_match_catalog_not_merely_maximum_allowlist(self):
        names={x['table'] for x in self.catalog()['columns']}
        for sql in (self.dump(names-{b.PREFIX+'csf_sched_authorization'}),self.dump(names)+'CREATE TABLE TXGEh_csf_sched_extra(id int);',self.dump(names)+'CREATE TABLE TXGEh_users(id int);'):
            with self.assertRaises(r.Failure):r.validate_sql(b,io.StringIO(sql),names)

    def test_scheduling_rows_and_auth_changes_remain_critical(self):
        c=self.catalog();header={'database':b.DATABASE,'prefix':b.PREFIX,'version':1}
        row={'table':'csf_sched_audit','key_hash':'a'*64,'value_hash':'b'*64,'label':None}
        before=b.parse_rows(b'\n'.join(b.canonical(x) for x in [header,row]),c)
        row['value_hash']='c'*64
        after=b.parse_rows(b'\n'.join(b.canonical(x) for x in [header,row]),c)
        diff=b.drift(before,after,json.loads((b.BASE/'docs/pilot/baseline-contract.json').read_text()))
        self.assertEqual(1,diff['counts']['critical']);self.assertEqual('unknown',diff['attribution'])

    def test_literal_mfa_booleans_only_no_strings_or_dynamic_expressions(self):
        self.assertEqual({'CSF_MFA_ENABLED':True,'CSF_MFA_ENFORCE_ADMIN':False},r.security_flags(b"<?php define('CSF_MFA_ENABLED',true);define('CSF_MFA_ENFORCE_ADMIN',false);"))
        for raw in (b"<?php define('CSF_MFA_ENABLED',getenv('X'));",b"<?php $x=\"define('CSF_MFA_ENABLED',true);\";",b"<?php if(false)define('CSF_MFA_ENABLED',true);",b"<?php define('CSF_MFA_ENABLED',true);define('CSF_MFA_ENABLED',false);"):
            self.assertFalse(r.security_config_safe(raw))

    def test_only_reviewed_plugin_list_is_accepted(self):
        names=['csf-core/csf-core.php','two-factor/two-factor.php','csf-mfa/csf-mfa.php','csf-scheduling/csf-scheduling.php']
        raw='a:4:{'+''.join('i:'+str(i)+';s:'+str(len(n))+':"'+n+'";' for i,n in enumerate(names))+'}'
        self.assertEqual(names,r.plugin_names(raw))
        for raw in ('a:1:{i:0;s:1:"bad.php";}','O:8:"stdClass":0:{}','a:1:{i:0;s:21:"csf-mfa/csf-mfa.php";}'):
            with self.assertRaises(r.Failure):r.plugin_names(raw)

    def test_postmigration_expectation_refuses_all_scheduling_tables_missing(self):
        f=fixture.Fixture();f.setUp()
        try:
            f.v['expected_phase']='scheduling_v3'
            result=f.capture()
            self.assertEqual('incomplete',result['state'])
            self.assertFalse((f.bundle()/'database.sql.gz').exists())
            with self.assertRaises(b.Stop):b.verify_receipt(f.bundle(),b.sha((f.bundle()/'receipt.json').read_bytes()))
        finally:f.doCleanups()

    def test_schema_marker_and_constraint_drift_cannot_pass(self):
        before=self.catalog();after=copy.deepcopy(before)
        after['scheduling']['version']=0
        self.assertNotEqual(b.schema_digest(before),b.schema_digest(after))

        before['constraints']=[{'table':'TXGEh_csf_sched_resources','name':'fixture','type':'CHECK'}]
        after=copy.deepcopy(before);after['constraints']=[]
        self.assertNotEqual(b.schema_digest(before),b.schema_digest(after))

    def test_arbitrary_set_insert_select_and_table_rename_are_not_dump_sql(self):
        names={x['table'] for x in self.catalog()['columns']}
        for extra in ("SET SQL_MODE='NO_BACKSLASH_ESCAPES';",'SET @x=(SELECT 1);','INSERT INTO TXGEh_users SELECT * FROM TXGEh_users;','ALTER TABLE TXGEh_users RENAME TO TXGEh_outside;'):
            with self.assertRaises(r.Failure):r.validate_sql(b,io.StringIO(self.dump(names)+extra),names)
