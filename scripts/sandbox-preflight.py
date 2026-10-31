#!/usr/bin/env python3
"""Exact sandbox inspection and optional rsync dry run; no WordPress bootstrap/apply.

Run as the development subscription user. Requires existing WP-CLI, PHP, MySQL client
and (for --dry-run) rsync. Ordinary plugins, themes and wp-settings.php are not loaded;
WP-CLI db query still evaluates wp-config.php, so review custom configuration code.
"""
import argparse
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys

URL = 'https://sandbox.cornerstonefarmomak.com'
DATABASE = 'wp_mdf4x'
ROOT_NAME = 'sandbox.cornerstonefarmomak.com'


class StopInspection(Exception):
    """A failed prerequisite; never continue to a different target."""


def run(args):
    try:
        result = subprocess.run(args, capture_output=True, text=True, check=False, timeout=60)
    except subprocess.TimeoutExpired as error:
        raise StopInspection(f'{args[0]} command timed out; no further checks attempted') from error
    if result.returncode:
        # Raw PHP/WP-CLI diagnostics can include private configuration. Do not echo them.
        raise StopInspection(f'{args[0]} command failed (exit {result.returncode}); review diagnostics locally without sharing secrets')
    return result.stdout.strip()


def inspect(root_arg, dry_run=False):
    report = {'expected_url': URL, 'expected_database': DATABASE,
              'inspection_status': 'incomplete', 'dry_run_status': 'not run', 'checks': {}}
    try:
        candidate = Path(root_arg).expanduser().absolute()
        root = candidate.resolve(strict=True)
        report['absolute_document_root'] = str(root)
        if not root.is_dir() or root.name != ROOT_NAME:
            raise StopInspection('Canonical directory must be the owner-confirmed sandbox document root; do not substitute httpdocs or production')
        if candidate != root:
            raise StopInspection('Document-root path resolves through a symlink; review the canonical path first')
        config = root / 'wp-config.php'
        if not config.is_file() or config.is_symlink():
            raise StopInspection('Require a regular wp-config.php inside the sandbox root; no parent/linked configuration fallback')
        if not shutil.which('wp') or not shutil.which('php'):
            raise StopInspection('WP-CLI and the selected domain PHP handler must already be available')
        wp = ['wp', '--path=' + str(root), '--skip-plugins', '--skip-themes']

        def config_get(name, kind='constant'):
            return run(wp + ['config', 'get', name, '--type=' + kind])

        database = config_get('DB_NAME')
        report['checks']['configured_database'] = database
        if database != DATABASE:
            raise StopInspection('DB_NAME mismatch: no database query or rsync attempted')
        prefix = config_get('table_prefix', 'variable')
        if not re.fullmatch(r'[A-Za-z0-9_]+', prefix):
            raise StopInspection('Unsupported table prefix; no query attempted')
        report['checks']['table_prefix'] = prefix
        environment = config_get('WP_ENVIRONMENT_TYPE')
        report['checks']['declared_environment'] = environment
        if environment not in ('development', 'staging'):
            raise StopInspection('WP_ENVIRONMENT_TYPE must explicitly declare development or staging; do not change it during inspection')

        def query(sql):
            # db query runs before wp-settings.php, not via wp eval/option/theme commands.
            return run(wp + ['db', 'query', 'START TRANSACTION READ ONLY; ' + sql + '; COMMIT;', '--skip-column-names', '--batch'])

        connected = query('SELECT DATABASE()')
        report['checks']['connected_database'] = connected
        if connected != DATABASE:
            raise StopInspection('Connected database mismatch: no options or other schemas queried')
        report['checks']['database_version'] = query('SELECT VERSION()')
        rows = query(f"SELECT option_name, option_value FROM `{DATABASE}`.`{prefix}options` WHERE option_name IN ('home','siteurl','template','stylesheet','blog_public','permalink_structure') ORDER BY option_name")
        options = {}
        for line in rows.splitlines():
            key, separator, value = line.partition('\t')
            if not separator or key in options:
                raise StopInspection('Unexpected or duplicate option rows')
            options[key] = value
        report['checks']['database_options'] = options
        for key in ('home', 'siteurl'):
            if options.get(key) != URL:
                raise StopInspection(f'{key} must exactly match the sandbox HTTPS URL')
        if options.get('template') != 'kadence':
            raise StopInspection('Real Kadence parent is not active; do not activate it during inspection')
        if options.get('blog_public') != '0':
            raise StopInspection('Development indexing is not disabled; report without changing it')
        if options.get('permalink_structure') != '/%postname%/':
            raise StopInspection('Permalink structure differs from the prepared release; review without changing it')
        for path in (root / 'wp-content', root / 'wp-content/themes', root / 'wp-content/themes/kadence',
                     root / 'wp-content/plugins', root / 'wp-content/plugins/csf-core',
                     root / 'wp-content/plugins/csf-emergency'):
            if path.is_symlink():
                raise StopInspection('Linked content/theme/plugin destination requires review before rsync')
        style = root / 'wp-content/themes/kadence/style.css'
        match = re.search(r'^\s*(?:\*\s*)?Version:\s*([^\r\n]+)', style.read_text()[:8192], re.MULTILINE | re.IGNORECASE)
        report['checks']['kadence_version'] = match.group(1).strip() if match else 'unknown'
        if report['checks']['kadence_version'] != '1.5.2':
            raise StopInspection('Kadence version differs from the owner-confirmed 1.5.2; review the difference')
        report['checks']['wordpress_version'] = run(wp + ['core', 'version'])
        php_code = 'echo json_encode(array("version"=>PHP_VERSION,"extensions"=>get_loaded_extensions()));'
        runtime = json.loads(run(['php', '-r', php_code]))
        report['checks']['cli_php'] = runtime
        if report['checks']['wordpress_version'] != '7.1.4' or runtime['version'] != '8.3.36':
            raise StopInspection('CLI PHP/WordPress version differs from the Plesk-reported target; verify the handler/version before continuing')
        required = {'mysqli', 'json', 'mbstring', 'dom', 'xml', 'curl', 'openssl', 'fileinfo', 'filter', 'hash'}
        loaded = {name.lower() for name in runtime['extensions']}
        missing = sorted(required - loaded)
        if missing or not loaded.intersection({'gd', 'imagick'}):
            raise StopInspection('Required PHP extensions missing; report without installing them')
        report['inspection_status'] = 'identity and CLI prerequisites passed; web-handler/Kadence behavior still unverified'
        if dry_run:
            if not shutil.which('rsync'):
                raise StopInspection('rsync is missing; no package installation authorized on the VPS')
            repo = Path(__file__).resolve().parents[1]
            sources = [(repo / 'wp-content/plugins' / name, root / 'wp-content/plugins' / name)
                       for name in ('csf-core', 'csf-emergency')]
            if not all(source.is_dir() for source, _ in sources):
                raise StopInspection('Reviewed plugin sources must already be in a tooling checkout outside the web root')
            for source, destination in sources:
                if source.resolve() == destination.resolve():
                    raise StopInspection('Source and target plugin directories must be distinct')
            report['dry_run_status'] = 'running'
            report['dry_run_output'] = {}
            for source, destination in sources:
                # --dry-run is mandatory and cannot be overridden by caller flags.
                report['dry_run_output'][source.name] = run(['rsync', '-a', '--delete', '--exclude=.git',
                                                           '--itemize-changes', '--dry-run',
                                                           str(source) + '/', str(destination) + '/'])
            report['dry_run_status'] = 'completed; no plugin files copied/deleted, activation or setup performed'
    except (StopInspection, OSError, ValueError) as error:
        report['blocker'] = str(error)
        return report, 1
    return report, 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('document_root', nargs='?', default='./' + ROOT_NAME)
    parser.add_argument('--dry-run', action='store_true', help='compare reviewed plugin sources using mandatory rsync --dry-run')
    args = parser.parse_args()
    if os.geteuid() == 0:
        parser.error('Run as the development subscription user, not root')
    report, code = inspect(args.document_root, args.dry_run)
    print(json.dumps(report, indent=2))
    return code


if __name__ == '__main__':
    sys.exit(main())
