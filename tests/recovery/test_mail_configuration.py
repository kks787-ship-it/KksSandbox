"""Synthetic indicators only; no target connection, accounts or mail transport."""
import importlib.util
import json
import subprocess
import sys
import unittest
import test_backup as fixture

spec = importlib.util.spec_from_file_location('mail_review', fixture.b.BASE / 'scripts/pilot/inspect_mail_configuration.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


class DB:
    def __init__(self, row=None):
        self.row = row if row is not None else {'admin_present': 1, 'admin_plausible': 1, 'known_mailer_option_rows': 0}
        self.queries = []

    def query(self, sql):
        self.queries.append(sql)
        return json.dumps(self.row).encode()


class MailReview(unittest.TestCase):
    def test_only_boolean_indicators_no_addresses_or_account_reads(self):
        db = DB()
        r = m.inspect(db, 'fixture_', 'a:0:{}')
        self.assertTrue(r['native_admin_address_present'])
        for key in ('writes', 'delivery_tested', 'recipient_ownership_verified', 'controller_addresses_reviewed', 'sender_verified', 'web_sapi_transport_verified', 'execution_authorized'):
            self.assertIs(r[key], False)
        self.assertTrue(db.queries[0].startswith('SELECT '))
        self.assertNotIn('users', db.queries[0])
        self.assertNotIn('@example', json.dumps(r))

    def test_known_mailer_is_not_a_delivery_or_transport_claim(self):
        r = m.inspect(DB({'admin_present': 1, 'admin_plausible': 1, 'known_mailer_option_rows': 1}), 'fixture_', 'a:1:{i:0;s:32:"wp-mail-smtp/wp_mail_smtp.php";}')
        self.assertEqual(1, r['recognized_active_mailer_count'])
        self.assertTrue(r['known_mailer_option_rows_present'])
        self.assertFalse(r['web_sapi_transport_verified'])

    def test_malformed_and_missing_configuration_remains_unverified(self):
        r = m.inspect(DB({'admin_present': 0, 'admin_plausible': 0, 'known_mailer_option_rows': 0}), 'fixture_', 'a:0:{}')
        self.assertFalse(r['native_admin_address_syntax_plausible'])
        for row in ({}, {'admin_present': 1, 'admin_plausible': 1, 'known_mailer_option_rows': 100}, {'admin_present': 'sensitive-value', 'admin_plausible': 1, 'known_mailer_option_rows': 0}):
            with self.assertRaises(ValueError): m.inspect(DB(row), 'fixture_', '')

    def test_prefix_override_rejected_before_sql(self):
        db = DB()
        with self.assertRaises(ValueError): m.inspect(db, 'bad`prefix', '')
        self.assertEqual([], db.queries)

    def test_command_is_deterministic_bounded_and_rejects_input_before_target(self):
        sys.path.insert(0, str(fixture.b.BASE / 'scripts/pilot'))
        import build_mail_review
        cmd = build_mail_review.command()
        self.assertEqual(cmd, build_mail_review.command())
        self.assertLess(len(cmd), 65536)
        self.assertIn(b'55s', cmd)
        r = subprocess.run(['/bin/bash', '-c', cmd.decode()], input=b'not-allowed', capture_output=True, timeout=5)
        self.assertEqual(2, r.returncode)
        self.assertFalse(json.loads(r.stdout)['writes'])


if __name__ == '__main__': unittest.main()
