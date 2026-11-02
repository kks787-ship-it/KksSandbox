"""Disposable synthetic files/DB responses only. Never loads farm config or opens a network."""
import copy
import datetime as dt
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import signal
import stat
import tarfile
import tempfile
import time
import unittest
from unittest import mock

PATH=Path(__file__).resolve().parents[2]/'scripts/recovery/backup.py'
spec=importlib.util.spec_from_file_location('recovery_backup',PATH);b=importlib.util.module_from_spec(spec);spec.loader.exec_module(b)

class FakeDB:
    def __init__(self):self.reads=0;self.changed=False;self.fail=False;self.schema_change=False
    def guard(self,_):return {'bytes':32,'database':b.DATABASE,'home':b.URL,'siteurl':b.URL,'plugins':'synthetic','objects':0}
    def catalog(self):
        return {'columns':[{'table':b.PREFIX+n,'column':'synthetic_id','ordinal':1,'pk_order':1,'engine':'InnoDB',**({'note':'changed'} if self.schema_change and self.reads else {})} for n in sorted(b.NATIVE)]+[{'table':b.PREFIX+'options','column':'option_name','ordinal':2,'pk_order':None,'engine':'InnoDB'}],'indexes':[]}
    def query(self,_):
        self.reads+=1
        rows=[{'database':b.DATABASE,'prefix':b.PREFIX,'version':1,'captured_at':'synthetic'},
              {'table':'options','key_hash':'a'*64,'value_hash':('b' if self.changed and self.reads>1 else 'c')*64,'label':'users_can_register'}]
        return b'\n'.join(b.canonical(r) for r in rows)+b'\n'
    def versions(self):return {'client':'synthetic MariaDB','dump':'synthetic MariaDB'}
    def dump(self,out):
        out.write(b'-- SYNTHETIC SQL FIXTURE, NOT A FARM DUMP\n')
        if self.fail:raise b.Stop('synthetic private diagnostic must not leak')

