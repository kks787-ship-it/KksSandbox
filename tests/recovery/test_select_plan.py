"""Synthetic command-plan safety checks. Never executes SQL or connects to a server."""
import copy
from pathlib import Path
import unittest

PATH = Path(__file__).resolve().parents[2] / 'docs/pilot/baseline-select-plan.py.txt'
NAMESPACE = {'__name__': 'local_proposal_test'}
exec(compile(PATH.read_text(), str(PATH), 'exec'), NAMESPACE)
generate = NAMESPACE['generate']


def fixture():
    columns = []
    for table in sorted(NAMESPACE['NATIVE']):
        columns.append({'table': 'TXGEh_' + table, 'column': 'synthetic_id', 'ordinal': 1, 'pk_order': 1, 'engine': 'InnoDB'})
        if table == 'options':
            columns.append({'table': 'TXGEh_options', 'column': 'option_name', 'ordinal': 2, 'pk_order': None, 'engine': 'InnoDB'})
    return {'columns': columns, 'indexes': []}


class SelectPlan(unittest.TestCase):
    def test_one_read_only_transaction_covers_twelve_tables(self):
        sql = generate(fixture())
        self.assertEqual(1, sql.count('START TRANSACTION WITH CONSISTENT SNAPSHOT'))
        self.assertEqual(1, sql.count('COMMIT;'))
        self.assertEqual(12, sql.count("'key_hash'"))
        self.assertIn('TRANSACTION READ ONLY', sql)
        for forbidden in ('INSERT ', 'UPDATE ', 'DELETE ', 'DROP ', 'CREATE ', 'LOCK TABLES', 'SET GLOBAL', 'FLUSH '):
            self.assertNotIn(forbidden, sql)

    def test_catalog_order_does_not_change_plan(self):
        catalog = fixture()
        reverse = copy.deepcopy(catalog)
        reverse['columns'].reverse()
        self.assertEqual(generate(catalog), generate(reverse))

    def test_identifier_injection_rejected(self):
        catalog = fixture()
        catalog['columns'][0]['column'] = 'bad`; DROP TABLE x;'
        with self.assertRaises(ValueError):
            generate(catalog)

    def test_missing_extra_or_wrong_prefix_tables_rejected(self):
        for mutation in ('missing', 'extra', 'prefix'):
            catalog = fixture()
            if mutation == 'missing':
                catalog['columns'] = catalog['columns'][1:]
            elif mutation == 'extra':
                row = copy.deepcopy(catalog['columns'][0]);row['table'] = 'TXGEh_unreviewed';catalog['columns'].append(row)
            else:
                catalog['columns'][0]['table'] = 'unrelated_comments'
            with self.assertRaises(ValueError):
                generate(catalog)

    def test_nontransactional_engine_rejected(self):
        catalog = fixture();catalog['columns'][0]['engine'] = 'MyISAM'
        with self.assertRaises(ValueError):
            generate(catalog)

    def test_absent_primary_key_and_duplicate_columns_rejected(self):
        catalog = fixture();catalog['columns'][0]['pk_order'] = None
        with self.assertRaises(ValueError):
            generate(catalog)
        catalog = fixture();catalog['columns'].append(copy.deepcopy(catalog['columns'][0]))
        with self.assertRaises(ValueError):
            generate(catalog)

    def test_bytes_null_and_utc_are_explicit(self):
        sql = generate(fixture())
        self.assertIn("time_zone='+00:00'", sql)
        self.assertIn("IS NULL,'N;'", sql)
        self.assertIn('OCTET_LENGTH(CAST(', sql)
        self.assertIn('LOWER(HEX(CAST(', sql)
        self.assertIn('CHAR(0)', sql)

    def test_composite_primary_key_uses_index_order(self):
        catalog = fixture();row = catalog['columns'][0]
        row['pk_order'] = 2
        catalog['columns'].append({'table': row['table'], 'column': 'second_key', 'ordinal': 2, 'pk_order': 1, 'engine': 'InnoDB'})
        sql = generate(catalog)
        self.assertIn('ORDER BY `second_key`,`synthetic_id`;', sql)


if __name__ == '__main__':
    unittest.main()
