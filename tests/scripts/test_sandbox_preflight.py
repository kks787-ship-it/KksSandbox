#!/usr/bin/env python3
"""Target guard tests only; fixtures/mock command outputs do not validate Plesk."""
import importlib.util
import json
from pathlib import Path
import tempfile
import subprocess
import sys
import unittest
from unittest.mock import patch

MODULE_PATH = Path(__file__).resolve().parents[2] / 'scripts/sandbox-preflight.py'
spec = importlib.util.spec_from_file_location('sandbox_preflight', MODULE_PATH)
preflight = importlib.util.module_from_spec(spec)
spec.loader.exec_module(preflight)


class SandboxPreflightTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / preflight.ROOT_NAME
        (self.root / 'wp-content/themes/kadence').mkdir(parents=True)
        (self.root / 'wp-config.php').write_text('<?php // inert test fixture')
        (self.root / 'wp-content/themes/kadence/style.css').write_text('/*\nTheme Name: Kadence\nVersion: 1.5.2\n*/')
        self.commands = []
        self.database = preflight.DATABASE
        self.connected = preflight.DATABASE
        self.prefix = 'wp_'
        self.environment = 'development'
        self.options = dict(home=preflight.URL, siteurl=preflight.URL, template='kadence',
                            stylesheet='kadence', blog_public='0', permalink_structure='/%postname%/')
        self.php_version = '8.3.36'
        self.extensions = ['mysqli', 'json', 'mbstring', 'dom', 'xml', 'curl', 'openssl', 'fileinfo', 'filter', 'hash', 'gd']

    def command(self, args):
        self.commands.append(args)
        if args[0] == 'rsync':
            return '>f+++++++++ csf-plugin.php'
        if args[0] == 'php':
            return json.dumps({'version': self.php_version, 'extensions': self.extensions})
        command = args[4:]
        if command[:2] == ['config', 'get']:
            return {'DB_NAME': self.database, 'table_prefix': self.prefix, 'WP_ENVIRONMENT_TYPE': self.environment}[command[2]]
        if command[:2] == ['db', 'query']:
            sql = command[2]
            self.assertTrue(sql.startswith('START TRANSACTION READ ONLY; '))
            self.assertTrue(sql.endswith('; COMMIT;'))
            if 'SELECT DATABASE()' in sql:
                return self.connected
            if 'SELECT VERSION()' in sql:
                return '8.0 test fixture'
            self.assertIn('`wp_mdf4x`.`wp_options`', sql)
            return '\n'.join(key + '\t' + value for key, value in self.options.items())
        if command == ['core', 'version']:
            return '7.1.4'
        self.fail('Unexpected command: ' + repr(args))

    def inspect(self, dry_run=True):
        with patch.object(preflight, 'run', side_effect=self.command), patch.object(preflight.shutil, 'which', return_value='/fixture/tool'):
            return preflight.inspect(str(self.root), dry_run)

    def assert_no_database_queries(self):
        self.assertFalse(any('query' in command for command in self.commands))
        self.assertFalse(any(command[0] == 'rsync' for command in self.commands))

    def test_apply_flag_is_rejected(self):
        result = subprocess.run([sys.executable, str(MODULE_PATH), str(self.root), '--apply'], capture_output=True, text=True)
        self.assertEqual(result.returncode, 2)
        self.assertIn('unrecognized arguments: --apply', result.stderr)

    def test_production_root_name_blocks_before_commands(self):
        wrong = Path(self.temp.name) / 'httpdocs'
        wrong.mkdir()
        with patch.object(preflight, 'run', side_effect=self.command):
            report, status = preflight.inspect(str(wrong), True)
        self.assertEqual(status, 1, report)
        self.assertEqual(self.commands, [])

    def test_symlinked_root_blocks_before_commands(self):
        link = Path(self.temp.name) / 'linked-root'
        link.symlink_to(self.root, target_is_directory=True)
        with patch.object(preflight, 'run', side_effect=self.command):
            report, status = preflight.inspect(str(link), True)
        self.assertEqual(status, 1, report)
        self.assertEqual(self.commands, [])

    def test_matching_target_runs_only_read_queries_and_dry_rsync(self):
        before = {str(p): p.read_bytes() for p in self.root.rglob('*') if p.is_file()}
        report, status = self.inspect()
        self.assertEqual(status, 0, report)
        copies = [command for command in self.commands if command[0] == 'rsync']
        self.assertEqual(len(copies), 2)
        self.assertTrue(all('--dry-run' in command for command in copies))
        self.assertFalse(any('eval' in command or 'option' in command or 'theme' in command for command in self.commands))
        self.assertEqual(before, {str(p): p.read_bytes() for p in self.root.rglob('*') if p.is_file()})

    def test_production_and_unassigned_db_block_before_query(self):
        for database in ('wp_iacb7', 'wp_mdoaq', 'unknown'):
            with self.subTest(database=database):
                self.commands.clear()
                self.database = database
                report, status = self.inspect()
                self.assertEqual(status, 1, report)
                self.assert_no_database_queries()

    def test_mismatched_connected_db_blocks_before_option_query(self):
        self.connected = 'wp_iacb7'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertEqual(len([c for c in self.commands if 'query' in c]), 1)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_unknown_environment_blocks_before_query(self):
        self.environment = 'production'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assert_no_database_queries()

    def test_invalid_prefix_blocks_before_query(self):
        self.prefix = 'wp_; DROP TABLE'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assert_no_database_queries()

    def test_production_url_blocks_rsync(self):
        self.options['home'] = 'https://cornerstonefarmomak.com'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_standin_theme_blocks_rsync(self):
        self.options['template'] = 'csf-kadence-standin'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_symlinked_plugin_destination_blocks_rsync(self):
        plugins = self.root / 'wp-content/plugins'
        plugins.mkdir()
        (plugins / 'csf-core').symlink_to(self.root / 'wp-content/themes', target_is_directory=True)
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_cli_version_mismatch_blocks_rsync(self):
        self.php_version = '8.4.24'
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_missing_extension_blocks_rsync(self):
        self.extensions.remove('mysqli')
        report, status = self.inspect()
        self.assertEqual(status, 1, report)
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))

    def test_inspection_without_dry_run_never_runs_rsync(self):
        report, status = self.inspect(dry_run=False)
        self.assertEqual(status, 0, report)
        self.assertEqual(report['dry_run_status'], 'not run')
        self.assertFalse(any(c[0] == 'rsync' for c in self.commands))


if __name__ == '__main__':
    unittest.main()
