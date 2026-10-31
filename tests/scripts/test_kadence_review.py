"""Runner refusal paths only; no browser launch, network, or real credentials."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

RUNNER = Path(__file__).resolve().parents[2] / 'tests/e2e/kadence-review.js'


class KadenceReviewGuards(unittest.TestCase):
    def run_guard(self, expected, *, origin=None, content=None, mode=0o600):
        env = os.environ.copy()
        env.pop('CSF_HTTP_AUTH_FILE', None)
        env.pop('CSF_REVIEW_URL', None)
        if origin:
            env['CSF_REVIEW_URL'] = origin
        with tempfile.TemporaryDirectory(prefix='csf-browser-auth-guard.') as directory:
            if content is not None:
                auth = Path(directory) / 'synthetic.json'
                auth.write_text(content)
                auth.chmod(mode)
                env['CSF_HTTP_AUTH_FILE'] = str(auth)
            result = subprocess.run(['node', str(RUNNER)], env=env, capture_output=True, text=True, timeout=15)
        self.assertEqual(result.returncode, 2, result.stderr)
        self.assertIn(expected, result.stderr)
        self.assertNotIn('SYNTHETIC_VALUE_DO_NOT_LOG', result.stdout + result.stderr)

    def test_production_refused(self):
        self.run_guard('Only the exact sandbox origin', origin='https://cornerstonefarmomak.com')

    def test_other_host_refused(self):
        self.run_guard('Only the exact sandbox origin', origin='https://sandbox.cornerstonefarmomak.com.example.invalid')

    def test_missing_credentials_blocked(self):
        self.run_guard('Authenticated review is blocked')

    def test_public_credential_file_refused(self):
        self.run_guard('private permissions', content=json.dumps({'username': 'fixture', 'password': 'SYNTHETIC_VALUE_DO_NOT_LOG'}), mode=0o644)

    def test_malformed_json_never_echoed(self):
        self.run_guard('contents were not logged', content='{"password": "SYNTHETIC_VALUE_DO_NOT_LOG", broken')

    def test_empty_credentials_refused(self):
        self.run_guard('nonempty username and password', content=json.dumps({'username': '', 'password': 'SYNTHETIC_VALUE_DO_NOT_LOG'}))


if __name__ == '__main__':
    unittest.main()