class Fixture(unittest.TestCase):
    def setUp(self):
        self.tmp=tempfile.TemporaryDirectory();self.addCleanup(self.tmp.cleanup);self.base=Path(self.tmp.name)
        self.root=self.base/'site';self.root.mkdir();(self.root/'wp-content').mkdir();(self.root/'wp-content'/'fixture.txt').write_bytes(b'synthetic media')
        (self.root/'wp-config.php').write_bytes(b"<?php define('DB_NAME','wp_mdf4x'); define('DB_HOST','localhost'); define('WP_ENVIRONMENT_TYPE','development'); $table_prefix='TXGEh_';");(self.root/'index.php').write_bytes(b'synthetic root')
        self.parent=self.base/'private';self.parent.mkdir();self.protected=self.base/'synthetic-auth';self.protected.write_bytes(b'synthetic protected config')
        self.v={'db_secret':{'user':'synthetic','password':'synthetic','host':'localhost'},'mode':'capture','source_pin':b.IMPLEMENTATION,'actor_ref':'synthetic','approval_ref':'synthetic','run_token':'a'*32,'start':dt.datetime.fromtimestamp(time.time()-5,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),'end':dt.datetime.fromtimestamp(time.time()+120,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),'custody':'local-only','config_reviewed':True,'wp_config_sha256':b.sha((self.root/'wp-config.php').read_bytes()),'plugins_sha256':b.sha(b'synthetic'),'protected':[{'path':str(self.protected),'name':'auth.fixture','sha256':b.sha(self.protected.read_bytes()),'scope_verified':True}]}
        self.budget=b.Budget(time.time()+120,120);self.db=FakeDB()
        self.reserve=mock.patch.object(b,'free_bytes',return_value=100*1024**3);self.reserve.start();self.addCleanup(self.reserve.stop)
    def capture(self):return b.capture(self.v,self.db,self.budget,self.root,self.parent)
    def bundle(self):return self.parent/('csf-gate-b-backup-'+self.v['run_token'])
    def test_complete_synthetic_workflow_and_manifest(self):
        r=self.capture();self.assertEqual('complete',r['state']);self.assertTrue(b.verify_manifest(self.bundle(),r['manifest_sha256']))
        self.assertFalse(r['offserver_verified'] or r['restore_verified']);self.assertEqual(3,r['files']);self.assertEqual(0o700,stat.S_IMODE(self.bundle().stat().st_mode))
        for p in self.bundle().iterdir():self.assertEqual(0o600,stat.S_IMODE(p.stat().st_mode))
        self.assertFalse((self.bundle()/'dump.sql').exists())
        self.assertTrue(b.verify_receipt(self.bundle(),b.sha((self.bundle()/'receipt.json').read_bytes())))
    def test_drift_is_retained_not_attributed_or_passed(self):
        self.db.changed=True;r=self.capture();self.assertEqual('review-required',r['state']);self.assertEqual(1,r['drift_counts']['critical'])
        review=json.loads((self.bundle()/'drift-review.json').read_text());self.assertEqual('unknown',review['attribution']);self.assertEqual(1,len(review['changes']))
        with self.assertRaises(b.Stop):b.verify_receipt(self.bundle(),b.sha((self.bundle()/'receipt.json').read_bytes()))
    def test_plugin_guard_drift_cannot_be_lost_between_snapshots(self):
        original=self.db.guard
        def guard(expected):
            value=original(expected)
            if expected is None:value['plugins']='synthetic changed plugins'
            return value
        self.db.guard=guard;result=self.capture()
        self.assertEqual('review-required',result['state']);self.assertEqual(1,result['drift_counts']['critical'])
    def test_schema_drift_is_review_required(self):
        self.db.schema_change=True;r=self.capture();self.assertEqual('review-required',r['state']);self.assertTrue(r['schema_changed'])
    def test_incomplete_cleanup_and_sanitized_failure(self):
        self.db.fail=True;r=self.capture();self.assertEqual('incomplete',r['state']);self.assertNotIn('private diagnostic',json.dumps(r));self.assertFalse((self.bundle()/'dump.sql').exists());self.assertFalse((self.bundle()/'SHA256SUMS').exists())
    def test_no_overwrite_or_retry_same_bundle(self):
        self.capture()
        with self.assertRaises(b.Stop):self.capture()
    def test_source_symlink_rejected(self):
        (self.root/'outside').symlink_to(self.protected);self.assertEqual('incomplete',self.capture()['state'])
    def test_hardlink_rejected(self):
        os.link(self.protected,self.root/'link');self.assertEqual('incomplete',self.capture()['state'])
    def test_changed_config_hash_rejected(self):
        self.v['wp_config_sha256']='0'*64;self.assertEqual('incomplete',self.capture()['state'])
    def test_unapproved_protected_hash_rejected(self):
        self.v['protected'][0]['sha256']='0'*64;self.assertEqual('incomplete',self.capture()['state'])
    def test_low_disk_stops_before_dump(self):
        with mock.patch.object(b,'free_bytes',return_value=0),mock.patch.object(self.db,'dump') as dump:
            self.assertEqual('incomplete',self.capture()['state']);dump.assert_not_called()
    def test_disk_reserve_lost_mid_workflow_stops_and_closes_spool(self):
        original=self.db.dump
        def dump(out):
            original(out);b.free_bytes.return_value=0
        self.db.dump=dump;result=self.capture()
        self.assertEqual('incomplete',result['state']);self.assertFalse((self.bundle()/'dump.sql').exists());self.assertFalse((self.bundle()/'SHA256SUMS').exists())
    def test_expired_budget_prevents_directory(self):
        self.budget=b.Budget(time.time()-1)
        with self.assertRaises(b.Stop):self.capture()
        self.assertFalse(self.bundle().exists())
    def test_changed_source_during_dump_rejected(self):
        def mutate(out):out.write(b'synthetic');(self.root/'index.php').write_bytes(b'edited')
        self.db.dump=mutate;self.assertEqual('incomplete',self.capture()['state'])
    def test_inspection_is_no_write_no_dump_no_rows(self):
        before=b.inventory(self.root,self.budget)
        with mock.patch.object(self.db,'dump') as dump:
            r=b.inspect_only(self.v,self.db,self.budget,self.root,self.parent);dump.assert_not_called()
        self.assertEqual('prerequisites-pass',r['state']);self.assertEqual(0,self.db.reads);self.assertEqual([],list(self.parent.iterdir()));self.assertEqual(before,b.inventory(self.root,self.budget))
    def test_manifest_tamper_missing_duplicate_traversal_rejected(self):
        r=self.capture();p=self.bundle()/'SHA256SUMS';original=p.read_bytes()
        for mutation in (original+original.splitlines()[0]+b'\n',original.replace(b'  target.json',b'  ../target.json'),original.split(b'\n',1)[1]):
            p.write_bytes(mutation)
            with self.assertRaises(b.Stop):b.verify_manifest(self.bundle(),b.sha(mutation))
        p.write_bytes(original);(self.bundle()/'target.json').write_text('tampered')
        with self.assertRaises(b.Stop):b.verify_manifest(self.bundle(),r['manifest_sha256'])
    def test_binding_no_defaults_for_owner_decisions(self):
        b.bindings(self.v,'capture')
        for key,value in [('source_pin','floating'),('run_token','../escape'),('custody','guess'),('config_reviewed',False),('mode','inspect'),('start','2099-01-01T00:00:00Z')]:
            v=copy.deepcopy(self.v);v[key]=value
            with self.assertRaises(b.Stop):b.bindings(v,'capture')
    def test_duplicate_protected_names_paths_blocked(self):
        self.v['protected'].append(copy.deepcopy(self.v['protected'][0]))
        with self.assertRaises(b.Stop):b.bindings(self.v,'capture')
    def test_row_duplicate_and_unknown_field_blocked(self):
        catalog=self.db.catalog();raw=self.db.query('');lines=raw.splitlines();
        with self.assertRaises(b.Stop):b.parse_rows(raw+lines[-1]+b'\n',catalog)
        row=json.loads(lines[-1]);row['severity']='ignore'
        with self.assertRaises(b.Stop):b.parse_rows(lines[0]+b'\n'+b.canonical(row),catalog)
    def test_config_comments_quoted_defines_and_wrong_target_fail_closed(self):
        original=(self.root/'wp-config.php').read_bytes()
        b.validate_config(original)
        for data in (original.replace(b'wp_mdf4x',b'wrong_database'),original.replace(b'TXGEh_',b'wrong_'),original.replace(b'development',b'production'),original.replace(b"define('DB_NAME','wp_mdf4x');",b"/* define('DB_NAME','wp_mdf4x'); */"),original.replace(b"define('DB_NAME','wp_mdf4x');",b"if (false) define('DB_NAME','wp_mdf4x');"),original+b" define('DB_NAME','wp_mdf4x');"):
            with self.assertRaises(b.Stop):b.validate_config(data)
    def test_empty_directories_preserved(self):
        (self.root/'wp-content'/'empty').mkdir();self.assertEqual('complete',self.capture()['state'])
        with tarfile.open(self.bundle()/'wp-content.tar.gz') as archive:self.assertIn('wp-content/empty',archive.getnames())
    def test_inspection_can_establish_new_hashes_without_authorizing_capture(self):
        value=copy.deepcopy(self.v);value['mode']='inspect';value['wp_config_sha256']=None;value['plugins_sha256']=None;value['protected'][0]['sha256']=None
        b.bindings(value,'inspect');result=b.inspect_only(value,self.db,self.budget,self.root,self.parent)
        self.assertEqual(b.sha((self.root/'wp-config.php').read_bytes()),result['observed_wp_config_sha256'])
        value['mode']='capture'
        with self.assertRaises(b.Stop):b.bindings(value,'capture')
    def test_inspection_denied_if_protected_hash_changed(self):
        self.protected.write_text('changed')
        with self.assertRaises(b.Stop):b.inspect_only(self.v,self.db,self.budget,self.root,self.parent)
        self.assertEqual([],list(self.parent.iterdir()))
    def test_incomplete_receipt_never_verifies(self):
        self.db.fail=True;self.capture()
        with self.assertRaises(b.Stop):b.verify_receipt(self.bundle(),b.sha((self.bundle()/'receipt.json').read_bytes()))
    def test_volatile_candidate_not_automatically_complete(self):
        a=b.parse_rows(self.db.query(''),self.db.catalog());c=copy.deepcopy(a)
        a['rows'][0]['label']=c['rows'][0]['label']='_transient_doing_cron';c['rows'][0]['value_hash']='d'*64
        result=b.drift(a,c,json.loads((b.BASE/'docs/pilot/baseline-contract.json').read_text()))
        self.assertTrue(result['review_required']);self.assertEqual(1,result['counts']['volatile_candidate'])
    def test_byte_and_file_limits(self):
        with self.assertRaises(b.Stop):b.inventory(self.root,self.budget,max_bytes=1)
        with mock.patch.object(b,'MAX_FILES',1):
            with self.assertRaises(b.Stop):b.inventory(self.root,self.budget)
    def test_window_over_30_minutes_and_missing_bindings_blocked(self):
        value=copy.deepcopy(self.v);value['end']='2099-01-01T00:00:00Z'
        with self.assertRaises(b.Stop):b.bindings(value,'capture')
        del value['protected']
        with self.assertRaises(b.Stop):b.bindings(value,'capture')
    def test_unknown_binding_and_production_path_rejected(self):
        value=copy.deepcopy(self.v);value['root']='/production'
        with self.assertRaises(b.Stop):b.bindings(value,'capture')
        value=copy.deepcopy(self.v);value['protected'][0]['path']='/var/www/httpdocs/wp-config.php'
        with self.assertRaises(b.Stop):b.bindings(value,'capture')
    def test_no_secret_in_receipt(self):
        r=self.capture();data=json.dumps(r)
        self.assertNotIn('db_secret',data);self.assertNotIn(str(self.protected),data);self.assertNotIn('auth.fixture',data);self.assertNotIn('synthetic',data)
    def test_source_unreadable_directory_aborts(self):
        with mock.patch.object(b.os,'walk',side_effect=lambda *a,**k:k['onerror'](PermissionError())):
            with self.assertRaises(b.Stop):b.inventory(self.root,self.budget)
    def test_public_receipt_extra_field_rejected(self):
        self.capture();path=self.bundle()/'receipt.json';value=json.loads(path.read_text());value['unexpected']='synthetic private value';path.write_bytes(b.canonical(value)+b'\n')
        with self.assertRaises(b.Stop):b.verify_receipt(self.bundle(),b.sha(path.read_bytes()))
    def test_cli_uses_anonymous_secret_descriptor_and_clean_environment(self):
        tool=self.base/'fake-client';tool.write_text('#!/usr/bin/python3\nimport os,sys\nassert sys.argv[1].startswith("--defaults-file=/proc/self/fd/")\nassert "HOME" not in os.environ and "MYSQL_PWD" not in os.environ\np=sys.argv[1].split("=",1)[1]\nassert "synthetic-password" in open(p).read()\nassert "synthetic-password" not in " ".join(sys.argv)\nprint("synthetic-result")\n');tool.chmod(0o700)
        db=b.NativeDB({'user':'synthetic','password':'synthetic-password','host':'localhost'},self.budget,client=str(tool))
        with mock.patch.dict(os.environ,{'HOME':'hostile','MYSQL_PWD':'hostile'}):self.assertEqual(b'synthetic-result\n',db.query('SELECT synthetic'))
    def test_native_dump_kernel_output_limit(self):
        tool=self.base/'large-client';tool.write_text('#!/usr/bin/python3\nimport os\nos.write(1,b"x"*8192)\n');tool.chmod(0o700)
        db=b.NativeDB({'user':'synthetic','password':'synthetic','host':'localhost'},self.budget,dump=str(tool))
        with tempfile.TemporaryFile(dir=self.parent) as out,mock.patch.object(b,'MAX_DB',16):
            with self.assertRaises(b.Stop):db.dump(out)
            self.assertLessEqual(os.fstat(out.fileno()).st_size,32)
    def test_child_timeout_kills_and_reaps(self):
        tool=self.base/'slow-client';tool.write_text('#!/usr/bin/python3\nimport time\ntime.sleep(5)\n');tool.chmod(0o700)
        db=b.NativeDB({'user':'synthetic','password':'synthetic','host':'localhost'},b.Budget(time.time()+.1,.1),client=str(tool))
        start=time.monotonic()
        with self.assertRaises(b.Stop):db.query('SELECT synthetic')
        self.assertLess(time.monotonic()-start,2)

