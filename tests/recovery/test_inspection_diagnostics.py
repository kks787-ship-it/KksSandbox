"""Local synthetic diagnostic branch/privacy tests; no Gateway or farm access."""
import io
import json
from pathlib import Path
import signal
import subprocess
import sys
import time
import unittest
from unittest import mock
import test_local_inspection as fixture

b=fixture.b
local=fixture.local

class Diagnostics(unittest.TestCase):
    setUp=fixture.ServerLocal.setUp
    setup_local=fixture.ServerLocal.setup_local
    discover=fixture.ServerLocal.discover

    def invoke(self,effect=None):
        actual=local.run
        def scoped(bb,params):return actual(bb,params,self.root,self.parent,self.discover,lambda *_:self.db)
        with mock.patch.object(local,'run',side_effect=effect or scoped), \
             mock.patch.object(sys,'stdin',io.TextIOWrapper(io.BytesIO(b.canonical(self.params)))), \
             mock.patch.object(sys,'stdout',io.StringIO()) as out, \
             mock.patch.object(local.signal,'signal'),mock.patch.object(local.signal,'setitimer'), \
             mock.patch.object(b,'capture',side_effect=AssertionError('capture forbidden')), \
             mock.patch.object(b,'private_write',side_effect=AssertionError('write forbidden')):
            status=local.main(b)
        receipt=json.loads(out.getvalue());self.assertEqual({'state','code','stage'},set(receipt))
        self.assertIn(receipt['code'],local.CODES);self.assertEqual(local.CODES[receipt['code']],receipt['stage'])
        for secret in ('synthetic-password','synthetic-private-auth',str(self.root),str(self.conf),str(self.authfile)):
            self.assertNotIn(secret,out.getvalue())
        if self.parent.exists():self.assertEqual([],list(self.parent.iterdir()))
        return status,receipt

    def check(self,code):
        status,receipt=self.invoke();self.assertEqual(2,status);self.assertEqual(code,receipt['code']);self.assertEqual('blocked',receipt['state'])

    def test_pass_is_fixed_not_private_run_result(self):
        self.setup_local();status,receipt=self.invoke();self.assertEqual(0,status);self.assertEqual(local.safe_receipt('CHECKS_PASSED'),receipt)

    def test_input_and_window_guards(self):
        self.setup_local();saved=self.params.copy();del self.params['actor_ref'];self.check('INPUT_UNVERIFIED')
        self.params=saved;self.params['end']='2099-01-01T00:00:00Z';self.check('TIME_WINDOW_UNVERIFIED')

    def test_source_and_config_path_guards(self):
        self.setup_local();self.parent.rename(self.base/'elsewhere');self.check('SOURCE_SCOPE_BLOCKED')
        self.parent.mkdir();(self.root/'wp-config.php').unlink();self.check('CONFIG_PATH_UNVERIFIED')

    def test_config_literals_no_password_or_source_snippets(self):
        self.setup_local();path=self.root/'wp-config.php';path.write_bytes(path.read_bytes().replace(b"'synthetic-password'",b"getenv('synthetic-password')"));self.check('CONFIG_LITERALS_UNSUPPORTED')

    def test_discovery_path_and_scope_guards(self):
        self.setup_local();self.conf.rename(self.base/'moved');self.check('PLESK_CONFIG_PATH_UNVERIFIED')
        (self.base/'moved').rename(self.conf);(self.conf/'nginx.conf').symlink_to(self.authfile);self.check('PLESK_SCOPE_BLOCKED')

    def test_config_permission_guard(self):
        self.setup_local();original=b.read_regular
        def read(path,*args):
            if Path(path)==self.conf/'httpd.conf':raise PermissionError('synthetic private diagnostic')
            return original(path,*args)
        with mock.patch.object(b,'read_regular',side_effect=read):self.check('PLESK_CONFIG_READ_BLOCKED')

    def test_root_include_and_pool_guards(self):
        self.setup_local();path=self.conf/'httpd.conf';saved=path.read_text();path.write_text('DocumentRoot /unapproved/root\n');self.check('PLESK_ROOT_MISMATCH')
        path.write_text(saved+'Include /etc/global-unapproved.conf\n');self.check('PLESK_INCLUDE_SCOPE_BLOCKED')
        path.write_text(saved);self.pool.unlink();self.check('PLESK_POOL_UNVERIFIED')

    def test_auth_reference_and_read_guards(self):
        self.setup_local();path=self.conf/'httpd.conf';saved=path.read_text();path.write_text('DocumentRoot "'+str(self.root)+'"\nAuthUserFile /unapproved/credential\n');self.check('HTTP_AUTH_SCOPE_BLOCKED')
        path.write_text(saved);original=b.read_regular
        def read(path,*args):
            if Path(path)==self.authfile:raise PermissionError('synthetic private diagnostic')
            return original(path,*args)
        with mock.patch.object(b,'read_regular',side_effect=read):self.check('HTTP_AUTH_READ_BLOCKED')

    def test_db_client_target_metadata_and_schema_guards(self):
        self.setup_local()
        for method,code in (('versions','DB_CLIENT_UNAVAILABLE'),('guard','DB_TARGET_UNVERIFIED'),('catalog','DB_METADATA_UNAVAILABLE')):
            with self.subTest(code=code),mock.patch.object(self.db,method,side_effect=b.Stop('synthetic private sql')):self.check(code)
        with mock.patch.object(self.db,'catalog',return_value={'columns':[],'indexes':[]}):self.check('DB_SCHEMA_UNVERIFIED')

    def test_inventory_recheck_and_disk_guards(self):
        self.setup_local()
        with mock.patch.object(b,'inventory',side_effect=b.Stop('synthetic private file')):self.check('SOURCE_INVENTORY_BLOCKED')
        original=b.read_regular;reads=0
        def read(path,*args):
            nonlocal reads
            result=original(path,*args)
            if Path(path)==self.root/'wp-config.php':
                reads+=1
                if reads==2:return b'synthetic changed config',result[1]
            return result
        with mock.patch.object(b,'read_regular',side_effect=read):self.check('CONFIG_RECHECK_UNVERIFIED')
        with mock.patch.object(b,'free_bytes',return_value=0):self.check('DISK_RESERVE_UNVERIFIED')

    def test_all_known_failures_use_fixed_allowlist_and_unknowns_stay_generic(self):
        self.setup_local()
        for code in local.CODES:
            if code=='CHECKS_PASSED':continue
            def stop(*_,code=code):local.fail(b,code)
            status,receipt=self.invoke(stop);self.assertEqual(2,status);self.assertEqual(code,receipt['code'])
        for code in ('secret-value-not-a-code',{'synthetic':'password'},'CHECKS_PASSED',None):
            error=RuntimeError('synthetic-password');error.inspection_code=code
            status,receipt=self.invoke(lambda *_,error=error:(_ for _ in ()).throw(error))
            self.assertEqual(2,status);self.assertEqual('BLOCKED_UNCLASSIFIED',receipt['code'])

    def test_success_payload_private_fields_never_serialized(self):
        self.setup_local();status,receipt=self.invoke(lambda *_:{'state':'prerequisites-pass','password':'synthetic-password','hash':'synthetic-private-auth'})
        self.assertEqual(0,status);self.assertEqual(local.safe_receipt('CHECKS_PASSED'),receipt)

    def test_signal_handler_timeout_code_and_timer_disarm(self):
        self.setup_local();handlers={}
        def register(number,fn):handlers[number]=fn
        with mock.patch.object(local.signal,'signal',side_effect=register),mock.patch.object(local.signal,'setitimer') as timer, \
             mock.patch.object(local,'run',side_effect=lambda *_:handlers[signal.SIGALRM](signal.SIGALRM,None)), \
             mock.patch.object(sys,'stdin',io.TextIOWrapper(io.BytesIO(b.canonical(self.params)))),mock.patch.object(sys,'stdout',io.StringIO()) as out:
            self.assertEqual(2,local.main(b))
        self.assertEqual('TIME_LIMIT_REACHED',json.loads(out.getvalue())['code'])
        self.assertIn(mock.call(signal.ITIMER_REAL,115),timer.call_args_list);self.assertEqual(mock.call(signal.ITIMER_REAL,0),timer.call_args_list[-1])

    def test_term_is_interruption_without_claiming_timeout(self):
        self.setup_local();handlers={}
        with mock.patch.object(local.signal,'signal',side_effect=lambda number,fn:handlers.update({number:fn})),mock.patch.object(local.signal,'setitimer'), \
             mock.patch.object(local,'run',side_effect=lambda *_:handlers[signal.SIGTERM](signal.SIGTERM,None)), \
             mock.patch.object(sys,'stdin',io.TextIOWrapper(io.BytesIO(b.canonical(self.params)))),mock.patch.object(sys,'stdout',io.StringIO()) as out:
            self.assertEqual(2,local.main(b))
        self.assertEqual('EXECUTION_INTERRUPTED',json.loads(out.getvalue())['code'])

    def test_kill_semantics_and_exact_reviewed_wrapper_limit(self):
        command=(b.BASE/'scripts/recovery/inspect-local.command.sh').read_text();self.assertIn('--signal=TERM --kill-after=5s 115s',command)
        start=time.monotonic()
        r=subprocess.run(['/usr/bin/timeout','--signal=TERM','--kill-after=0.1s','0.1s',sys.executable,'-c','import signal,time;signal.signal(signal.SIGTERM,signal.SIG_IGN);time.sleep(10)'],capture_output=True)
        self.assertIn(r.returncode,(-9,137));self.assertLess(time.monotonic()-start,2)

if __name__=='__main__':unittest.main()
