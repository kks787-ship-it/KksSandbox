"""Local synthetic specification tests; no DB client, capture, restore, network or farm access."""
import copy
import hashlib
import json
from pathlib import Path
import unittest

POLICY = json.loads((Path(__file__).resolve().parents[2] / 'docs/pilot/baseline-contract.json').read_text())


def frame(cells):
    result = bytearray(b'CSFBASELINE1\0')
    for value in cells:
        if value is None:
            result.extend(b'N;')
        elif isinstance(value, bytes):
            result.extend(b'V' + str(len(value)).encode('ascii') + b':' + value.hex().encode('ascii') + b';')
        else:
            raise ValueError('Capture exact bytes or SQL NULL; do not coerce types')
    return bytes(result)


def digest(value):
    return hashlib.sha256(json.dumps(value, sort_keys=True, separators=(',', ':'), allow_nan=False).encode()).hexdigest()


def record(table, primary_key, cells, label=None):
    if not primary_key or any(value is None for value in primary_key):
        raise ValueError('Explicit primary key required')
    return {'table': table,
            'key_hash': hashlib.sha256(frame([table.encode(), *primary_key])).hexdigest(),
            'value_hash': hashlib.sha256(frame(cells)).hexdigest(), 'label': label}


def snapshot(rows, capture='synthetic capture A', database='synthetic', prefix='fixture_', schema='synthetic-schema-v1', origin='synthetic-live-diagnostic'):
    return {'version': POLICY['version'], 'database': database, 'prefix': prefix,
            'schema_digest': schema, 'origin': origin, 'captured_at': capture, 'rows': rows}


def category(row, prefix):
    if row['table'] != 'options':
        return 'critical'
    label = row['label'] or ''
    critical = {name.replace('{prefix}', prefix) for name in POLICY['critical_options']}
    if label in critical or label.startswith(tuple(POLICY['critical_option_prefixes'])):
        return 'critical'
    if label in POLICY['volatile_candidate_options']:
        return 'volatile_candidate'
    return 'unclassified'


def index_rows(value):
    result = {}
    for row in value['rows']:
        if set(row) != {'table', 'key_hash', 'value_hash', 'label'}:
            raise ValueError('Unreviewed row fields/classifications')
        key = (row['table'], row['key_hash'])
        if key in result:
            raise ValueError('Duplicate row identity; never overwrite evidence')
        result[key] = row
    return result


def compare(before, after, approved_before_hash, approved_after_hash):
    if digest(before) != approved_before_hash or digest(after) != approved_after_hash:
        raise ValueError('Snapshot differs from trusted private receipt/manifest')
    if any(before[name] != after[name] for name in POLICY['source_binding']):
        raise ValueError('Different source/version/schema requires separate review')
    old, new = index_rows(before), index_rows(after)
    changes = []
    for key in sorted(old.keys() | new.keys()):
        a, b = old.get(key), new.get(key)
        if a == b:
            continue
        kind = 'added' if a is None else 'removed' if b is None else 'modified'
        # Never trust a caller's precomputed severity or drop a volatile candidate.
        classes = {category(row, before['prefix']) for row in (a, b) if row is not None}
        severity = 'critical' if 'critical' in classes else 'unclassified' if 'unclassified' in classes else 'volatile_candidate'
        changes.append({'table': key[0], 'locator': key[1], 'kind': kind, 'category': severity})
    return {'raw_drift': bool(changes), 'review_required': bool(changes), 'changes': changes,
            'stable_projection_changes': sum(c['category'] != 'volatile_candidate' for c in changes)}


