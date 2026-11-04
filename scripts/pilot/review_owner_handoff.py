"""Classify reference completion only; never return private values or grant authority."""
import json
import re

ROLES = ('technical_master', 'operations', 'head_trainer')
FIELDS = {
    'controller': r'controller(?: reference| confirmed)?',
    'recovery_custodian': r'recovery custodian(?: reference)?',
    'witnessed_login': r'witnessed native login(?: date/verifier)?(?: reference)?',
    'device_custodian': r'(?:factor/device|authenticator/device|device|factor) custodian',
    'code_custodian': r'recovery[- ]code custody(?: reference)?',
}
SECRET_KEYS = {'password', 'otp', 'totp_seed', 'totp_secret', 'recovery_codes', 'backup_codes', 'authenticator_seed'}


def supplied(value):
    if not isinstance(value, str):
        return False
    value = value.strip().strip(' .')
    if not value or re.search(r'__+|\b(?:pending|unknown|undecided|unverified|not yet|not supplied|placeholder)\b', value, re.I):
        return False
    return value.lower() not in ('none', 'n/a', 'tbd') and not (value.startswith('<') and value.endswith('>'))


def choice(value):
    if not isinstance(value, str):
        return 'unresolved'
    value = value.strip().strip(' .').lower().replace('_', ' ')
    return {'different humans': 'different_humans', 'different people': 'different_humans',
            'same human': 'same_human', 'same person separate accounts': 'same_human'}.get(value, 'unresolved')


def text_field(text, pattern):
    match = re.search(r'(?:^|[;\n])\s*' + pattern + r'\s*(?::|=|\s)\s*([^;\n]*)', text, re.I)
    return match[1] if match else ''


def review(raw):
    if not isinstance(raw, bytes) or len(raw) > 65536:
        raise ValueError('Bounded input required')
    text = raw.decode('utf-8').strip('\ufeff \r\n\t')
    if not text.strip() or any(ord(c) < 32 and c not in '\n\r\t' for c in text):
        raise ValueError('Invalid input')
    fenced = re.fullmatch(r'```(?:json|text)?[ \t]*\n(.*?)\n```', text, re.S | re.I)
    if fenced: text = fenced[1].strip()
    if '\n' not in text and '\\n' in text: text = text.replace('\\n', '\n')
    # Normalize only the published role labels, never infer roles from personal names.
    text = re.sub(r'(?im)^[ \t#>*-]*(?:\*\*)?(Technical Master|Operations|Head Trainer)(?:\*\*)?[ \t]*(?::|$)', lambda match: match[1] + ':', text)
    structure = {'known_role_labels_present': bool(re.search(r'Technical Master|Operations|Head Trainer', text, re.I)),
                 'known_reference_labels_present': bool(re.search(r'controller reference|recovery custodian|witnessed native login', text, re.I)),
                 'unresolved_markers_present': bool(re.search(r'__+|\b(?:pending|undecided|unverified)\b', text, re.I)),
                 'artifact_reference_only': '\n' not in text and bool(re.fullmatch(r'\S+\.(?:md|txt|json)', text, re.I)),
                 'code_fence_removed': bool(fenced)}
    result = {'format': 'plain_text', 'structure': structure, 'roles': {r: {f: False for f in FIELDS} for r in ROLES},
              'human_independence_choice': 'unresolved', 'maintenance_reference_supplied': False,
              'mail_review_reference_supplied': False, 'independent_evidence_verified': False,
              'execution_authorized': False}
    if text.lstrip().startswith('{'):
        value = json.loads(text)
        if not isinstance(value, dict):
            raise ValueError('Invalid structure')
        def no_secrets(node):
            if isinstance(node, dict):
                for k, v in node.items():
                    if k.lower().replace(' ', '_') in SECRET_KEYS and v:
                        raise ValueError('Authentication material not accepted')
                    no_secrets(v)
            elif isinstance(node, list):
                for item in node: no_secrets(item)
        no_secrets(value)
        accounts = value.get('accounts', {})
        if not isinstance(accounts, dict): raise ValueError('Invalid accounts')
        aliases = {'controller': ('controller_ref', 'controller'), 'recovery_custodian': ('recovery_ref', 'recovery_custodian'),
                   'witnessed_login': ('witnessed_login_ref', 'witnessed_login'), 'device_custodian': ('device_custodian_ref', 'device_custodian'),
                   'code_custodian': ('code_custodian_ref', 'code_custodian')}
        for role in ROLES:
            record = accounts.get(role, {})
            if not isinstance(record, dict): continue
            for field, keys in aliases.items(): result['roles'][role][field] = any(supplied(record.get(k)) for k in keys)
        result.update(format='json', human_independence_choice=choice(value.get('human_independence')),
                      maintenance_reference_supplied=supplied(value.get('update_custodian_ref')),
                      mail_review_reference_supplied=supplied(value.get('mail_review_ref')))
    else:
        if re.search(r'^\s*(?:password|otp|totp[_ ](?:seed|secret)|authenticator[_ ]seed|recovery[_ ]codes|backup[_ ]codes)\s*[:=]\s*\S', text, re.I | re.M):
            raise ValueError('Authentication material not accepted')
        headings = list(re.finditer(r'^\s*(Technical Master|Operations|Head Trainer)(?:\s*\([^\n)]*\))?\s*:', text, re.I | re.M))
        for i, heading in enumerate(headings):
            role = {'technical master': 'technical_master', 'operations': 'operations', 'head trainer': 'head_trainer'}[heading[1].lower()]
            block = text[heading.end():headings[i + 1].start() if i + 1 < len(headings) else len(text)]
            for field, pattern in FIELDS.items(): result['roles'][role][field] = supplied(text_field(block, pattern))
        result['human_independence_choice'] = choice(text_field(text, r'Technical operator and initial farm owner'))
        result['maintenance_reference_supplied'] = supplied(text_field(text, r'Maintenance/recovery custodian reference'))
        result['mail_review_reference_supplied'] = supplied(text_field(text, r'(?:Mail review|Sender/transport review) reference'))
    result['state'] = 'references-reviewed-not-authenticated'
    return result
