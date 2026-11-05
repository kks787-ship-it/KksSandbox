"""Synthetic tests of recovery-qualified inactive-file staging; never accesses a farm."""
import hashlib
import importlib.util
import json
from pathlib import Path
import tempfile
import types
import unittest
from unittest import mock
import test_backup as fixture
b=fixture.b
spec=importlib.util.spec_from_file_location('inactive_staging',b.BASE/'scripts/pilot/stage_scheduling.py');s=importlib.util.module_from_spec(spec);spec.loader.exec_module(s)

class Staging(unittest.TestCase):
    def entries(self):
        paths=['csf-scheduling.php']+['includes/test-'+str(i)+'.php' for i in range(19)]
        return [{'path':s.BASE+p,'text':'<?php // synthetic '+p,'sha256':hashlib.sha256(('<?php // synthetic '+p).encode()).hexdigest()} for p in paths]
    def test_exact_twenty_files(self):self.assertEqual(20,len(s.source_files(self.entries())))
    def test_reject_path_override_duplicate_and_changed_bytes(self):
        for key,value in (('path',s.BASE+'../outside.php'),('path','other/plugin.php'),('sha256','0'*64),('text','tampered')):
            rows=self.entries();rows[0][key]=value
            with self.assertRaises(s.Blocked):s.source_files(rows)
        rows=self.entries();rows[-1]=rows[0]
        with self.assertRaises(s.Blocked):s.source_files(rows)
    def test_missing_or_extra_files_block(self):
        for rows in (self.entries()[:-1],self.entries()+self.entries()[:1]):
            with self.assertRaises(s.Blocked):s.source_files(rows)
    def test_atomic_publish_never_overwrites(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);a=root/'new';a.mkdir();(a/'new').write_text('new');target=root/'old';target.mkdir();(target/'keep').write_text('owner')
            with self.assertRaises(s.Blocked):s.publish(a,target)
            self.assertEqual('owner',(target/'keep').read_text());self.assertTrue(a.exists())
    def test_verify_tree_rejects_extra_or_changed_file(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);entries=s.source_files(self.entries())
            for name,row in entries.items():p=root/name;p.parent.mkdir(exist_ok=True);p.write_text(row['text'])
            s.verify_tree(root,entries);(root/'unreviewed').write_text('extra')
            with self.assertRaises(s.Blocked):s.verify_tree(root,entries)
    def exercise(self,mode,php_ok=True):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);(root/'wp-config.php').write_text('synthetic config');private=root/'private';private.mkdir();parent=root/'plugins';parent.mkdir();target=parent/'csf-scheduling'
            db=mock.Mock();db.guard.return_value={'plugins':'synthetic'}
            with mock.patch.object(s,'recovery',return_value=private/'backup'),mock.patch.object(s,'target_guard',return_value=(db,db.guard.return_value,parent,target,b.sha(b'synthetic config'))),mock.patch.object(b,'ROOT',root),mock.patch.object(s.subprocess,'run',return_value=types.SimpleNamespace(returncode=0 if php_ok else 1)):
                if not php_ok:
                    with self.assertRaises(s.Blocked):s.run(b,{},self.entries(),mode)
                    self.assertFalse(target.exists());return
                result=s.run(b,{},self.entries(),mode)
            self.assertEqual(mode=='stage-inactive',target.exists())
            if target.exists():s.verify_tree(target,s.source_files(self.entries()))
            self.assertFalse(list(private.iterdir()))
            return result
    def test_preflight_has_no_writes(self):self.assertFalse(self.exercise('preflight')['writes'])
    def test_full_stage_inactive_only(self):
        result=self.exercise('stage-inactive');self.assertFalse(result['activation_performed']);self.assertFalse(result['database_writes']);self.assertEqual(20,result['files'])
    def test_php_failure_stops_before_publication(self):self.exercise('stage-inactive',False)
    def test_unapproved_mode_rejected(self):
        with self.assertRaises(s.Blocked):s.run(b,{},self.entries(),'activate')
    def test_old_or_incomplete_recovery_blocks(self):
        with tempfile.TemporaryDirectory() as tmp:
            parent=Path(tmp);binding={'bundle_id':'csf-gate-b-backup-'+'a'*32,'manifest_sha256':b.sha(b'manifest'),'receipt_sha256':'b'*64,'recovery_token':'c'*32};source=parent/binding['bundle_id'];source.mkdir();(source/'SHA256SUMS').write_bytes(b'manifest');(source/'receipt.json').write_text(json.dumps({'started':'2000-01-01T00:00:00Z'}))
            with mock.patch.object(b,'PARENT',parent),mock.patch.object(b,'verify_receipt'):
                with self.assertRaises(s.Blocked):s.recovery(b,binding)
    def test_reproducible_bounded_transport_and_closed_template(self):
        import sys,subprocess,base64,zlib
        sys.path.insert(0,str(b.BASE/'scripts/pilot'));import build_inactive_staging as builder
        # This builder intentionally stays pinned to the installed release. Exercise its
        # historical bytes in memory; a new local candidate must not silently move that pin.
        pinned={b.BASE/path:subprocess.check_output(['git','-C',str(b.BASE),'show',builder.PIN+':'+path])
                for line in (b.BASE/'docs/test-results/scheduling-security-files.sha256').read_text().splitlines()
                for path in [line.split()[1]] if path.startswith(s.BASE)}
        original=Path.read_bytes
        with mock.patch.object(Path,'read_bytes',lambda path:pinned[path] if path in pinned else original(path)):
            payload=builder.artifacts();self.assertEqual(payload,builder.artifacts())
        command=builder.command(payload,{},'preflight');tail=base64.b64encode(zlib.compress(payload,9))[48000:]
        self.assertLessEqual(len(command),65536);self.assertLessEqual(len(tail),65536)
        result=subprocess.run(['/bin/bash','-c',command.decode()],input=tail,capture_output=True,timeout=10)
        self.assertEqual(2,result.returncode);self.assertIn(b'STAGING_GUARD_FAILED',result.stdout)
    def test_pinned_builder_rejects_unreviewed_working_source(self):
        import sys
        sys.path.insert(0,str(b.BASE/'scripts/pilot'));import build_inactive_staging as builder
        original=Path.read_bytes
        with mock.patch.object(Path,'read_bytes',lambda path:b'tampered source' if path==b.BASE/'wp-content/plugins/csf-scheduling/csf-scheduling.php' else original(path)):
            with self.assertRaises(AssertionError):builder.artifacts()
    def test_source_manifest_hashes(self):
        for line in (b.BASE/'scripts/pilot/STAGING.sha256').read_text().splitlines():
            digest,path=line.split('  ');self.assertEqual(digest,hashlib.sha256((b.BASE/path).read_bytes()).hexdigest())
    def test_no_activation_or_migration_implementation(self):
        text=(b.BASE/'scripts/pilot/stage_scheduling.py').read_text()
        for forbidden in ('activate_plugin(', 'wp eval','ALTER TABLE','CREATE TABLE','UPDATE TXGEh','GRANT '):self.assertNotIn(forbidden,text)

if __name__=='__main__':unittest.main()