class BaselineContract(unittest.TestCase):
    def compare(self, before, after):
        return compare(before, after, digest(before), digest(after))

    def option(self, name, value):
        return record('options', [name.encode()], [name.encode(), value], name)

    def test_null_empty_and_literal_are_distinct(self):
        self.assertEqual(4, len({frame([None]), frame([b'']), frame([b'NULL']), frame([b'\0'])}))

    def test_binary_and_delimiters_cannot_alias(self):
        self.assertNotEqual(frame([b'a|b', b'c']), frame([b'a', b'b|c']))
        self.assertNotEqual(frame([b'\xff\0']), frame([b'ff00']))

    def test_type_coercion_rejected(self):
        for value in (False, 0, '', []):
            with self.assertRaises(ValueError):
                frame([value])

    def test_composite_key_order_preserved(self):
        self.assertNotEqual(record('links', [b'a', b'b'], [])['key_hash'], record('links', [b'b', b'a'], [])['key_hash'])

    def test_row_order_is_not_a_change(self):
        rows = [self.option('home', b'synthetic origin'), self.option('siteurl', b'synthetic origin')]
        self.assertFalse(self.compare(snapshot(rows), snapshot(list(reversed(rows))))['raw_drift'])

    def test_capture_time_is_provenance_only(self):
        self.assertFalse(self.compare(snapshot([], 'synthetic A'), snapshot([], 'synthetic B'))['raw_drift'])

    def test_security_option_is_never_suppressed(self):
        r = self.compare(snapshot([self.option('users_can_register', b'0')]), snapshot([self.option('users_can_register', b'1')]))
        self.assertEqual('critical', r['changes'][0]['category'])
        self.assertTrue(r['review_required'])

    def test_volatile_candidate_still_preserved_and_reviewed(self):
        r = self.compare(snapshot([self.option('_transient_doing_cron', b'synthetic A')]), snapshot([self.option('_transient_doing_cron', b'synthetic B')]))
        self.assertTrue(r['raw_drift'] and r['review_required'])
        self.assertEqual(0, r['stable_projection_changes'])
        self.assertEqual(1, len(r['changes']))

    def test_unknown_transient_is_not_a_wildcard_exception(self):
        r = self.compare(snapshot([]), snapshot([self.option('_transient_synthetic_security_state', b'synthetic')]))
        self.assertEqual('unclassified', r['changes'][0]['category'])

    def test_auth_table_never_uses_option_exceptions(self):
        r = self.compare(snapshot([]), snapshot([record('usermeta', [b'synthetic key'], [b'changed'], '_transient_doing_cron')]))
        self.assertEqual('critical', r['changes'][0]['category'])

    def test_added_removed_rows_do_not_cancel(self):
        r = self.compare(snapshot([self.option('synthetic old', b'same')]), snapshot([self.option('synthetic new', b'same')]))
        self.assertEqual({'added', 'removed'}, {c['kind'] for c in r['changes']})

    def test_duplicate_key_rejected(self):
        row = self.option('home', b'synthetic')
        with self.assertRaises(ValueError):
            self.compare(snapshot([]), snapshot([row, row]))

    def test_manifest_tampering_rejected(self):
        before = snapshot([self.option('admin_email', b'synthetic contact')])
        trusted = digest(before)
        tampered = copy.deepcopy(before)
        tampered['rows'][0]['label'] = '_transient_doing_cron'
        with self.assertRaises(ValueError):
            compare(before, tampered, trusted, trusted)

    def test_untrusted_classification_rejected(self):
        after = snapshot([self.option('active_plugins', b'synthetic')])
        after['rows'][0]['classification'] = 'volatile_candidate'
        with self.assertRaises(ValueError):
            self.compare(snapshot([]), after)

    def test_other_source_or_schema_refuses_comparison(self):
        for after in (snapshot([], database='another synthetic source'), snapshot([], schema='another schema'), snapshot([], prefix='another_'), snapshot([], origin='synthetic-artifact-restored')):
            with self.assertRaises(ValueError):
                self.compare(snapshot([]), after)

    def test_audit_timestamp_is_not_normalized(self):
        a = record('audit', [b'synthetic audit'], [b'synthetic time A'])
        b = record('audit', [b'synthetic audit'], [b'synthetic time B'])
        self.assertEqual('critical', self.compare(snapshot([a]), snapshot([b]))['changes'][0]['category'])

    def test_site_role_option_uses_bound_prefix(self):
        r = self.compare(snapshot([]), snapshot([self.option('fixture_user_roles', b'synthetic role policy')]))
        self.assertEqual('critical', r['changes'][0]['category'])


if __name__ == '__main__':
    unittest.main()
