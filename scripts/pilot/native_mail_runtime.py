"""Private PHP-FPM mail runtime tooling. Preview never calls wp_mail; dispatch is held."""
import base64
import json
import os
from pathlib import Path
import pwd
import re
import signal
import socket
import stat
import struct
import tempfile
import time
import sqlite3


def runtime_result(value):
    if not isinstance(value, dict): raise ValueError('Unexpected runtime response')
    state = value.get('state')
    if state == 'native-mail-runtime-qualified':
        expected = {'state','binding_fingerprint','distinct_recipient_count','native_sender_qualified','unexpected_mail_hooks','blocked_bootstrap_mail','mail_sent','execution_authorized'}
        if set(value) != expected or re.fullmatch('[a-f0-9]{64}', value.get('binding_fingerprint','')) is None: raise ValueError('Unexpected runtime response')
        if type(value['distinct_recipient_count']) is not int or not 1 <= value['distinct_recipient_count'] <= 2: raise ValueError('Unexpected runtime response')
        if value['native_sender_qualified'] is not True or value['unexpected_mail_hooks'] is not False or value['mail_sent'] is not False or value['execution_authorized'] is not False or value['blocked_bootstrap_mail'] != 0: raise ValueError('Unexpected runtime response')
    elif state in ('native-mail-submission-results','blocked'):
        expected = {'state','results','receipt_confirmed'} if state != 'blocked' else {'state','code','stage','unexpected_hook','results','receipt_confirmed'}
        if set(value) != expected or value['receipt_confirmed'] is not False: raise ValueError('Unexpected runtime response')
        if state == 'blocked' and (value['code'] != 'NATIVE_MAIL_RUNTIME_GUARD' or value['stage'] not in ('runtime_identity','mail_hook_registry','native_bindings','dispatch_authorization') or value['unexpected_hook'] not in (None,'wp_mail','pre_wp_mail','wp_mail_from','wp_mail_from_name','phpmailer_init','wp_mail_succeeded','wp_mail_failed','wp_mail_content_type','wp_mail_charset')): raise ValueError('Unexpected runtime response')
        if not isinstance(value['results'],list) or len(value['results']) > 2: raise ValueError('Unexpected runtime response')
        for row in value['results']:
            if set(row) != {'message_reference','state'} or re.fullmatch('[a-f0-9]{32}',row['message_reference']) is None or row['state'] not in ('submission_accepted','outcome_unknown'): raise ValueError('Unexpected runtime response')
    else: raise ValueError('Unexpected runtime response')
    return value


def fcgi_record(kind, content=b''):
    if len(content) > 65535: raise ValueError('Record limit')
    return struct.pack('!BBHHBB', 1, kind, 1, len(content), 0, 0) + content


def fcgi_length(n):
    return bytes([n]) if n < 128 else struct.pack('!I', n | 0x80000000)


def fcgi_params(values):
    raw = b''
    for key, value in values.items():
        k, v = key.encode(), value.encode()
        raw += fcgi_length(len(k)) + fcgi_length(len(v)) + k + v
    if len(raw) > 32768: raise ValueError('Parameter limit')
    return raw


def fcgi_response(connection):
    def read(n):
        out = b''
        while len(out) < n:
            part = connection.recv(n - len(out))
            if not part: raise ValueError('Incomplete response')
            out += part
        return out
    stdout, stderr = b'', b''
    for _ in range(512):
        version, kind, request, size, padding, _ = struct.unpack('!BBHHBB', read(8))
        if version != 1 or request != 1: raise ValueError('Unexpected response')
        content = read(size)
        read(padding)
        if kind == 6: stdout += content
        elif kind == 7: stderr += content
        elif kind == 3:
            if len(content) != 8 or content[:5] != b'\0' * 5: raise ValueError('Runtime failed')
            combined = stdout + stderr
            if b'open_basedir restriction' in combined: raise ValueError('FPM open-basedir boundary')
            if b'Primary script unknown' in combined or b'File not found.' in combined: raise ValueError('FPM script unavailable')
            if b'Access denied' in combined: raise ValueError('FPM script access denied')
            body = stdout.split(b'\r\n\r\n', 1)
            if len(body) != 2: raise ValueError('Invalid response framing')
            return runtime_result(json.loads(body[1]))
        else: raise ValueError('Unexpected record')
        if len(stdout) > 1024**2 or len(stderr) > 65536: raise ValueError('Output limit')
    raise ValueError('Response record limit')


