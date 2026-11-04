"""FastCGI framing and fixed runtime guard tests; no mail or farm access."""
import importlib.util
import io
import json
import struct
import hashlib
import datetime
import tempfile
import subprocess
import stat
import socket
from pathlib import Path
from unittest.mock import patch
from types import SimpleNamespace
import unittest
import test_backup as fixture

spec = importlib.util.spec_from_file_location('native_runtime', fixture.b.BASE / 'scripts/pilot/native_mail_runtime.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


class Connection:
    def __init__(self, raw): self.raw = io.BytesIO(raw)
    def recv(self, n): return self.raw.read(min(n, 3))


class NativeMailRuntimeTests(unittest.TestCase):
    def valid_response(self):return {'state':'native-mail-runtime-qualified','binding_fingerprint':'a'*64,'distinct_recipient_count':1,'native_sender_qualified':True,'unexpected_mail_hooks':False,'blocked_bootstrap_mail':0,'mail_sent':False,'execution_authorized':False}
    def test_quoted_pool_values_preserve_exact_socket_scope(self):
        directory=Path('/synthetic-scope')
        path=directory/'native.sock'
        with patch.object(Path,'resolve',return_value=path),patch.object(Path,'lstat',return_value=SimpleNamespace(st_mode=stat.S_IFSOCK)),patch.object(m.pwd,'getpwnam',return_value=SimpleNamespace(pw_uid=1234)):
            result=m.pool_target('[synthetic.invalid]\nuser = "synthetic-user"\nlisten = "'+str(path)+'"\n','synthetic.invalid',directory)
            self.assertEqual(((socket.AF_UNIX,str(path)),1234),result)
            with self.assertRaises(ValueError):m.pool_target('[synthetic.invalid]\nuser = synthetic\nlisten = '+str(path)+'\n','synthetic.invalid',directory/'other')

    def test_only_explicit_loopback_pool_listeners_are_accepted(self):
        with patch.object(m.pwd,'getpwnam',return_value=SimpleNamespace(pw_uid=1234)):
            result=m.pool_target('[synthetic.invalid]\nuser = synthetic\nlisten = 127.0.0.1:9009\n','synthetic.invalid',Path('/synthetic'))
            self.assertEqual(((socket.AF_INET,('127.0.0.1',9009)),1234),result)
            for listener in ('0.0.0.0:9009','10.0.0.1:9009','example.invalid:9009','9009','127.0.0.1:0','127.0.0.1:99999'):
                with self.assertRaises(ValueError):m.pool_target('[synthetic.invalid]\nuser = synthetic\nlisten = '+listener+'\n','synthetic.invalid',Path('/synthetic'))

    def test_relative_socket_requires_an_explicit_scoped_prefix(self):
        directory=Path('/synthetic/synthetic.invalid')
        with patch.object(Path,'resolve',lambda self,strict=False:self),patch.object(Path,'lstat',return_value=SimpleNamespace(st_mode=stat.S_IFSOCK)),patch.object(m.pwd,'getpwnam',return_value=SimpleNamespace(pw_uid=1234)):
            result=m.pool_target('[synthetic.invalid]\nuser = synthetic\nprefix = /synthetic/$pool\nlisten = native.sock\n','synthetic.invalid',directory)
            self.assertEqual(((socket.AF_UNIX,str(directory/'native.sock')),1234),result)
            with self.assertRaises(ValueError):m.pool_target('[synthetic.invalid]\nuser = synthetic\nlisten = native.sock\n','synthetic.invalid',directory)
            with self.assertRaises(ValueError):m.pool_target('[synthetic.invalid]\nuser = synthetic\nprefix = /other\nlisten = native.sock\n','synthetic.invalid',directory)
    def test_fcgi_response_handles_fragmentation_and_does_not_return_headers(self):
        stdout = b'Content-Type: application/json\r\nX-Private: not-returned\r\n\r\n' + json.dumps(self.valid_response()).encode()
        raw = m.fcgi_record(6, stdout) + m.fcgi_record(3, b'\0'*8)
        self.assertEqual(self.valid_response(), m.fcgi_response(Connection(raw)))

    def test_runtime_result_rejects_unexpected_or_sensitive_fields(self):
        r=self.valid_response();r['email']='private@example.invalid'
        with self.assertRaises(ValueError):m.runtime_result(r)
        with self.assertRaises(ValueError):m.runtime_result({'state':'private-error','secret':'not-returned'})

    def test_consent_requires_exact_scope_and_current_bounded_window(self):
        b=SimpleNamespace(utc=fixture.b.utc)
        now=datetime.datetime.now(datetime.timezone.utc)
        start=(now-datetime.timedelta(seconds=5)).strftime('%Y-%m-%dT%H:%M:%SZ')
        end=(now+datetime.timedelta(seconds=60)).strftime('%Y-%m-%dT%H:%M:%SZ')
        value={'authorization':'one-native-security-mail-test','approval_reference':'synthetic-owner-consent','start':start,'end':end}
        self.assertGreater(m.consent_window(b,value),m.time.time())
        for altered in ({**value,'authorization':'F2-policy-only'},{**value,'end':start},{**value,'extra':'not-allowed'}):
            with self.assertRaises(ValueError):m.consent_window(b,altered)

    def test_dispatch_package_cannot_treat_policy_only_as_send_authorization(self):
        import sys
        sys.path.insert(0,str(fixture.b.BASE/'scripts/pilot'))
        import build_native_mail_runtime
        plan={'snapshot':{'binding_fingerprint':'a'*64,'distinct_recipient_count':1,'queue_intervals':{'synthetic':1}},'test_ref':'b'*32}
        command=build_native_mail_runtime.command(None,plan)
        self.assertEqual(command,build_native_mail_runtime.command(None,plan))
        self.assertLessEqual(len(command),65536)
        response=subprocess.run(['/bin/bash','-c',command.decode()],input=b'{"authorization":"F2-policy-only"}',capture_output=True,timeout=5)
        self.assertEqual(2,response.returncode)
        self.assertEqual('blocked-or-unknown',json.loads(response.stdout)['state'])

    def test_dispatch_claims_precede_runtime_and_prevent_resubmission_after_lost_result(self):
        import sys
        sys.path.insert(0,str(fixture.b.BASE/'scripts/pilot'))
        import security_mail_ledger
        with tempfile.TemporaryDirectory() as directory:
            now=datetime.datetime.now(datetime.timezone.utc)
            value={'authorization':'one-native-security-mail-test','approval_reference':'synthetic-consent','start':(now-datetime.timedelta(seconds=5)).strftime('%Y-%m-%dT%H:%M:%SZ'),'end':(now+datetime.timedelta(seconds=60)).strftime('%Y-%m-%dT%H:%M:%SZ')}
            backup={'bundle_id':'csf-gate-b-backup-'+'b'*32,'receipt_sha256':'c'*64,'manifest_sha256':'d'*64}
            receipt={'ended':now.strftime('%Y-%m-%dT%H:%M:%SZ'),'manifest_sha256':'d'*64}
            b=SimpleNamespace(PARENT=Path(directory),require=lambda ok:fixture.b.require(ok),utc=fixture.b.utc,verify_receipt=lambda *a:True,
                              Budget=fixture.b.Budget,read_regular=lambda *a:(json.dumps(receipt).encode(),None),QUEUE_LIMITS=lambda:{'synthetic':1},
                              sha=lambda x:hashlib.sha256(x).hexdigest(),canonical=lambda x:json.dumps(x).encode(),private_write=lambda p,x:p.write_bytes(x))
            snapshot={'binding_fingerprint':'a'*64,'distinct_recipient_count':1,'queue_intervals':{'synthetic':1}}
            def lost(*args,**kwargs):
                path=Path(directory)/('csf-native-mail-test-'+'e'*32)/'submission.sqlite'
                import sqlite3
                conn=sqlite3.connect(path)
                self.assertEqual(1,conn.execute('SELECT COUNT(*) FROM submissions').fetchone()[0]);conn.close()
                raise TimeoutError('Synthetic lost result')
            with patch.object(m,'preview',side_effect=lost):
                with self.assertRaises(TimeoutError):m.dispatch(b,None,'',backup,snapshot,'e'*32,security_mail_ledger.SubmissionLedger,value)
            with patch.object(m,'preview',side_effect=AssertionError('Must not submit again')):
                out=m.dispatch(b,None,'',backup,snapshot,'e'*32,security_mail_ledger.SubmissionLedger,value)
                self.assertEqual('existing-attempt-no-resubmission',out['state'])

    def test_queue_drift_and_stale_recovery_stop_before_runtime_or_claims(self):
        with tempfile.TemporaryDirectory() as directory:
            now=datetime.datetime.now(datetime.timezone.utc)
            consent={'authorization':'one-native-security-mail-test','approval_reference':'synthetic-consent','start':(now-datetime.timedelta(seconds=5)).strftime('%Y-%m-%dT%H:%M:%SZ'),'end':(now+datetime.timedelta(seconds=60)).strftime('%Y-%m-%dT%H:%M:%SZ')}
            backup={'bundle_id':'csf-gate-b-backup-'+'b'*32,'receipt_sha256':'c'*64,'manifest_sha256':'d'*64}
            snapshot={'binding_fingerprint':'a'*64,'distinct_recipient_count':1,'queue_intervals':{'synthetic':1}}
            receipt={'ended':(now-datetime.timedelta(days=2)).strftime('%Y-%m-%dT%H:%M:%SZ'),'manifest_sha256':'d'*64}
            b=SimpleNamespace(PARENT=Path(directory),require=fixture.b.require,utc=fixture.b.utc,QUEUE_LIMITS=lambda:{'synthetic':2},verify_receipt=lambda *a:True,Budget=fixture.b.Budget,read_regular=lambda *a:(json.dumps(receipt).encode(),None))
            with patch.object(m,'preview',side_effect=AssertionError('Runtime must not start')):
                with self.assertRaises(ValueError):m.dispatch(b,None,'',backup,snapshot,'e'*32,None,consent)
                b.QUEUE_LIMITS=lambda:{'synthetic':1}
                with self.assertRaises(Exception):m.dispatch(b,None,'',backup,snapshot,'e'*32,None,consent)
            self.assertEqual([],list(Path(directory).iterdir()))

    def test_invalid_response_and_raw_private_error_are_not_echoed(self):
        for raw in (b'', m.fcgi_record(6, b'private-non-json-error') + m.fcgi_record(3, b'\0'*8), m.fcgi_record(3, b'\x01'+b'\0'*7)):
            with self.assertRaises(ValueError) as error: m.fcgi_response(Connection(raw))
            self.assertNotIn('private-non-json', str(error.exception))

    def test_protocol_parameter_bounds_and_record_limits(self):
        self.assertEqual(b'\x01\x01kv', m.fcgi_params({'k': 'v'}))
        self.assertEqual(struct.pack('!I',128|0x80000000),m.fcgi_length(128))
        with self.assertRaises(ValueError): m.fcgi_record(4, b'x'*65536)
        with self.assertRaises(ValueError): m.fcgi_params({'key':'x'*32768})

    def test_runtime_source_requires_native_sapi_and_mode(self):
        template=(fixture.b.BASE/'scripts/pilot/native_mail_runtime.php.txt').read_text()
        self.assertIn("PHP_SAPI === 'fpm-fcgi'",template)
        program=m.php_program(template,{'mode':'preview'})
        self.assertNotIn(b'__PARAMETERS__',program)
        with self.assertRaises(ValueError):m.php_program(template,{'mode':'unrestricted'})
