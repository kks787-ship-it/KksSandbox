"""Syntax, reproducible packaging, documentation links and privacy regression checks."""
import ast
import hashlib
from pathlib import Path
import re
import subprocess
import unittest

ROOT=Path(__file__).resolve().parents[2]

class Package(unittest.TestCase):
    def test_all_payload_python_parses_without_import_side_effects(self):
        for path in (ROOT/'scripts/recovery').glob('*.py'):ast.parse(path.read_text(),filename=str(path))
    def test_all_shell_entrypoints_parse(self):
        for name in ('scripts/recovery/run.sh','scripts/recovery/inspect.command.sh','scripts/recovery/capture.command.sh','tests/recovery/mariadb-defaults.sh'):
            subprocess.run(['/bin/bash','-n',str(ROOT/name)],check=True)
    def test_release_file_hashes_and_fixed_allowlist(self):
        expected={'scripts/recovery/backup.py','scripts/recovery/build.py','scripts/recovery/run.sh','scripts/recovery/payload.py','scripts/recovery/inspect.command.sh','scripts/recovery/capture.command.sh','docs/pilot/baseline-contract.json','docs/pilot/baseline-select-plan.py.txt'}
        observed=set()
        for line in (ROOT/'scripts/recovery/PAYLOAD.sha256').read_text().splitlines():
            digest,name=line.split('  ');self.assertIn(name,expected);self.assertNotIn(name,observed);observed.add(name)
            self.assertEqual(digest,hashlib.sha256((ROOT/name).read_bytes()).hexdigest())
        self.assertEqual(expected,observed)
    def test_relative_document_links(self):
        for name in ('docs/pilot/r-backup-approval.md','docs/pilot/recovery-command-catalogue.md','docs/05-backup-rollback-deploy.md','docs/test-results/recovery-readiness.md'):
            path=ROOT/name;text=re.sub(r'```.*?```','',path.read_text(),flags=re.S)
            for target in re.findall(r'\[[^\]]+\]\(([^\s)]+)\)',text):
                target=target.split('#')[0]
                if not target or '://' in target or target.startswith('mailto:'):continue
                self.assertTrue((path.parent/target).exists(),(name,target))
    def test_public_sources_have_no_credential_artifacts(self):
        patterns=(r'-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----',r'gh[pousr]_[A-Za-z0-9]{30,}',r'AKIA[A-Z0-9]{16}',r'https?://[^\s/:]+:[^\s/@]+@')
        for path in (ROOT/'scripts/recovery').iterdir():
            self.assertTrue(path.is_file());text=path.read_text()
            for pattern in patterns:self.assertIsNone(re.search(pattern,text),path.name)
    def test_recovery_pin_and_targets_preserved(self):
        source=(ROOT/'scripts/recovery/backup.py').read_text()
        self.assertIn("IMPLEMENTATION = '2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1'",source)
        self.assertNotIn('wp_iacb7',source);self.assertNotIn('wp_mdoaq',source)

if __name__=='__main__':unittest.main()
