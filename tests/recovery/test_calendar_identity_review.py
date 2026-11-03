"""Synthetic-only private-input validation; never opens real account records."""
import copy
import importlib.util
import json
import re
import unittest
import test_backup as fixture
spec=importlib.util.spec_from_file_location('identity_review',fixture.b.BASE/'scripts/pilot/verify_calendar_identities.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)

class DB:
    def __init__(self):self.queries=[];self.roles=['a:1:{s:13:"administrator";b:1;}','a:1:{s:10:"subscriber";b:1;}','a:1:{s:10:"subscriber";b:1;}'];self.ids=[11,22,33]
    def query(self,sql):
        self.queries.append(sql);i=len(self.queries)-1
        return json.dumps({'id':self.ids[i],'roles':self.roles[i]}).encode()

class IdentityReview(unittest.TestCase):
    def value(self):return {'version':1,'purpose':'calendar-identity-review-only','accounts':{p:{'login':'synthetic-'+p,'controller_ref':'private-record','recovery_ref':'private-record'}for p in m.PEOPLE},'human_independence':'undecided','update_custodian_ref':'','native_security_mail':'hold'}
    def test_existing_mapping_does_not_authorize_any_execution(self):
        db=DB();r=m.verify(db,'fixture_',self.value());self.assertTrue(r['distinct_existing_accounts']);self.assertFalse(r['writes']);self.assertFalse(r['execution_authorized']);self.assertFalse(r['controllers_independently_verified']);self.assertFalse(r['mfa_or_recovery_verified'])
        text=json.dumps(r);self.assertNotIn('synthetic-',text)
        for identifier in ('11','22','33'):self.assertNotIn(identifier,text)
    def test_no_plaintext_login_or_sql_injection(self):
        v=self.value();v['accounts']['operations']['login']="quote' OR 1=1 --";db=DB();m.verify(db,'fixture_',v)
        self.assertNotIn(v['accounts']['operations']['login'],db.queries[1]);self.assertIn(v['accounts']['operations']['login'].encode().hex(),db.queries[1]);self.assertTrue(all(q.startswith('SELECT ')for q in db.queries))
    def test_duplicate_private_mapping_denied(self):
        v=self.value();v['accounts']['head_trainer']['login']=v['accounts']['operations']['login']
        with self.assertRaises(m.Blocked):m.inputs(v)
        db=DB();db.ids=[11,22,22]
        with self.assertRaises(m.Blocked):m.verify(db,'fixture_',self.value())
    def test_no_subscriber_promotion_inferred(self):
        db=DB();db.roles[1]='a:1:{s:13:"administrator";b:1;}'
        with self.assertRaises(m.Blocked):m.verify(db,'fixture_',self.value())
    def test_unknown_extra_fields_and_authentication_values_rejected(self):
        for key in ('password','totp_seed','approve_enforcement','target'):
            v=self.value();v[key]='not-allowed'
            with self.assertRaises(m.Blocked):m.inputs(v)
    def test_control_characters_and_oversized_names(self):
        for value in ('x\n','\0','x'*61):
            v=self.value();v['accounts']['operations']['login']=value
            with self.assertRaises(m.Blocked):m.inputs(v)
    def test_strict_nonexecuting_capability_parser(self):
        for value in ('O:8:"BadClass":0:{}','a:1:{s:9:"subscriber";b:1;}','a:2:{s:10:"subscriber";b:1;s:10:"subscriber";b:1;}','a:1:{s:10:"subscriber";s:1:"1";}'):
            with self.assertRaises(m.Blocked):m.capabilities(value)
    def test_additional_capabilities_are_reported_not_changed(self):
        db=DB();db.roles[2]='a:2:{s:10:"subscriber";b:1;s:14:"manage_options";b:1;}'
        r=m.verify(db,'fixture_',self.value());self.assertTrue(r['additional_capabilities_need_review']);self.assertFalse(r['execution_authorized'])
    def test_missing_account_is_not_guessed(self):
        class Empty:
            def query(self,sql):return b''
        with self.assertRaises(m.Blocked):m.verify(Empty(),'fixture_',self.value())
    def test_deterministic_bounded_transport(self):
        import sys
        sys.path.insert(0,str(fixture.b.BASE/'scripts/pilot'));import build_identity_review
        one=build_identity_review.command();self.assertEqual(one,build_identity_review.command());self.assertLess(len(one),65536);self.assertIn(b'55s',one);self.assertIn(b'--kill-after=5s',one)
    def test_empty_private_input_fails_before_target_access(self):
        import sys,subprocess
        sys.path.insert(0,str(fixture.b.BASE/'scripts/pilot'));import build_identity_review
        r=subprocess.run(['/bin/bash','-c',build_identity_review.command().decode()],input=b'{}',capture_output=True,timeout=5)
        self.assertEqual(2,r.returncode);result=json.loads(r.stdout);self.assertEqual('INPUT_INVALID',result['code']);self.assertFalse(result['writes']);self.assertFalse(result['execution_authorized'])
    def test_target_prefix_cannot_be_overridden(self):
        with self.assertRaises(m.Blocked):m.verify(DB(),'bad`;',self.value())

if __name__=='__main__':unittest.main()
