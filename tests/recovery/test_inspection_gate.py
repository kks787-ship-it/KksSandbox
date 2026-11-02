"""Final inspection-only authorization gate; mock bindings and disposable local files only."""
import copy
import io
import json
import os
from pathlib import Path
import subprocess
import sys
import unittest
from unittest import mock

import test_backup as fixture
b=fixture.b

class InspectionGate(unittest.TestCase):
    setUp=fixture.Fixture.setUp

    def invoke(self,value,inspect_effect=None):
        original=b.inspect_only
        def scoped(v,db,budget):
            return original(v,db,budget,self.root,self.parent)
        with mock.patch.object(sys,'argv',['backup.py','inspect']), \
             mock.patch.object(sys,'stdin',io.TextIOWrapper(io.BytesIO(b.canonical(value)))), \
             mock.patch.object(sys,'stdout',io.StringIO()) as stdout, \
             mock.patch.object(b,'NativeDB',return_value=self.db), \
             mock.patch.object(b,'inspect_only',side_effect=inspect_effect or scoped), \
             mock.patch.object(b,'capture',side_effect=AssertionError('capture forbidden')) as capture, \
             mock.patch.object(b,'private_write',side_effect=AssertionError('file write forbidden')), \
             mock.patch.object(b.signal,'signal'),mock.patch.object(b.signal,'setitimer'):
            code=b.main();capture.assert_not_called()
        self.assertEqual([],list(self.parent.iterdir()))
        return code,stdout.getvalue()

    def inspection_binding(self):
        value=copy.deepcopy(self.v);value['mode']='inspect';return value

    def test_fixed_inspect_mode_cannot_dispatch_capture_and_cannot_write(self):
        before=b.inventory(self.root,self.budget)
        with mock.patch.object(self.db,'dump',side_effect=AssertionError('dump forbidden')) as dump:
            code,text=self.invoke(self.inspection_binding());dump.assert_not_called()
        self.assertEqual(0,code);self.assertFalse(json.loads(text)['capture_authorized'])
        self.assertEqual(0,self.db.reads);self.assertEqual(before,b.inventory(self.root,self.budget))

    def test_capture_mode_in_private_json_is_rejected_before_dispatch(self):
        code,text=self.invoke(self.v);self.assertEqual(2,code);self.assertEqual('blocked',json.loads(text)['state'])

    def test_all_missing_bindings_fail_closed_without_private_diagnostics(self):
        for key in self.v:
            value=self.inspection_binding();del value[key]
            with self.subTest(key=key):
                code,text=self.invoke(value);self.assertEqual(2,code)
                self.assertEqual({'state':'blocked','details':'private review required'},json.loads(text))

    def test_missing_protected_config_is_blocked(self):
        self.protected.unlink();code,text=self.invoke(self.inspection_binding())
        self.assertEqual(2,code);self.assertNotIn(str(self.protected),text)

    def test_inaccessible_protected_config_is_blocked(self):
        original=b.read_regular
        def deny(path,*args):
            if Path(path)==self.protected:raise PermissionError('synthetic private path')
            return original(path,*args)
        with mock.patch.object(b,'read_regular',side_effect=deny):code,text=self.invoke(self.inspection_binding())
        self.assertEqual(2,code);self.assertNotIn('synthetic private',text)

    def test_timeout_at_inspection_is_sanitized(self):
        code,text=self.invoke(self.inspection_binding(),lambda *_: (_ for _ in ()).throw(b.Stop('synthetic timeout')))
        self.assertEqual(2,code);self.assertNotIn('timeout',text)

    def test_unexpected_target_identity_is_blocked(self):
        for key,value in (('database','synthetic_wrong'),('home','https://synthetic.invalid'),('siteurl','https://synthetic.invalid')):
            native=b.NativeDB(self.v['db_secret'],self.budget);row=self.db.guard(None);row[key]=value
            with mock.patch.object(native,'query',return_value=b.canonical(row)):
                with self.assertRaises(b.Stop):native.guard(self.v['plugins_sha256'])

    def test_incompatible_client_and_unsupported_flags_fail_closed(self):
        executable=self.base/'incompatible';executable.write_text('#!/usr/bin/python3\nimport sys\nsys.stderr.write("synthetic private error")\nsys.exit(1)\n');executable.chmod(0o700)
        native=b.NativeDB(self.v['db_secret'],self.budget,client=str(executable))
        with self.assertRaises(b.Stop):native.versions()
        executable.write_text('#!/usr/bin/python3\nprint("unsupported client")\n')
        with self.assertRaises(b.Stop):native.versions()

    def test_native_inspection_sql_has_no_account_enumeration_or_writes(self):
        native=b.NativeDB(self.v['db_secret'],self.budget);sql=[];catalog=self.db.catalog()
        def query(statement):
            sql.append(statement)
            if 'DATABASE(),' in statement:return b.canonical(self.db.guard(None))
            if 'information_schema.COLUMNS' in statement:return b'\n'.join(b.canonical(r) for r in catalog['columns'])
            if 'information_schema.STATISTICS WHERE' in statement:return b''
            return b''
        with mock.patch.object(native,'query',side_effect=query):native.guard(self.v['plugins_sha256']);native.catalog()
        self.assertEqual(4,len(sql))
        for statement in sql:
            self.assertTrue(statement.startswith('SELECT '))
            for forbidden in ('FROM TXGEh_users','FROM TXGEh_usermeta','INSERT ','UPDATE ','DELETE ','GRANT ','CREATE ','INTO OUTFILE','LOAD_FILE','SLEEP('):self.assertNotIn(forbidden,statement)

    def test_reviewed_command_cannot_accept_appended_capture_argument(self):
        command=(b.BASE/'scripts/recovery/inspect.command.sh').read_text()
        payload=(b.BASE/'scripts/recovery/payload.py').read_text()
        result=subprocess.run(['/bin/bash','-c',command,'ignored','capture'],input=b.canonical(self.v),env={'CSF_REVIEWED_PAYLOAD':payload},capture_output=True)
        self.assertEqual(2,result.returncode);self.assertEqual('blocked',json.loads(result.stdout)['state']);self.assertEqual(b'',result.stderr)

if __name__=='__main__':unittest.main()
