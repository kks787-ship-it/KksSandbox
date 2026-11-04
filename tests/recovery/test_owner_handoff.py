"""No real handoff or identities: prevent reference-presence checks claiming authentication."""
import importlib.util
import json
import unittest
import tempfile
import subprocess
import sys
from pathlib import Path
import test_backup as fixture

spec = importlib.util.spec_from_file_location('owner_handoff', fixture.b.BASE / 'scripts/pilot/review_owner_handoff.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


class OwnerHandoffTests(unittest.TestCase):
    def test_mail_review_reference_is_not_an_exact_binding_or_dispatch_consent(self):
        r = m.mail_readiness(b'Mail review reference: synthetic-completed-record\n')
        self.assertTrue(r['mail_review_reference_supplied'])
        self.assertEqual(3, len(r['missing_fields']))
        self.assertFalse(r['execution_authorized'])

    def test_explicit_mail_binding_still_requires_actual_configuration_verification(self):
        raw = json.dumps({'mail_test': {'sender': 'sender@example.invalid', 'recipients': ['receiver@example.invalid'], 'recipient_consent': True}}).encode()
        r = m.mail_readiness(raw)
        self.assertEqual([], r['missing_fields'])
        self.assertFalse(r['sender_and_recipient_configuration_verified'])
        self.assertNotIn('sender@example.invalid', json.dumps(r))
        self.assertFalse(r['execution_authorized'])

    def test_mail_injection_duplicates_and_negative_consent_cannot_pass(self):
        for sender, recipients in [('sender@example.invalid\nBcc:x@example.invalid', ['receiver@example.invalid']), ('sender@example.invalid', ['receiver@example.invalid', 'receiver@example.invalid']), ('sender@example.invalid', ['a@example.invalid', 'b@example.invalid', 'c@example.invalid'])]:
            r = m.mail_readiness(json.dumps({'mail_test': {'sender': sender, 'recipients': recipients, 'recipient_consent': False}}).encode())
            self.assertTrue(r['missing_fields'])
            self.assertFalse(r['explicit_recipient_consent_supplied'])
    def sample(self, value):
        return ('\n'.join(name + ': controller reference '+value+'; recovery custodian reference '+value+';\n witnessed native login date/verifier reference '+value+'; factor/device custodian '+value+';\n recovery-code custody reference '+value+'.\n' for name in ('Technical Master', 'Operations', 'Head Trainer'))+'\nTechnical operator and initial farm owner: different humans\nMaintenance/recovery custodian reference: '+value+'\n').encode()

    def test_completed_references_never_authenticate_or_expose_values(self):
        r = m.review(self.sample('synthetic-private-record'))
        self.assertTrue(all(v for role in r['roles'].values() for v in role.values()))
        self.assertTrue(r['maintenance_reference_supplied'])
        self.assertEqual('different_humans', r['human_independence_choice'])
        self.assertFalse(r['independent_evidence_verified'])
        self.assertFalse(r['execution_authorized'])
        self.assertNotIn('synthetic-private-record', json.dumps(r))

    def test_blank_template_and_pending_remain_missing(self):
        for value in ('__', 'pending', 'not yet verified', '<reference>'):
            self.assertFalse(any(v for role in m.review(self.sample(value))['roles'].values() for v in role.values()))

    def test_choices_must_be_explicit_not_an_unselected_menu(self):
        self.assertEqual('unresolved', m.choice('different humans / same human / undecided'))
        self.assertEqual('same_human', m.choice('same human'))

    def test_common_code_fence_and_markdown_roles_are_supported(self):
        text = self.sample('synthetic-reference').decode().replace('Technical Master:', '**Technical Master**:')
        r = m.review(('```text\n' + text + '\n```').encode())
        self.assertTrue(r['structure']['code_fence_removed'])
        self.assertTrue(r['roles']['technical_master']['controller'])

    def test_artifact_reference_is_not_misreported_as_a_completed_checklist(self):
        r = m.review(b'/synthetic-private/checklist.md')
        self.assertTrue(r['structure']['artifact_reference_only'])
        self.assertFalse(r['structure']['known_reference_labels_present'])
        self.assertNotIn('synthetic-private', json.dumps(r))

    def test_json_missing_fields_stay_missing(self):
        r = m.review(json.dumps({'accounts': {'operations': {'controller_ref': 'synthetic-record'}}, 'human_independence': 'different_people'}).encode())
        self.assertTrue(r['roles']['operations']['controller'])
        self.assertFalse(r['roles']['operations']['witnessed_login'])
        self.assertFalse(r['roles']['head_trainer']['controller'])

    def test_oversize_controls_and_authentication_material_rejected(self):
        for value in (b'x'*65537, b'\0', b'Password: must-not-return', b'{"accounts":{"x":{"totp_seed":"must-not-return"}}}'):
            with self.assertRaises(ValueError): m.review(value)

    def test_secure_file_review_removes_only_its_temporary_copy(self):
        sys.path.insert(0, str(fixture.b.BASE / 'scripts/pilot'))
        import build_owner_handoff_review as builder
        import uuid
        for content in (self.sample('synthetic-reference'), b'Password: must-not-return', b'x'*65537):
            folder = Path('/tmp/dr-controller-review-' + uuid.uuid4().hex)
            folder.mkdir(mode=0o700)
            path = folder / 'handoff'
            path.write_bytes(content)
            path.chmod(0o600)
            cmd = builder.command(str(folder))
            self.assertEqual(cmd, builder.command(str(folder)))
            r = subprocess.run(['/bin/bash', '-c', cmd.decode()], capture_output=True, timeout=5)
            self.assertEqual(0, r.returncode)
            result = json.loads(r.stdout)
            self.assertTrue(result['temporary_copy_removed'])
            self.assertFalse(result['execution_authorized'])
            self.assertFalse(folder.exists())
            self.assertNotIn('synthetic-reference', r.stdout.decode())
        with self.assertRaises(ValueError): builder.command('/tmp/unrelated')


if __name__ == '__main__': unittest.main()
