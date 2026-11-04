"""Local durable/concurrent duplicate-submission protection; no mail transport."""
import concurrent.futures
import importlib.util
from pathlib import Path
import sqlite3
import tempfile
import unittest
import test_backup as fixture

spec = importlib.util.spec_from_file_location('mail_ledger', fixture.b.BASE / 'scripts/pilot/security_mail_ledger.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
TEST = '1' * 32


class LedgerTests(unittest.TestCase):
    def test_claim_is_durable_before_send_and_cannot_be_replayed(self):
        with tempfile.TemporaryDirectory() as folder:
            path = str(Path(folder) / 'opaque-fixture.sqlite')
            db = sqlite3.connect(path)
            self.assertTrue(m.SubmissionLedger(db).claim(TEST, '2' * 64, '3' * 32))
            db.close()  # Simulate loss before any known transport result.
            db = sqlite3.connect(path)
            self.assertFalse(m.SubmissionLedger(db).claim(TEST, '2' * 64, '4' * 32))
            db.close()

    def test_two_submissions_only_including_failed_or_unknown(self):
        db = sqlite3.connect(':memory:')
        ledger = m.SubmissionLedger(db)
        for n, state in ((2, 'submission_failed'), (3, 'outcome_unknown')):
            self.assertTrue(ledger.claim(TEST, str(n) * 64, str(n) * 32))
            ledger.finish(TEST, str(n) * 64, state)
            self.assertFalse(ledger.claim(TEST, str(n) * 64, '9' * 32))
        with self.assertRaises(ValueError): ledger.claim(TEST, '4' * 64, '4' * 32)

    def test_concurrent_workers_claim_exactly_once(self):
        with tempfile.TemporaryDirectory() as folder:
            path = str(Path(folder) / 'opaque-fixture.sqlite')
            db = sqlite3.connect(path)
            m.SubmissionLedger(db)
            db.close()
            def worker(n):
                conn = sqlite3.connect(path, timeout=3)
                try: return m.SubmissionLedger(conn).claim(TEST, '2' * 64, format(n, '032x'))
                finally: conn.close()
            with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
                self.assertEqual(1, sum(pool.map(worker, range(1, 9))))

    def test_delivery_observations_cannot_be_claimed_as_submission_outcomes(self):
        ledger = m.SubmissionLedger(sqlite3.connect(':memory:'))
        ledger.claim(TEST, '2' * 64, '3' * 32)
        for state in ('queued', 'attempted', 'provider_accepted', 'receiver_confirmed'):
            with self.assertRaises(ValueError): ledger.finish(TEST, '2' * 64, state)
        ledger.finish(TEST, '2' * 64, 'submission_accepted')
        ledger.finish(TEST, '2' * 64, 'submission_accepted')
        with self.assertRaises(ValueError): ledger.finish(TEST, '2' * 64, 'submission_failed')

    def test_concurrent_distinct_mailboxes_cannot_exceed_two_initial_claims(self):
        with tempfile.TemporaryDirectory() as folder:
            path = str(Path(folder) / 'opaque-fixture.sqlite')
            db = sqlite3.connect(path)
            m.SubmissionLedger(db)
            db.close()
            def worker(n):
                conn = sqlite3.connect(path, timeout=3)
                try:
                    try: return m.SubmissionLedger(conn).claim(TEST, format(n, '064x'), format(n, '032x'))
                    except ValueError: return False
                finally: conn.close()
            with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
                self.assertEqual(2, sum(pool.map(worker, range(1, 9))))

    def test_message_reference_collision_rolls_back_new_claim(self):
        ledger = m.SubmissionLedger(sqlite3.connect(':memory:'))
        ledger.claim(TEST, '2' * 64, '3' * 32)
        with self.assertRaises(sqlite3.IntegrityError): ledger.claim(TEST, '4' * 64, '3' * 32)
        self.assertTrue(ledger.claim(TEST, '4' * 64, '5' * 32))

    def test_private_values_and_unclaimed_results_rejected(self):
        ledger = m.SubmissionLedger(sqlite3.connect(':memory:'))
        with self.assertRaises(ValueError): ledger.claim(TEST, 'person@example.invalid', '3' * 32)
        with self.assertRaises(ValueError): ledger.finish(TEST, '2' * 64, 'submission_accepted')


if __name__ == '__main__': unittest.main()
