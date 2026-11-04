"""SELECT-only mail configuration indicators; never bootstrap WordPress or send mail."""
import json
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
