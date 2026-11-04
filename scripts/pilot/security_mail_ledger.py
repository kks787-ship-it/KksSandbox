"""Local pilot submission gate; no mail, filesystem, queue or WordPress operations.

A future approved dispatcher must use a dedicated, private durable database and commit
the claim BEFORE submission. An unresolved claim forbids blind retry after a crash.
Recipient references must be deduplicated/bound privately before this gate is used.
"""
import re


class SubmissionLedger:
    def __init__(self, connection):
        if connection.in_transaction:
            raise ValueError('Dedicated idle connection required')
        self.db = connection
        self.db.execute('CREATE TABLE IF NOT EXISTS submissions (test_ref TEXT NOT NULL, recipient_ref TEXT NOT NULL, message_ref TEXT NOT NULL UNIQUE, state TEXT NOT NULL, PRIMARY KEY(test_ref,recipient_ref))')

    def claim(self, test_ref, recipient_ref, message_ref):
        for value, size in ((test_ref, 32), (recipient_ref, 64), (message_ref, 32)):
            if not isinstance(value, str) or re.fullmatch('[a-f0-9]{' + str(size) + '}', value) is None:
                raise ValueError('Opaque bound references required')
        self.db.execute('BEGIN IMMEDIATE')
        try:
            if self.db.execute('SELECT 1 FROM submissions WHERE test_ref=? AND recipient_ref=?', (test_ref, recipient_ref)).fetchone():
                self.db.commit()
                return False
            if self.db.execute('SELECT COUNT(*) FROM submissions WHERE test_ref=?', (test_ref,)).fetchone()[0] >= 2:
                raise ValueError('Two initial submissions maximum')
            self.db.execute('INSERT INTO submissions VALUES (?,?,?,?)', (test_ref, recipient_ref, message_ref, 'claimed'))
            self.db.commit()
            return True
        except Exception:
            self.db.rollback()
            raise

    def finish(self, test_ref, recipient_ref, state):
        if state not in ('submission_failed', 'submission_accepted', 'outcome_unknown'):
            raise ValueError('Not a submission outcome')
        self.db.execute('BEGIN IMMEDIATE')
        try:
            row = self.db.execute('SELECT state FROM submissions WHERE test_ref=? AND recipient_ref=?', (test_ref, recipient_ref)).fetchone()
            if row is None:
                raise ValueError('Claim required')
            if row[0] == 'claimed':
                self.db.execute('UPDATE submissions SET state=? WHERE test_ref=? AND recipient_ref=?', (state, test_ref, recipient_ref))
            elif row[0] != state:
                raise ValueError('Conflicting outcome requires reconciliation')
            self.db.commit()
        except Exception:
            self.db.rollback()
            raise