def pool_target(text, domain, conf_parent):
    if not re.search(r'^\s*\[' + re.escape(domain) + r'\]\s*$', text, re.M): raise ValueError('Wrong pool')
    values = {}
    for key in ('user', 'listen'):
        rows = re.findall(r'^\s*' + key + r'\s*=\s*([^\s;]+)\s*$', text, re.M)
        if len(rows) != 1: raise ValueError('Ambiguous pool')
        value = rows[0]
        if len(value) >= 2 and value[0] == value[-1] and value[0] in '\"\'': value = value[1:-1]
        values[key] = value
    path = Path(values['listen'])
    if not path.is_absolute() and values['listen'].endswith('.sock'):
        prefixes = re.findall(r'^\s*prefix\s*=\s*([^\s;]+)\s*$', text, re.M)
        if len(prefixes) != 1: raise ValueError('Relative socket prefix unavailable')
        prefix = prefixes[0]
        if len(prefix) >= 2 and prefix[0] == prefix[-1] and prefix[0] in '\"\'': prefix = prefix[1:-1]
        prefix = prefix.replace('$pool', domain)
        if '$' in prefix: raise ValueError('Relative socket prefix unavailable')
        base = Path(prefix)
        if not base.is_absolute() or base.resolve(strict=True) != conf_parent: raise ValueError('Socket scope')
        path = base / path
    if path.is_absolute():
        if path.resolve(strict=True) != path or path.parent != conf_parent or not stat.S_ISSOCK(path.lstat().st_mode):
            raise ValueError('Socket scope')
        listener = (socket.AF_UNIX, str(path))
    else:
        match = re.fullmatch(r'(127\.0\.0\.1|\[::1\]):([0-9]{1,5})', values['listen'])
        if match is None or not 1 <= int(match[2]) <= 65535:
            form = 'bare_port' if values['listen'].isdigit() else 'localhost_name' if values['listen'].startswith('localhost:') else 'wildcard' if values['listen'].startswith(('0.0.0.0:', '[::]:')) else 'relative_socket' if values['listen'].endswith('.sock') else 'unsupported_literal'
            raise ValueError('Unqualified listener: ' + form)
        listener = (socket.AF_INET6, ('::1', int(match[2]))) if match[1] == '[::1]' else (socket.AF_INET, ('127.0.0.1', int(match[2])))
    user = pwd.getpwnam(values['user'])
    if user.pw_uid == 0: raise ValueError('Root pool refused')
    return listener, user.pw_uid


def php_program(template, parameters):
    if parameters['mode'] not in ('preview', 'dispatch'): raise ValueError('Unknown mode')
    if template.count('__PARAMETERS__') != 1: raise ValueError('Template')
    return template.replace('__PARAMETERS__', base64.b64encode(json.dumps(parameters).encode()).decode()).encode()


