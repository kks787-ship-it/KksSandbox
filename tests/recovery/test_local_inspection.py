"""Synthetic server-local credential/discovery checks. No farm/Gateway access."""
import copy
import importlib.util
import io
import json
import os
from pathlib import Path
import subprocess
import sys
import time
import unittest
from unittest import mock
import test_backup as fixture

b=fixture.b
path=b.BASE/'scripts/recovery/inspect_local.py';spec=importlib.util.spec_from_file_location('server_local_inspection',path);local=importlib.util.module_from_spec(spec);spec.loader.exec_module(local)

class ServerLocal(unittest.TestCase):
    setUp=fixture.Fixture.setUp
    def setup_local(self):
        config=(self.root/'wp-config.php').read_bytes()+b"define('DB_USER','synthetic'); define('DB_PASSWORD','synthetic-password');"
        (self.root/'wp-config.php').write_bytes(config)
        self.params={k:self.v[k] for k in ('actor_ref','approval_ref','start','end','run_token')}
        self.params['end']=b.dt.datetime.fromtimestamp(b.utc(self.params['start'])+120,b.dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
        self.conf=self.base/'conf';self.conf.mkdir();self.auth=self.base/'pd';self.auth.mkdir();self.pool=self.base/'pool.conf';self.pool.write_text('[synthetic pool]\n')
        self.authfile=self.auth/'synthetic.auth';self.authfile.write_text('synthetic-private-auth')
        (self.conf/'httpd.conf').write_text('DocumentRoot "'+str(self.root)+'"\nAuthUserFile "'+str(self.authfile)+'"\n')
    def discover(self,bb,budget,root):return local.discover(bb,budget,root,self.conf,self.pool,self.auth)
    def run_local(self):return local.run(b,self.params,self.root,self.parent,self.discover,lambda secret,budget:self.db)
    def test_complete_private_discovery_no_creds_or_paths_in_output(self):
        self.setup_local();self.db.dump=mock.Mock(side_effect=AssertionError('dump forbidden'))
        with mock.patch.object(b,'capture',side_effect=AssertionError('capture forbidden')),mock.patch.object(b,'private_write',side_effect=AssertionError('write forbidden')):
            result=self.run_local()
        self.assertEqual('prerequisites-pass',result['state']);text=json.dumps(result)
        for secret in ('synthetic-password','synthetic-private-auth',str(self.authfile),str(self.conf)):self.assertNotIn(secret,text)
        self.assertEqual([],list(self.parent.iterdir()));self.db.dump.assert_not_called();self.assertEqual(0,self.db.reads)
    def test_auth_scope_is_the_sandbox_not_parent_subscription(self):
        self.assertEqual(Path('/var/www/vhosts/system')/local.DOMAIN/'pd',local.AUTH)
        self.assertNotEqual(Path('/var/www/vhosts/system/cornerstonefarmomak.com/pd'),local.AUTH)
    def test_certificate_validation_auxiliary_roots_only(self):
        root=Path('/synthetic/sandbox')
        for location in ('/.well-known/acme-challenge/','/.well-known/pki-validation/'):
            text='server {\nroot /synthetic/sandbox;\nlocation ^~ '+location+' {\nroot /var/www/vhosts/default/htdocs;\n}\n}'
            self.assertTrue(local.validate_roots(b,text,root))
            with self.assertRaises(b.Stop):local.validate_roots(b,text.replace('/var/www/vhosts/default/htdocs','/production/httpdocs'),root)
            with self.assertRaises(b.Stop):local.validate_roots(b,text.replace(location,'/unrelated/'),root)
    def test_nested_quoted_certificate_validation_location(self):
        text='server {\nroot /synthetic/sandbox;\nlocation ~ "^/\\.well-known/acme-challenge/(.*)" {\nif ($x) { return 1; }\nroot /var/www/vhosts/default/htdocs;\n}\n}'
        self.assertTrue(local.validate_roots(b,text,Path('/synthetic/sandbox')))
    def test_shared_static_root_is_never_a_main_document_root(self):
        with self.assertRaises(b.Stop):local.validate_roots(b,'root /var/www/vhosts/default/htdocs;\n',Path('/synthetic/sandbox'))
    def test_literal_credential_escapes_and_no_php_eval(self):
        self.setup_local();raw=(self.root/'wp-config.php').read_bytes().replace(b"'synthetic-password'",b"'synthetic\\'pass\\\\word'")
        secret=b.validate_config(raw,credentials=True);self.assertEqual("synthetic'pass\\word",secret['password'])
    def test_dynamic_commented_conditional_and_remote_credentials_blocked(self):
        self.setup_local();original=(self.root/'wp-config.php').read_bytes()
        for value in (original.replace(b"'synthetic-password'",b"getenv('SECRET')"),original.replace(b"define('DB_USER','synthetic');",b"/* define('DB_USER','synthetic'); */"),original.replace(b"define('DB_USER','synthetic');",b"if (true) define('DB_USER','synthetic');"),original.replace(b"'localhost'",b"'remote.invalid'")):
            with self.assertRaises(b.Stop):b.validate_config(value,credentials=True)
    def test_missing_pool_or_auth_blocks(self):
        self.setup_local();self.pool.unlink()
        with self.assertRaises(b.Stop):self.run_local()
    def test_production_root_and_outside_includes_block(self):
        self.setup_local()
        for text in ('DocumentRoot /production/httpdocs\n','DocumentRoot "'+str(self.root)+'"\nInclude /etc/plesk/global.conf\n'):
            (self.conf/'httpd.conf').write_text(text)
            with self.assertRaises((b.Stop,FileNotFoundError)):self.run_local()
    def test_auth_reference_outside_exact_area_blocks(self):
        self.setup_local();other=self.base/'unrelated';other.write_text('synthetic-private')
        (self.conf/'httpd.conf').write_text('DocumentRoot "'+str(self.root)+'"\nAuthUserFile "'+str(other)+'"\n')
        with self.assertRaises(b.Stop):self.run_local()
    def test_same_conf_generated_symlink_allowed_outside_link_blocked(self):
        self.setup_local();(self.conf/'httpd.conf').rename(self.conf/'last_httpd.conf');(self.conf/'httpd.conf').symlink_to(self.conf/'last_httpd.conf')
        self.assertEqual('prerequisites-pass',self.run_local()['state'])
        (self.conf/'httpd.conf').unlink();(self.conf/'httpd.conf').symlink_to(self.authfile)
        with self.assertRaises(b.Stop):self.run_local()
    def test_extra_credential_or_capture_parameter_rejected(self):
        self.setup_local();self.params['db_secret']={'password':'synthetic'}
        with self.assertRaises(b.Stop):self.run_local()
    def test_window_limit_and_missing_target_credentials_fail(self):
        self.setup_local();self.params['end']='2099-01-01T00:00:00Z'
        with self.assertRaises(b.Stop):self.run_local()
    def test_native_versions_incompatible_stops(self):
        self.setup_local();self.db.versions=mock.Mock(side_effect=b.Stop('incompatible'))
        with self.assertRaises(b.Stop):self.run_local()
    def test_standalone_command_needs_no_stored_code_or_secret(self):
        command=(b.BASE/'scripts/recovery/inspect-local.command.sh').read_text()
        result=subprocess.run(['/bin/bash','-c',command],input=b'{}',env={},capture_output=True)
        self.assertEqual(2,result.returncode);self.assertEqual('blocked',json.loads(result.stdout)['state']);self.assertEqual(b'',result.stderr)
    def test_local_release_manifest_and_shell_syntax(self):
        import hashlib
        observed=set()
        for line in (b.BASE/'scripts/recovery/LOCAL-INSPECTION.sha256').read_text().splitlines():
            digest,name=line.split('  ');self.assertNotIn(name,observed);observed.add(name)
            self.assertEqual(digest,hashlib.sha256((b.BASE/name).read_bytes()).hexdigest())
        self.assertEqual(8,len(observed))
        subprocess.run(['/bin/bash','-n',str(b.BASE/'scripts/recovery/inspect-local.command.sh')],check=True)
    def test_build_is_reproducible(self):
        sys.path.insert(0,str(b.BASE/'scripts/recovery'))
        try:
            import build_local_inspection as builder
            for name,data in builder.artifacts().items():self.assertEqual(data,(b.BASE/name).read_bytes())
        finally:sys.path.pop(0)

if __name__=='__main__':unittest.main()
