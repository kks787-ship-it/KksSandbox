"""Local capture candidate integration/negative tests. Synthetic temporary files/DB only."""
import copy
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import unittest
from unittest import mock
import test_local_inspection as fixture

b=fixture.b
inspector=fixture.local
path=b.BASE/'scripts/recovery/capture_local.py';spec=importlib.util.spec_from_file_location('server_local_capture',path);capture=importlib.util.module_from_spec(spec);spec.loader.exec_module(capture)

class LocalCapture(unittest.TestCase):
    def test_explicit_fresh_run_nonce_never_overwrites_or_silently_reuses(self):
        self.setup_capture();self.params['run_token']='d'*32
        result=self.run_capture()
        self.assertEqual('csf-gate-b-backup-'+'d'*32,result['bundle_id'])
        self.assertTrue(result['schema_context_complete'])
        with self.assertRaises(b.Stop):self.run_capture()
        self.params['run_token']='../outside'
        with self.assertRaises(b.Stop):self.run_capture()
    setUp=fixture.ServerLocal.setUp
    setup_local=fixture.ServerLocal.setup_local

    def setup_capture(self):
        self.setup_local()
        self.params={k:self.params[k] for k in ('actor_ref','approval_ref','start','end')}
        self.params['authorization']=capture.AUTHORIZATION
    def discover(self):return inspector.discover(b,self.budget,self.root,self.conf,self.pool,self.auth,include_private=True)
    def run_capture(self):return capture.run(b,inspector,self.params,self.root,self.parent,self.discover,lambda *_:self.db)
    def bundle(self):return self.parent/('csf-gate-b-backup-'+capture.CANDIDATE_TOKEN)

    def test_complete_private_source_map_salts_and_verified_receipt(self):
        self.setup_capture();config=self.root/'wp-config.php';config.write_bytes(config.read_bytes()+b"define('AUTH_KEY','synthetic-salt');")
        result=self.run_capture();self.assertEqual('LOCAL_CAPTURE_VERIFIED',result['code'])
        self.assertEqual({'state','code','bundle_id','manifest_sha256','receipt_sha256','offserver_verified','restore_verified','schema_phase','schema_context_complete'},set(result))
        self.assertTrue(b.verify_receipt(self.bundle(),result['receipt_sha256']))
        target=json.loads((self.bundle()/'target.json').read_text())
        self.assertIn(str(self.authfile),target['protected_sources'].values())
        self.assertEqual(config.read_bytes(),(self.bundle()/'wp-config.php.recovery').read_bytes())
        for secret in ('synthetic-password','synthetic-salt',str(self.authfile),str(self.pool)):self.assertNotIn(secret,json.dumps(result))
        self.assertFalse(result['offserver_verified'] or result['restore_verified'])

    def test_symlink_topology_retained_privately(self):
        self.setup_capture();source=self.conf/'httpd.conf';target=self.conf/'last_httpd.conf';source.rename(target);source.symlink_to(target)
        result=self.run_capture();self.assertEqual('complete',result['state'])
        private=json.loads((self.bundle()/'target.json').read_text())
        self.assertEqual(str(source),private['protected_metadata']['symlinks'][0]['path'])
        self.assertEqual(str(target),private['protected_metadata']['symlinks'][0]['target'])

    def test_reference_change_after_dump_is_incomplete(self):
        self.setup_capture();original=self.db.dump
        def dump(out):
            original(out);(self.conf/'httpd.conf').write_text('DocumentRoot /production/httpdocs\n')
        self.db.dump=dump;result=self.run_capture();self.assertEqual('CAPTURE_INCOMPLETE',result['code'])
        self.assertEqual('incomplete',json.loads((self.bundle()/'receipt.json').read_text())['state'])

    def test_alias_topology_change_is_incomplete_even_when_content_stable(self):
        self.setup_capture();original=self.discover;calls=0
        def discover():
            nonlocal calls
            result=original();calls+=1
            if calls>1:result['private_symlinks']=[{'path':'synthetic','link_text':'changed','target':'synthetic'}]
            return result
        with mock.patch.object(self,'discover',side_effect=discover):self.assertEqual('incomplete',self.run_capture()['state'])

    def test_only_fixed_candidate_no_overwrite_or_new_attempt(self):
        self.setup_capture();self.bundle().mkdir();marker=self.bundle()/'old';marker.write_bytes(b'synthetic prior attempt')
        with self.assertRaises(b.Stop):self.run_capture()
        self.assertEqual(b'synthetic prior attempt',marker.read_bytes());self.assertEqual([marker],list(self.bundle().iterdir()))

    def test_authorization_and_extra_mode_target_credentials_block(self):
        self.setup_capture();original=copy.deepcopy(self.params)
        for key,value in (('authorization','inspect'),('mode','inspect'),('root','/production/httpdocs'),('db_secret',{'password':'synthetic'})):
            self.params=copy.deepcopy(original);self.params[key]=value
            with self.assertRaises(b.Stop):self.run_capture()
        self.assertEqual([],list(self.parent.iterdir()))

    def test_wrong_wp_database_never_connects_or_creates_bundle(self):
        self.setup_capture();p=self.root/'wp-config.php';p.write_bytes(p.read_bytes().replace(b'wp_mdf4x',b'wrong_database'))
        with mock.patch.object(self.db,'guard') as guard:
            with self.assertRaises(b.Stop):self.run_capture()
            guard.assert_not_called()
        self.assertFalse(self.bundle().exists())

    def test_unexpected_protected_path_is_not_read(self):
        self.setup_capture();(self.conf/'httpd.conf').write_text('DocumentRoot "'+str(self.root)+'"\nAuthUserFile /unapproved/secret\n')
        with self.assertRaises(b.Stop):self.run_capture()
        self.assertFalse(self.bundle().exists())

    def test_non_innodb_or_wrong_table_phase_blocks_before_writes(self):
        self.setup_capture();value=self.db.catalog();value['columns'][0]['engine']='MyISAM'
        with mock.patch.object(self.db,'catalog',return_value=value):
            with self.assertRaises(ValueError):self.run_capture()
        self.assertFalse(self.bundle().exists())

    def test_schema_and_critical_data_drift_are_not_accepted(self):
        self.setup_capture();self.db.schema_change=True;self.db.changed=True
        result=self.run_capture();self.assertEqual('CAPTURE_REVIEW_REQUIRED',result['code'])
        review=json.loads((self.bundle()/'drift-review.json').read_text());self.assertTrue(review['schema_changed']);self.assertGreater(review['counts']['critical'],0)
        self.assertEqual('unknown',review['attribution'])

    def test_low_space_prevents_export_and_marks_attempt_incomplete(self):
        self.setup_capture()
        with mock.patch.object(b,'free_bytes',return_value=0),mock.patch.object(self.db,'dump') as dump:
            self.assertEqual('incomplete',self.run_capture()['state']);dump.assert_not_called()
        self.assertFalse((self.bundle()/'SHA256SUMS').exists())

    def test_database_failure_and_interruption_have_no_accepted_manifest(self):
        self.setup_capture();self.db.dump=mock.Mock(side_effect=KeyboardInterrupt())
        result=self.run_capture();self.assertEqual('incomplete',result['state']);self.assertFalse((self.bundle()/'SHA256SUMS').exists())
        self.assertFalse((self.bundle()/'dump.sql').exists())

    def test_credentials_clear_after_success_and_failure(self):
        self.setup_capture();secrets=[]
        def factory(secret,_):secrets.append(secret);return self.db
        result=capture.run(b,inspector,self.params,self.root,self.parent,self.discover,factory)
        self.assertEqual('complete',result['state']);self.assertEqual({},secrets[0])
        self.assertNotIn('synthetic-password',(self.bundle()/'target.json').read_text())

    def test_native_dump_is_single_transaction_target_only_no_shell(self):
        self.setup_capture();db=b.NativeDB({'user':'synthetic','password':'synthetic','host':'localhost'},self.budget)
        with mock.patch.object(db,'run') as run:
            db.dump(object());args=run.call_args.args[1]
        self.assertIn('--single-transaction',args);self.assertIn('--skip-lock-tables',args);self.assertEqual(b.DATABASE,args[-1])
        self.assertNotIn('--all-databases',args);self.assertNotIn('--databases',args)

    def test_main_suppresses_private_exception_text(self):
        self.setup_capture()
        with mock.patch.object(capture,'run',side_effect=RuntimeError('synthetic-password /private/path')), \
             mock.patch.object(sys,'stdin',io.TextIOWrapper(io.BytesIO(b.canonical(self.params)))), \
             mock.patch.object(sys,'stdout',io.StringIO()) as output, \
             mock.patch.object(capture.signal,'signal'),mock.patch.object(capture.signal,'setitimer'):
            self.assertEqual(2,capture.main(b,inspector))
        self.assertEqual({'state':'blocked','code':'CAPTURE_NOT_ACCEPTED'},json.loads(output.getvalue()))

    def test_standalone_invalid_binding_fails_without_target_reads(self):
        command=(b.BASE/'scripts/recovery/capture-local.command.sh').read_text()
        result=subprocess.run(['/bin/bash','-c',command],input=b'{}',env={},capture_output=True)
        self.assertEqual(2,result.returncode);self.assertEqual('CAPTURE_NOT_ACCEPTED',json.loads(result.stdout)['code']);self.assertEqual(b'',result.stderr)
        self.assertIn('--signal=TERM --kill-after=30s 1770s',command)

    def test_capture_release_is_reproducible_and_all_inputs_pinned(self):
        import hashlib
        sys.path.insert(0,str(b.BASE/'scripts/recovery'))
        try:
            import build_local_capture as builder
            for name,data in builder.artifacts().items():self.assertEqual(data,(b.BASE/name).read_bytes())
        finally:sys.path.pop(0)
        names=set()
        for line in (b.BASE/'scripts/recovery/LOCAL-CAPTURE.sha256').read_text().splitlines():
            digest,name=line.split('  ');self.assertNotIn(name,names);names.add(name)
            self.assertEqual(digest,hashlib.sha256((b.BASE/name).read_bytes()).hexdigest())
        self.assertEqual(9,len(names))
        subprocess.run(['/bin/bash','-n',str(b.BASE/'scripts/recovery/capture-local.command.sh')],check=True)

if __name__=='__main__':unittest.main()