def preview(b, inspector, template, backup, progress=None, dispatch_parameters=None):
    """Requires existing recovery qualification; only temporary code artifacts are created."""
    progress = progress if progress is not None else {}
    progress.update(stage='recovery_binding', temporary_code_created=False)
    budget = b.Budget(time.time() + 50, 50)
    if set(backup) != {'bundle_id', 'receipt_sha256', 'manifest_sha256'} or re.fullmatch(r'csf-gate-b-backup-[a-f0-9]{32}', backup['bundle_id']) is None:
        raise ValueError('Recovery binding')
    bundle = b.PARENT / backup['bundle_id']
    progress['stage'] = 'recovery_integrity'
    b.verify_receipt(bundle, backup['receipt_sha256'])
    progress['stage'] = 'recovery_freshness'
    receipt = json.loads(b.read_regular(bundle / 'receipt.json', 16384, budget)[0])
    b.require(receipt['manifest_sha256'] == backup['manifest_sha256'] and 0 <= time.time() - b.utc(receipt['ended']) < 86400)
    progress['stage'] = 'target_root'
    b.require(b.ROOT.resolve(strict=True) == b.ROOT)
    config = b.read_regular(b.ROOT / 'wp-config.php', 16 * 1024**2, budget)[0]
    progress['stage'] = 'database_target'
    b.NativeDB(b.validate_config(config, credentials=True), budget).guard(None)
    progress['stage'] = 'pool_configuration'
    b.require(inspector.POOL.resolve(strict=True) == inspector.POOL)
    pool = b.read_regular(inspector.POOL, 1024**2, budget)[0].decode()
    progress['stage'] = 'pool_socket_scope'
    listener, uid = pool_target(pool, inspector.DOMAIN, inspector.CONF.parent)
    progress['stage'] = 'private_probe_parent'
    b.require(b.PARENT.resolve(strict=True) == b.PARENT and b.PARENT.is_relative_to(b.ROOT.parent))
    owner = pwd.getpwuid(uid)
    groups = os.getgrouplist(owner.pw_name, owner.pw_gid)
    parent_stat = b.PARENT.stat()
    allowed = bool((parent_stat.st_uid == uid and parent_stat.st_mode & stat.S_IXUSR) or
                   (parent_stat.st_gid in groups and parent_stat.st_mode & stat.S_IXGRP) or parent_stat.st_mode & stat.S_IXOTH)
    if not allowed: raise ValueError('Private probe parent not traversable')
    progress['stage'] = 'temporary_code'
    directory = Path(tempfile.mkdtemp(prefix='dr-native-mail-probe-', dir=str(b.PARENT)))
    progress['temporary_code_created'] = True
    path = directory / 'probe.php'
    result = {'state': 'blocked', 'code': 'NATIVE_RUNTIME_PROBE_UNAVAILABLE'}
    try:
        parameters = {'root': str(b.ROOT), 'url': b.URL, 'uid': uid, 'mode': 'preview'}
        if dispatch_parameters is not None: parameters.update(dispatch_parameters)
        fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
        try:
            with os.fdopen(fd, 'wb', closefd=False) as stream: stream.write(php_program(template, parameters))
            os.fchown(fd, uid, -1)
        finally: os.close(fd)
        os.chown(directory, uid, -1)
        progress['stage'] = 'fastcgi_request'
        with socket.socket(listener[0], socket.SOCK_STREAM) as connection:
            connection.settimeout(min(35, budget.remaining()))
            connection.connect(listener[1])
            values = {'GATEWAY_INTERFACE': 'CGI/1.1', 'REQUEST_METHOD': 'GET', 'SCRIPT_FILENAME': str(path),
                      'SCRIPT_NAME': '/__private_native_mail_probe.php', 'REQUEST_URI': '/__private_native_mail_probe.php',
                      'SERVER_NAME': inspector.DOMAIN, 'HTTP_HOST': inspector.DOMAIN, 'SERVER_PORT': '443',
                      'SERVER_PROTOCOL': 'HTTP/1.1', 'HTTPS': 'on', 'REMOTE_ADDR': '127.0.0.1',
                      'DOCUMENT_ROOT': str(b.ROOT), 'CONTENT_LENGTH': '0'}
            connection.sendall(fcgi_record(1, struct.pack('!HB5x', 1, 0)) + fcgi_record(4, fcgi_params(values)) + fcgi_record(4) + fcgi_record(5))
            result = fcgi_response(connection)
    except Exception as error:
        result = {'state': 'blocked', 'code': 'NATIVE_FPM_PREVIEW_UNAVAILABLE', 'stage': progress['stage']}
        if dispatch_parameters is None: result['mail_sent'] = False
        else: result['submission_state'] = 'unknown'
        if isinstance(error, ValueError) and str(error) in ('FPM open-basedir boundary','FPM script unavailable','FPM script access denied','Invalid response framing','Runtime failed','Incomplete response'):
            result['diagnostic'] = str(error)
    finally:
        try:
            if path.exists(): path.unlink()
            directory.rmdir()
            result['temporary_code_removed'] = True
        except Exception:
            result = {'state': 'blocked', 'code': 'TEMPORARY_CODE_CLEANUP_REQUIRED', 'temporary_code_removed': False}
            if dispatch_parameters is None: result['mail_sent'] = False
            else: result['submission_state'] = 'unknown'
    return result


