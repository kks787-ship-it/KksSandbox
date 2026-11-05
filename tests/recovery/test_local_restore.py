"""Synthetic restore tests. No farm inputs or network access."""
import datetime as dt
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import time
import types
import unittest
from unittest import mock
import test_backup as fixture
b=fixture.b
spec=importlib.util.spec_from_file_location('isolated_restore',b.BASE/'scripts/recovery/restore_local.py');r=importlib.util.module_from_spec(spec);spec.loader.exec_module(r)
sys.path.insert(0,str(b.BASE/'scripts/recovery'))
import build_local_restore as builder

class Restore(unittest.TestCase):
    def setUp(self):self.binding={'bundle_id':'csf-gate-b-backup-'+'a'*32,'manifest_sha256':'b'*64,'receipt_sha256':'c'*64,'recovery_token':'d'*32}
    def blocked(self,fn,code):
        with self.assertRaises(r.Failure) as error:fn()
        self.assertEqual(code,error.exception.code)
    def params(self):
        stamp=lambda v:dt.datetime.fromtimestamp(v,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
        return {'actor_ref':'synthetic','approval_ref':'synthetic','authorization':r.AUTHORIZATION,'login_test':'clone-admin','start':stamp(time.time()-10),'end':stamp(time.time()+120)}
    def test_exact_private_binding(self):
        r.binding_check(b,self.binding)
        for key in self.binding:
            wrong=dict(self.binding);del wrong[key];self.blocked(lambda:r.binding_check(b,wrong),'INPUT_BLOCKED')
    def test_binding_traversal_bad_hash_and_extra_target(self):
        for key,value in (('bundle_id','../source'),('recovery_token','../dest'),('manifest_sha256','not-hash'),('receipt_sha256','0'*63),('root','/production')):
            wrong=dict(self.binding);wrong[key]=value;self.blocked(lambda:r.binding_check(b,wrong),'INPUT_BLOCKED')
    def test_explicit_consent_and_active_window(self):
        r.params_check(b,self.params())
        for key,value in (('authorization','capture'),('login_test','auto'),('actor_ref','synthetic\n'),('root','/production'),('end','2000-01-01T00:00:00Z')):
            p=self.params();p[key]=value;self.blocked(lambda:r.params_check(b,p),'INPUT_BLOCKED')
    def test_thirty_minute_limit(self):
        p=self.params();p['end']=dt.datetime.fromtimestamp(time.time()+1900,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ');self.blocked(lambda:r.params_check(b,p),'INPUT_BLOCKED')
    def test_destination_no_reuse_or_alias(self):
        with tempfile.TemporaryDirectory() as tmp:
            parent=Path(tmp);source=parent/self.binding['bundle_id'];source.mkdir();self.assertEqual(source,r.selected_paths(b,self.binding,parent)[0])
            dest=parent/('csf-gate-b-recovery-'+self.binding['recovery_token']);dest.symlink_to(parent/'absent');self.blocked(lambda:r.selected_paths(b,self.binding,parent),'SOURCE_BINDING_BLOCKED')
            dest.unlink();source.rmdir();source.symlink_to(parent);self.blocked(lambda:r.selected_paths(b,self.binding,parent),'SOURCE_BINDING_BLOCKED')
    def test_manifest_receipt_and_exact_target(self):
        target={'root':str(b.ROOT),'database':b.DATABASE,'prefix':b.PREFIX,'url':b.URL}
        with tempfile.TemporaryDirectory() as tmp:
            source=Path(tmp)
            for name in set(b.MANIFEST_NAMES)|{'SHA256SUMS','receipt.json'}:(source/name).write_text('{}')
            (source/'target.json').write_text(json.dumps(target));self.binding['manifest_sha256']=b.sha((source/'SHA256SUMS').read_bytes())
            with mock.patch.object(b,'verify_receipt',return_value=True):
                self.assertEqual(target,r.verify_input(b,source,self.binding));target['database']='unexpected';(source/'target.json').write_text(json.dumps(target));self.blocked(lambda:r.verify_input(b,source,self.binding),'SOURCE_BINDING_BLOCKED')
                target['database']=b.DATABASE;(source/'target.json').write_text(json.dumps(target));(source/'unapproved').write_text('');self.blocked(lambda:r.verify_input(b,source,self.binding),'SOURCE_BINDING_BLOCKED')
    def dump(self,extra=''):return ''.join('CREATE TABLE `'+b.PREFIX+n+'` (`id` int);INSERT INTO `'+b.PREFIX+n+'` VALUES (1),(2);\n' for n in sorted(b.NATIVE))+extra
    def test_scoped_sql_full_table_set_and_counts(self):
        counts=r.validate_sql(b,io.StringIO(self.dump()));self.assertEqual(set(b.NATIVE),set(counts));self.assertEqual({2},set(counts.values()))
    def test_quoted_sql_is_opaque(self):
        sql=self.dump("INSERT INTO TXGEh_options VALUES (';DROP DATABASE bad; OUTFILE'),('🐴');");self.assertEqual(4,r.validate_sql(b,io.StringIO(sql))['options'])
    def test_executable_comment_cannot_hide_global_or_outfile(self):
        for sql in ('/*!50000 DROP DATABASE bad */;','/*!50000 SET GLOBAL event_scheduler=ON */;','/*!50000 SELECT 1 INTO OUTFILE \'/tmp/x\' */;'):
            self.blocked(lambda:r.validate_sql(b,io.StringIO(self.dump(sql))),'SQL_STRUCTURE_BLOCKED')
    def test_mariadb_client_marker_and_session_set(self):
        sql='/*M!999999\\- enable the sandbox mode */\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;'+self.dump();self.assertEqual(12,len(r.validate_sql(b,io.StringIO(sql))))
    def test_foreign_tables_databases_and_client_commands(self):
        for sql in ('USE other;','CREATE TABLE other_table(id int);','INSERT INTO other.TXGEh_options VALUES(1);','GRANT ALL ON *.* TO x;','SOURCE /tmp/x;','DELIMITER $$','\\! touch /tmp/x;','INSTALL SONAME \'bad\';','LOAD DATA INFILE \'/x\' INTO TABLE TXGEh_options;'):
            self.blocked(lambda:r.validate_sql(b,io.StringIO(self.dump(sql))),'SQL_STRUCTURE_BLOCKED')
    def test_truncated_sql_or_missing_tables(self):
        for sql in ("INSERT INTO TXGEh_options VALUES ('unterminated);",'/* truncated','CREATE TABLE TXGEh_options(id int);',self.dump()+"INSERT INTO TXGEh_options VALUES(1"):
            self.blocked(lambda:r.validate_sql(b,io.StringIO(sql)),'SQL_STRUCTURE_BLOCKED')
    def test_comments_and_setup_flag_are_not_active_multisite(self):self.assertTrue(r.security_config_safe(b"<?php /* MULTISITE TWO_FACTOR */ // FORCE_SSL_ADMIN\ndefine('WP_ALLOW_MULTISITE',true);"))
    def test_security_setting_string_or_uncertainty_blocks(self):
        for data in (b"<?php define('MULTISITE',true);",b"<?php $x='TWO_FACTOR';",b'<?php /* truncated',b"<?php 'unclosed",b"<?php $x='https://host/MULTISITE';"):
            self.blocked(lambda:r.require(r.security_config_safe(data),'SOURCE_SECURITY_BLOCKED'),'SOURCE_SECURITY_BLOCKED')
    def test_chroot_nonroot_no_capabilities(self):
        rt=object.__new__(r.Runtime);rt.jail=Path('/synthetic/jail');rt.mysql=types.SimpleNamespace(pw_uid=101,pw_gid=102);rt.web=types.SimpleNamespace(pw_uid=103,pw_gid=104)
        for web in (False,True):
            prefix=rt.prefix(web);self.assertEqual(['/usr/sbin/chroot','/synthetic/jail','/usr/bin/setpriv'],prefix[:3])
            for flag in ('--clear-groups','--no-new-privs','--bounding-set=-all','--inh-caps=-all','--ambient-caps=-all'):self.assertIn(flag,prefix)
            self.assertNotIn('--reuid=0',prefix)
    def test_namespace_absence_or_reuse_stops_before_mount(self):
        rt=types.SimpleNamespace(host=mock.Mock())
        with mock.patch.object(r.os,'getpid',return_value=3):self.blocked(lambda:r.setup_jail(rt,Path('/source'),{}),'ISOLATION_BLOCKED')
        with mock.patch.object(r.os,'getpid',return_value=1),mock.patch.object(r.os,'readlink',return_value='same'):self.blocked(lambda:r.setup_jail(rt,Path('/source'),dict.fromkeys(('mnt','net','pid'),'same')),'ISOLATION_BLOCKED')
        rt.host.assert_not_called()
    def test_external_requests_rejected_before_io(self):
        opener=mock.Mock()
        for url in ('https://example.invalid/','http://127.0.0.1:80/','http://user@127.0.0.1:8899/','//example.invalid/'):self.blocked(lambda:r.request(opener,url),'PAGE_TEST_BLOCKED')
        opener.open.assert_not_called()
    def test_response_bound(self):
        reply=mock.MagicMock();reply.__enter__.return_value=reply;reply.read.return_value=b'x'*(4*1024*1024+1);opener=mock.Mock();opener.open.return_value=reply;self.blocked(lambda:r.request(opener,'/'),'PAGE_TEST_BLOCKED')
    def test_skip_does_not_authenticate(self):
        rt=mock.Mock()
        with mock.patch.object(r,'http_client',return_value=(mock.Mock(),[])),mock.patch.object(r,'request',return_value=(200,r.ORIGIN,b'<html><title>Synthetic</title>')):self.assertFalse(r.functional_tests(rt,[],'skip'))
        rt.sql.assert_not_called()
    def worker(self,fail=None,login=True,cleanup=True,catalog=None):
        rt=mock.Mock();rt.work=Path('/synthetic');rt.jail=Path('/synthetic/jail');rt.stop.return_value=cleanup
        original=Path.read_text
        source_catalog=catalog or fixture.FakeDB().catalog()
        patches=[mock.patch.object(Path,'read_text',lambda path,*a,**kw:json.dumps(source_catalog) if str(path)=='/source/before.catalog.json' else original(path,*a,**kw)),mock.patch.object(r,'params_check'),mock.patch.object(r,'selected_paths_for_worker',return_value=(Path('/source'),Path('/synthetic'))),mock.patch.object(r,'Runtime',return_value=rt),mock.patch.object(r,'setup_jail',side_effect=fail),mock.patch.object(r,'verify_input'),mock.patch.object(r,'unpack',return_value=b'synthetic'),mock.patch.object(r,'import_database'),mock.patch.object(r,'prepare_wordpress',return_value=[]),mock.patch.object(r,'functional_tests',return_value=login),mock.patch.object(b,'write_json')]
        with __import__('contextlib').ExitStack() as stack:
            for p in patches:stack.enter_context(p)
            result=r.worker(b,self.binding,self.params(),{})
        rt.stop.assert_called_once();return result
    def test_worker_success(self):self.assertTrue(self.worker()['restore_verified'])
    def test_worker_skip_partial(self):
        result=self.worker(login=False);self.assertEqual('partial',result['state']);self.assertFalse(result['restore_verified'])
    def test_worker_failure_always_cleans(self):
        result=self.worker(fail=r.Failure('ISOLATION_BLOCKED'));self.assertEqual('ISOLATION_BLOCKED',result['code']);self.assertTrue(result['cleanup_verified']);self.assertFalse(result['restore_verified'])
    def test_exception_detail_redacted(self):
        result=self.worker(fail=RuntimeError('synthetic private details'));self.assertEqual('RESTORE_UNCLASSIFIED',result['code']);self.assertNotIn('private',json.dumps(result))
    def test_cleanup_failure_revokes_pass(self):
        result=self.worker(cleanup=False);self.assertEqual('CLEANUP_BLOCKED',result['code']);self.assertFalse(result['restore_verified'])
    def test_timeout_is_failure_and_cleans(self):self.assertEqual('TIME_LIMIT_REACHED',self.worker(fail=r.Failure('TIME_LIMIT_REACHED'))['code'])
    def test_reproducible_public_code_and_bounded_command(self):
        payload=builder.public_payload();self.assertEqual(payload,builder.public_payload());cmd=builder.command(payload,{});self.assertEqual(cmd,builder.command(payload,{}));self.assertIn(b.sha(payload).encode(),cmd);self.assertIn(b'1770s',cmd);self.assertIn(b'--kill-after=30s',cmd);subprocess.run(['/bin/bash','-n'],input=cmd,check=True)
    def test_public_template_fails_closed(self):
        result=subprocess.run(['/bin/bash','-c',builder.command(builder.public_payload(),{}).decode()],input=b'{}',capture_output=True,timeout=5);self.assertEqual(2,result.returncode);self.assertEqual({'state':'blocked','code':'INPUT_BLOCKED'},json.loads(result.stdout))
    def test_release_hashes(self):
        for line in (b.BASE/'scripts/recovery/LOCAL-RESTORE.sha256').read_text().splitlines():
            digest,name=line.split('  ');self.assertEqual(digest,b.sha((b.BASE/name).read_bytes()))
    def test_private_builder_preserves_exact_binding_and_permissions(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);record=root/'record';out=root/'command';record.write_text(json.dumps({'status':'succeeded','receipt':{'code':'LOCAL_CAPTURE_VERIFIED',**{k:v for k,v in self.binding.items() if k!='recovery_token'}}}));record.chmod(0o600);builder.private_build(record,out);one=out.with_suffix('.binding.json').read_bytes();builder.private_build(record,out);self.assertEqual(one,out.with_suffix('.binding.json').read_bytes())
            for file in root.glob('command.*'):self.assertEqual(0o600,file.stat().st_mode&0o777)
    def test_private_input_must_not_be_world_readable(self):
        with tempfile.TemporaryDirectory() as tmp:
            record=Path(tmp)/'input';record.write_text('{}');record.chmod(0o644)
            with self.assertRaises(ValueError):builder.private_build(record,Path(tmp)/'out')
    def test_private_output_alias_cannot_overwrite_another_file(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);record=root/'record';record.write_text(json.dumps({'status':'succeeded','receipt':{'code':'LOCAL_CAPTURE_VERIFIED',**{k:v for k,v in self.binding.items() if k!='recovery_token'}}}));record.chmod(0o600)
            victim=root/'unrelated';victim.write_text('preserve');(root/'out.command.sh').symlink_to(victim)
            with self.assertRaises(OSError):builder.private_build(record,root/'out')
            self.assertEqual('preserve',victim.read_text())
    def test_own_daemon_cleanup_escalates_only_recorded_pid(self):
        with tempfile.TemporaryDirectory() as tmp:
            rt=object.__new__(r.Runtime);rt.work=Path(tmp);rt.root_password='synthetic';rt.app_password='synthetic'
            proc=mock.Mock();proc.poll.side_effect=[None,0];proc.wait.side_effect=[subprocess.TimeoutExpired('synthetic',8),0];log=io.BytesIO();rt.children=[(proc,log)]
            self.assertTrue(rt.stop());proc.terminate.assert_called_once();proc.kill.assert_called_once();self.assertTrue(log.closed);self.assertEqual('',rt.root_password);self.assertEqual([],rt.children)
    def test_unknown_error_cannot_export_message(self):self.assertEqual('RESTORE_UNCLASSIFIED',r.Failure('synthetic private message').code)
    def test_no_external_transfer_live_services_or_global_kill(self):
        source=(b.BASE/'scripts/recovery/restore_local.py').read_text()
        for word in ('systemctl','plesk bin','curl ','scp ','rsync ','--network=host','--privileged','pkill','killall'):self.assertNotIn(word,source)
        self.assertIn('pidfd_send_signal',source);self.assertIn('--kill-child=TERM',source)

if __name__=='__main__':unittest.main()