class ArchiveHeaders(unittest.TestCase):
    def setUp(self):self.tmp=tempfile.TemporaryDirectory();self.addCleanup(self.tmp.cleanup);self.path=Path(self.tmp.name)/'fixture.tar.gz'
    def make(self,entries,fmt=tarfile.PAX_FORMAT):
        with tarfile.open(self.path,'w:gz',format=fmt) as out:
            for name,kind,pax in entries:
                e=tarfile.TarInfo(name);e.type=kind;e.pax_headers=pax
                if kind in (tarfile.SYMTYPE,tarfile.LNKTYPE):e.linkname='synthetic-target'
                out.addfile(e)
    def rejected(self,entries,fmt=tarfile.PAX_FORMAT):
        self.make(entries,fmt)
        with self.assertRaises((b.Stop,tarfile.TarError,EOFError,OSError)):b.validate_archive(self.path)
    def test_regular_and_directory_pass(self):
        self.make([('folder',tarfile.DIRTYPE,{}),('folder/file',tarfile.REGTYPE,{})]);self.assertEqual(2,b.validate_archive(self.path)['entries'])
    def test_traversal_absolute_empty_components_controls(self):
        for name in ('../escape','a/../b','/etc/synthetic','a//b','a\nb','a\rb','a\tb','a\x7fb','a\\b'):
            with self.subTest(name=repr(name)):self.rejected([(name,tarfile.REGTYPE,{})])
    def test_effective_pax_names_rejected(self):
        for path in ('../../escape','/absolute','a\nb'):
            self.rejected([('benign',tarfile.REGTYPE,{'path':path})])
    def test_gnu_longname_traversal_rejected(self):
        self.rejected([('folder/'+'x'*120+'/../../escape',tarfile.REGTYPE,{})],tarfile.GNU_FORMAT)
    def test_normalized_duplicates(self):
        for names in (('a','./a'),('a/b','a/./b'),('a/','./a/')):
            self.rejected([(n,tarfile.REGTYPE,{}) for n in names])
    def test_links_and_special_types(self):
        for kind in (tarfile.SYMTYPE,tarfile.LNKTYPE,tarfile.FIFOTYPE,tarfile.CHRTYPE,tarfile.BLKTYPE,tarfile.GNUTYPE_SPARSE,b'Z'):
            self.rejected([('entry',kind,{})])
    def test_prefix_collisions_both_orders(self):
        for entries in ([('a',tarfile.REGTYPE,{}),('a/b',tarfile.REGTYPE,{})],[('a/b',tarfile.REGTYPE,{}),('a',tarfile.REGTYPE,{})]):self.rejected(entries)
    def test_unreviewed_extended_metadata(self):self.rejected([('file',tarfile.REGTYPE,{'GNU.sparse.name':'other'})])
    def test_scope_and_entry_limits(self):
        self.make([('file',tarfile.REGTYPE,{})])
        with self.assertRaises(b.Stop):b.validate_archive(self.path,allowed={'other'})
        with self.assertRaises(b.Stop):b.validate_archive(self.path,max_entries=0)
    def test_archived_bytes_must_match_inventory_not_just_header(self):
        with tarfile.open(self.path,'w:gz') as out:
            entry=tarfile.TarInfo('file');entry.size=4;out.addfile(entry,io.BytesIO(b'data'))
        with self.assertRaises(b.Stop):b.validate_archive(self.path,expected_files={'file':{'size':4,'sha256':'0'*64}})
    def test_oversized_extended_header_rejected_before_read(self):
        import gzip
        raw=tarfile.TarInfo('pax');raw.type=tarfile.XHDTYPE;raw.size=1024*1024+1
        self.path.write_bytes(gzip.compress(raw.tobuf()+b'\0'*1024))
        with self.assertRaises(b.Stop):b.validate_archive(self.path)
    def test_hidden_members_after_tar_end_and_missing_expected_member(self):
        import gzip
        self.make([('file',tarfile.REGTYPE,{})]);original=self.path.read_bytes()
        self.path.write_bytes(original+gzip.compress(tarfile.TarInfo('../hidden').tobuf()+b'\0'*1024))
        with self.assertRaises(b.Stop):b.validate_archive(self.path)
        self.path.write_bytes(original)
        with self.assertRaises(b.Stop):b.validate_archive(self.path,allowed={'file','missing'})
    def test_archive_byte_limit_and_corrupt_dump_gzip(self):
        with tarfile.open(self.path,'w:gz') as out:
            entry=tarfile.TarInfo('file');entry.size=4;out.addfile(entry,io.BytesIO(b'data'))
        with self.assertRaises(b.Stop):b.validate_archive(self.path,max_bytes=1)
        self.path.write_bytes(b'not gzip')
        with self.assertRaises((OSError,EOFError)):b.validate_gzip(self.path,b.Budget(time.time()+60))
    def test_truncated_crc_never_passes(self):
        self.make([('file',tarfile.REGTYPE,{})]);self.path.write_bytes(self.path.read_bytes()[:-7])
        with self.assertRaises((b.Stop,tarfile.TarError,EOFError,OSError)):b.validate_archive(self.path)