def main(b, inspector, template, backup):
    def stop(*args): raise TimeoutError('Runtime limit')
    signal.signal(signal.SIGTERM, stop)
    signal.signal(signal.SIGALRM, stop)
    signal.setitimer(signal.ITIMER_REAL, 50)
    progress = {'stage': 'input', 'temporary_code_created': False}
    try:
        if __import__('sys').stdin.buffer.read(1): raise ValueError('No input accepted')
        result = preview(b, inspector, template, backup, progress)
        code = 0 if result.get('state') == 'native-mail-runtime-qualified' else 2
    except Exception as error:
        result, code = {'state': 'blocked', 'code': 'NATIVE_RUNTIME_PROBE_UNAVAILABLE', 'stage': progress['stage'],
                        'temporary_code_created': progress['temporary_code_created'],
                        'temporary_code_removed': not progress['temporary_code_created'], 'mail_sent': False}, 2
        if isinstance(error, ValueError) and str(error) in ('Wrong pool', 'Ambiguous pool', 'Socket scope', 'Root pool refused', 'Private probe parent not traversable', 'Relative socket prefix unavailable', 'Unqualified listener: bare_port', 'Unqualified listener: localhost_name', 'Unqualified listener: wildcard', 'Unqualified listener: relative_socket', 'Unqualified listener: unsupported_literal'):
            result['diagnostic'] = str(error)
    finally: signal.setitimer(signal.ITIMER_REAL, 0)
    print(json.dumps(result, sort_keys=True))
    return code


def consent_window(b, value):
    if not isinstance(value,dict) or set(value) != {'authorization','approval_reference','start','end'}:
        raise ValueError('Exact dispatch consent required')
    if value['authorization'] != 'one-native-security-mail-test' or not isinstance(value['approval_reference'],str) or re.fullmatch('[A-Za-z0-9_.:-]{1,128}',value['approval_reference']) is None:
        raise ValueError('Exact dispatch consent required')
    start,end=b.utc(value['start']),b.utc(value['end'])
    if not start <= time.time() < end or not 0 < end-start <= 3600: raise ValueError('Dispatch window')
    return end


