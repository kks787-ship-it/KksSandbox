"""Read-only mail metadata; never bootstrap WordPress, run sendmail or send a message."""
import json
import html
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys
import time

KNOWN_MAILERS = (
    'wp-mail-smtp/wp_mail_smtp.php', 'fluent-smtp/fluent-smtp.php',
    'post-smtp/postman-smtp.php', 'easy-wp-smtp/easy-wp-smtp.php',
    'wp-smtp/wp-smtp.php', 'smtp-mailer/main.php',
)
OPTION_NAMES = ('wp_mail_smtp', 'fluentmail-settings', 'postman_options', 'swpsmtp_options', 'smtp_mailer_options', 'wp_smtp_options')


def inspect(db, prefix, active_plugins):
    # Prefix comes only from the existing exact-target library, never user input.
    if not isinstance(prefix, str) or not prefix or not all(c in 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_' for c in prefix):
        raise ValueError('Target prefix')
    names = ','.join("'" + n + "'" for n in OPTION_NAMES)
    sql = "SELECT JSON_OBJECT('admin_present',COALESCE((SELECT CHAR_LENGTH(option_value)>0 FROM `" + prefix + "options` WHERE option_name='admin_email'),0),'admin_plausible',COALESCE((SELECT option_value REGEXP '^[^[:space:]@]+@[^[:space:]@]+[.][^[:space:]@]+$' FROM `" + prefix + "options` WHERE option_name='admin_email'),0),'known_mailer_option_rows',(SELECT COUNT(*) FROM `" + prefix + "options` WHERE option_name IN (" + names + ")));"
    row = json.loads(db.query(sql))
    if set(row) != {'admin_present', 'admin_plausible', 'known_mailer_option_rows'} or any(type(v) is not int for v in row.values()):
        raise ValueError('Unexpected result')
    if row['admin_present'] not in (0, 1) or row['admin_plausible'] not in (0, 1) or not 0 <= row['known_mailer_option_rows'] <= len(OPTION_NAMES):
        raise ValueError('Unexpected indicators')
    if not isinstance(active_plugins, str):
        raise ValueError('Unexpected plugins')
    return {
        'state': 'configuration-indicators-only',
        'native_admin_address_present': bool(row['admin_present']),
        'native_admin_address_syntax_plausible': bool(row['admin_plausible']),
        'recognized_active_mailer_count': sum(('"' + p + '"') in active_plugins for p in KNOWN_MAILERS),
        'known_mailer_option_rows_present': row['known_mailer_option_rows'] > 0,
        'recipient_ownership_verified': False, 'controller_addresses_reviewed': False,
        'sender_verified': False, 'web_sapi_transport_verified': False,
        'delivery_tested': False, 'writes': False, 'execution_authorized': False,
    }


def main(b):
    try:
        # No private input is required or accepted. No account enumeration or address extraction.
        if sys.stdin.buffer.read(1):
            raise ValueError('No input accepted')
        budget = b.Budget(time.time() + 55, 55)
        b.require(b.ROOT.resolve(strict=True) == b.ROOT)
        config = b.read_regular(b.ROOT / 'wp-config.php', 16 * 1024**2, budget)[0]
        db = b.NativeDB(b.validate_config(config, credentials=True), budget)
        target = db.guard(None)
        result = inspect(db, b.PREFIX, target['plugins'])
        code = 0
    except Exception:
        result = {'state': 'blocked', 'code': 'MAIL_CONFIGURATION_REVIEW_UNAVAILABLE', 'writes': False, 'execution_authorized': False}
        code = 2
    print(json.dumps(result, sort_keys=True))
    return code


def handler_mail_default(info):
    """Extract only sendmail_path from private php-fpm -i output; never return raw info."""
    if len(info) > 2 * 1024**2:
        raise ValueError('Runtime output limit')
    text = re.sub(r'</td>\s*<td[^>]*>', ' => ', info, flags=re.I)
    text = re.sub(r'</tr>', '\n', text, flags=re.I)
    text = html.unescape(re.sub(r'<[^>]*>', '', text))
    values = re.findall(r'^\s*sendmail_path\s*=>\s*(.*?)\s*=>[^\n]*$', text, re.M)
    if len(values) != 1 or not values[0] or len(values[0]) > 4096:
        raise ValueError('Runtime mail default unavailable')
    return values[0]


def pool_transport(text, domain, run=subprocess.run, executable=lambda p: Path(p).is_file() and os.access(p, os.X_OK), default=None):
    """Read a previously verified pool's override; never run its sendmail command."""
    if not re.search(r'^\s*\[' + re.escape(domain) + r'\]\s*$', text, re.M):
        raise ValueError('Pool identity')
    rows = re.findall(r'^\s*php_admin_value\[sendmail_path\]\s*=\s*(.+)$', text, re.M)
    normal = re.findall(r'^\s*php_value\[sendmail_path\]\s*=\s*(.+)$', text, re.M)
    result = {'pool_admin_sendmail_override_present': bool(rows), 'sendmail_executable_present': False,
              'transport_package': 'unresolved', 'queue_capable_transport': None,
              'nonzero_native_queue_lifetime_observed': None, 'effective_sender_verified': False,
              'current_web_pool_mapping_verified': False, 'delivery_tested': False,
              'strict_no_queue_bound_verified': False, 'writes': False,
              'sendmail_setting_source': 'unresolved'}
    if len(rows) > 1 or len(normal) > 1:
        raise ValueError('Ambiguous pool override')
    selected = rows or normal
    result['sendmail_setting_source'] = 'pool_admin_override' if rows else 'pool_value_override' if normal else 'handler_global_default' if default else 'unresolved'
    if not selected and not default:
        return result
    value = (selected[0] if selected else default).strip()
    if len(value) > 4096 or any(c in value for c in '`$|;&<>'):
        raise ValueError('Nonliteral pool command')
    if len(value) >= 2 and value[0] == value[-1] and value[0] in '\"\'':
        value = value[1:-1]
    parts = shlex.split(value)
    if not parts or not Path(parts[0]).is_absolute():
        raise ValueError('Nonliteral binary')
    result['sendmail_executable_present'] = bool(executable(parts[0]))
    response = run(['/usr/bin/dpkg-query', '-S', parts[0]], capture_output=True, timeout=5,
                   env={'PATH': '/usr/sbin:/usr/bin:/bin', 'LC_ALL': 'C'})
    if response.returncode != 0 or len(response.stdout) > 65536:
        return result
    owners = {line.split(': ', 1)[0].split(':', 1)[0] for line in response.stdout.decode('ascii', 'replace').splitlines()}
    recognized = owners & {'postfix', 'sendmail-bin', 'exim4-daemon-light', 'exim4-daemon-heavy', 'msmtp-mta', 'ssmtp'}
    if len(recognized) != 1:
        return result
    package = recognized.pop()
    result['transport_package'] = package
    result['queue_capable_transport'] = package in ('postfix', 'sendmail-bin', 'exim4-daemon-light', 'exim4-daemon-heavy')
    if package == 'postfix':
        response = run(['/usr/sbin/postconf', '-h', 'maximal_queue_lifetime'], capture_output=True, timeout=5,
                       env={'PATH': '/usr/sbin:/usr/bin:/bin', 'LC_ALL': 'C'})
        if response.returncode == 0 and len(response.stdout) <= 128:
            value = response.stdout.strip()
            if re.fullmatch(rb'[0-9]+[smhdw]?', value):
                result['nonzero_native_queue_lifetime_observed'] = int(re.match(rb'[0-9]+', value)[0]) > 0
    return result


def transport_main(b):
    try:
        if sys.stdin.buffer.read(1):
            raise ValueError('No input accepted')
        budget = b.Budget(time.time() + 55, 55)
        b.require(b.ROOT.resolve(strict=True) == b.ROOT)
        config = b.read_regular(b.ROOT / 'wp-config.php', 16 * 1024**2, budget)[0]
        db = b.NativeDB(b.validate_config(config, credentials=True), budget)
        db.guard(None)  # Target identity only; no repeated mail-option or account inspection.
        b.require(b.POOL.resolve(strict=True) == b.POOL)
        raw = b.read_regular(b.POOL, 1024**2, budget)[0]
        text = raw.decode('utf-8')
        b.require(re.search(r'^\s*\[' + re.escape(b.DOMAIN) + r'\]\s*$', text, re.M) is not None)
        default = None
        if not re.search(r'^\s*php_(?:admin_)?value\[sendmail_path\]\s*=', text, re.M):
            # Handler root comes from the already verified pool path, not a guessed config file.
            binary = b.POOL.parent.parent.parent / 'sbin/php-fpm'
            b.require(binary.resolve(strict=True) == binary and os.access(binary, os.X_OK))
            response = subprocess.run([str(binary), '-i'], capture_output=True, timeout=10,
                                      env={'PATH': '/usr/sbin:/usr/bin:/bin', 'LC_ALL': 'C'})
            b.require(response.returncode == 0)
            default = handler_mail_default(response.stdout.decode('utf-8', 'replace'))
        result = pool_transport(text, b.DOMAIN, default=default)
        result.update(state='pool-transport-configuration-only', execution_authorized=False)
        code = 0
    except Exception:
        result = {'state': 'blocked', 'code': 'POOL_TRANSPORT_REVIEW_UNAVAILABLE', 'writes': False, 'execution_authorized': False}
        code = 2
    print(json.dumps(result, sort_keys=True))
    return code
