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
    def test_handler_defaults_extract_only_mail_value_from_text_or_html(self):
        for info in ('secret=do-not-return\nsendmail_path => /usr/sbin/sendmail -t -i => /usr/sbin/sendmail -t -i\n', '<tr><td class="e">sendmail_path</td><td class="v">/usr/sbin/sendmail -t -i</td><td class="v">same</td></tr>'):
            self.assertEqual('/usr/sbin/sendmail -t -i', m.handler_mail_default(info))
        for info in ('no setting', 'sendmail_path => x => x\nsendmail_path => y => y'):
            with self.assertRaises(ValueError): m.handler_mail_default(info)

    def test_pool_override_takes_precedence_over_handler_default(self):
        from types import SimpleNamespace
        binaries = []
        r = m.pool_transport('[synthetic.invalid]\nphp_value[sendmail_path]=/custom/sendmail', 'synthetic.invalid', lambda args, **k: binaries.append(args[-1]) or SimpleNamespace(returncode=1, stdout=b''), lambda p: True, default='/default/sendmail')
        self.assertEqual(['/custom/sendmail'], binaries)
        self.assertEqual('pool_value_override', r['sendmail_setting_source'])

    def test_pool_missing_override_does_not_guess_transport(self):
        r = m.pool_transport('[synthetic.invalid]\n', 'synthetic.invalid')
        self.assertFalse(r['pool_admin_sendmail_override_present'])
        self.assertIsNone(r['queue_capable_transport'])
        self.assertFalse(r['current_web_pool_mapping_verified'])

    def test_pool_identity_and_dynamic_or_duplicate_commands_rejected(self):
        for text in ('[other.invalid]', '[synthetic.invalid]\nphp_admin_value[sendmail_path]=$SECRET', '[synthetic.invalid]\nphp_admin_value[sendmail_path]=/bin/mail\nphp_admin_value[sendmail_path]=/bin/mail'):
            with self.assertRaises(ValueError): m.pool_transport(text, 'synthetic.invalid')

    def test_native_queue_is_not_silently_treated_as_no_retry(self):
        from types import SimpleNamespace
        calls = []
        def run(args, **kwargs):
            calls.append(args)
            return SimpleNamespace(returncode=0, stdout=b'postfix: /usr/sbin/sendmail\n' if len(calls) == 1 else b'5d\n')
        r = m.pool_transport('[synthetic.invalid]\nphp_admin_value[sendmail_path] = /usr/sbin/sendmail -t -i -f private@example.invalid', 'synthetic.invalid', run, lambda p: True)
        self.assertTrue(r['queue_capable_transport'])
        self.assertTrue(r['nonzero_native_queue_lifetime_observed'])
        self.assertFalse(r['strict_no_queue_bound_verified'])
        self.assertNotIn('private@example.invalid', json.dumps(r))
        self.assertEqual(['/usr/sbin/postconf', '-h', 'maximal_queue_lifetime'], calls[1])
        self.assertTrue(all(args[0] != '/usr/sbin/sendmail' for args in calls))

    def test_unknown_package_is_unresolved_not_a_nonqueueing_claim(self):
        from types import SimpleNamespace
        r = m.pool_transport('[synthetic.invalid]\nphp_admin_value[sendmail_path]=/usr/sbin/sendmail', 'synthetic.invalid', lambda *a, **k: SimpleNamespace(returncode=1, stdout=b''), lambda p: True)
        self.assertIsNone(r['queue_capable_transport'])

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

    def test_transport_command_has_no_arbitrary_entrypoint_or_private_input(self):
        sys.path.insert(0, str(fixture.b.BASE / 'scripts/pilot'))
        import build_mail_review
        with self.assertRaises(ValueError): build_mail_review.command('capture')
        cmd = build_mail_review.command('transport_main')
        self.assertEqual(cmd, build_mail_review.command('transport_main'))
        r = subprocess.run(['/bin/bash', '-c', cmd.decode()], input=b'not-allowed', capture_output=True, timeout=5)
        self.assertEqual(2, r.returncode)
        self.assertFalse(json.loads(r.stdout)['writes'])


if __name__ == '__main__': unittest.main()