class PayloadBuild(unittest.TestCase):
    def test_deterministic_standalone_and_exact_command(self):
        path=b.BASE/'scripts/recovery/build.py';namespace={'__name__':'build_test','__file__':str(path)}
        exec(compile(path.read_text(),str(path),'exec'),namespace)
        for name,data in namespace['artifacts']().items():self.assertEqual(data,(b.BASE/name).read_bytes())
    def test_memory_inspection_invalid_binding_never_reads_source_or_writes(self):
        import subprocess
        payload=(b.BASE/'scripts/recovery/payload.py').read_text();command=(b.BASE/'scripts/recovery/inspect.command.sh').read_text()
        result=subprocess.run(['/bin/bash','-c',command],input=b'{}',env={'CSF_REVIEWED_PAYLOAD':payload},capture_output=True)
        self.assertEqual(2,result.returncode);self.assertEqual({'state':'blocked','details':'private review required'},json.loads(result.stdout));self.assertEqual(b'',result.stderr)
    def test_payload_tamper_cannot_reach_binding_or_target(self):
        import subprocess
        command=(b.BASE/'scripts/recovery/inspect.command.sh').read_text()
        result=subprocess.run(['/bin/bash','-c',command],input=b'{}',env={'CSF_REVIEWED_PAYLOAD':'tampered synthetic payload'},capture_output=True)
        self.assertNotEqual(0,result.returncode);self.assertEqual(b'',result.stdout)


if __name__=='__main__':unittest.main()