def dispatch(b, inspector, template, backup, snapshot, test_ref, ledger_class, consent):
    deadline = consent_window(b,consent)
    if re.fullmatch('[a-f0-9]{32}',test_ref) is None or set(snapshot) != {'binding_fingerprint','distinct_recipient_count','queue_intervals'}:
        raise ValueError('Reviewed dispatch binding required')
    if re.fullmatch('[a-f0-9]{64}',snapshot['binding_fingerprint']) is None or type(snapshot['distinct_recipient_count']) is not int or not 1 <= snapshot['distinct_recipient_count'] <= 2:
        raise ValueError('Reviewed dispatch binding required')
    b.require(b.PARENT.resolve(strict=True)==b.PARENT)
    if b.QUEUE_LIMITS() != snapshot['queue_intervals']: raise ValueError('Native queue policy changed')
    directory=b.PARENT/('csf-native-mail-test-'+test_ref)
    # An existing attempt is NEVER a reason to submit another message, even without a receipt.
    if directory.exists() or directory.is_symlink():
        return {'state':'existing-attempt-no-resubmission','test_reference':test_ref,'receipt_confirmed':False}
    # Verify recovery freshness BEFORE creating durable claims or any native runtime request.
    if set(backup) != {'bundle_id','receipt_sha256','manifest_sha256'} or re.fullmatch('csf-gate-b-backup-[a-f0-9]{32}',backup['bundle_id']) is None:
        raise ValueError('Recovery binding')
    bundle=b.PARENT/backup['bundle_id']
    b.verify_receipt(bundle,backup['receipt_sha256'])
    budget=b.Budget(min(deadline,time.time()+100),100)
    receipt=json.loads(b.read_regular(bundle/'receipt.json',16384,budget)[0])
    b.require(receipt['manifest_sha256']==backup['manifest_sha256'] and 0 <= time.time()-b.utc(receipt['ended']) < 86400)
    directory.mkdir(mode=0o700)
    file=directory/'submission.sqlite'
    fd=os.open(file,os.O_CREAT|os.O_EXCL|os.O_WRONLY|os.O_NOFOLLOW,0o600);os.close(fd)
    connection=sqlite3.connect(file,timeout=3)
    refs=[b.sha((test_ref+':'+str(i)).encode())[:32] for i in range(snapshot['distinct_recipient_count'])]
    recipient_refs=[b.sha((snapshot['binding_fingerprint']+':'+str(i)).encode()) for i in range(len(refs))]
    result={'state':'outcome_unknown','test_reference':test_ref,'receipt_confirmed':False}
    try:
        ledger=ledger_class(connection)
        for recipient_ref,message_ref in zip(recipient_refs,refs):
            if not ledger.claim(test_ref,recipient_ref,message_ref): raise ValueError('Existing claim')
        parameters={'mode':'dispatch','dispatch_authorized':True,'approval_reference':consent['approval_reference'],
                    'expected_binding':snapshot['binding_fingerprint'],'deadline':int(min(deadline,time.time()+90)),
                    'message_references':refs}
        outcome=preview(b,inspector,template,backup,dispatch_parameters=parameters)
        observed={row['message_reference']:row['state'] for row in outcome.get('results',[])}
        if not set(observed).issubset(set(refs)): raise ValueError('Unexpected submission reference')
        for recipient_ref,message_ref in zip(recipient_refs,refs):
            ledger.finish(test_ref,recipient_ref,observed.get(message_ref,'outcome_unknown'))
        result={'state':'submission_accepted' if len(observed)==len(refs) and all(v=='submission_accepted' for v in observed.values()) else 'outcome_unknown',
                'test_reference':test_ref,'message_references':refs,'initial_slots_claimed':len(refs),
                'runtime_state':outcome.get('state'),'temporary_code_removed':outcome.get('temporary_code_removed',False),'receipt_confirmed':False}
    finally:
        connection.close()
        # Sanitized durable result survives lost responses; contains no addresses or credentials.
        b.private_write(directory/'result.json',b.canonical(result))
    return result


def dispatch_main(b, inspector, template, backup, snapshot, test_ref, ledger_class):
    os.umask(0o077)
    def stop(*args): raise TimeoutError('Submission deadline')
    signal.signal(signal.SIGTERM,stop);signal.signal(signal.SIGALRM,stop);signal.setitimer(signal.ITIMER_REAL,110)
    try:
        raw=__import__('sys').stdin.buffer.read(8193)
        if len(raw)>8192:raise ValueError('Bounded consent required')
        consent=json.loads(raw)
        if backup is None:
            if not isinstance(consent,dict) or set(consent) != {'authorization','approval_reference','start','end','recovery'}:
                raise ValueError('Fresh runtime recovery binding required')
            backup=consent.pop('recovery')
        result=dispatch(b,inspector,template,backup,snapshot,test_ref,ledger_class,consent)
        code=0 if result['state'] in ('submission_accepted','existing-attempt-no-resubmission') else 2
    except Exception:
        result,code={'state':'blocked-or-unknown','code':'DISPATCH_RECONCILIATION_REQUIRED','receipt_confirmed':False},2
    finally:signal.setitimer(signal.ITIMER_REAL,0)
    print(json.dumps(result,sort_keys=True));return code
