"""Owner-approved A only: pinned files, native activation, default-off enforcement.

No enrollment secrets, real-user edits, grants, scheduling activation or schema writes.
Uses existing target/recovery library and pool guards. Failures retain private evidence;
never automatically restore over a newer owner change or weaken authentication.
"""
import base64
import hashlib
import io
import json
import os
from pathlib import Path
import re
import shutil
import socket
import stat
import struct
import subprocess
import tempfile
import time
import types
import urllib.request
import zipfile

PHP = '/opt/plesk/php/8.3/bin/php'
PLUGINS = ('two-factor', 'csf-mfa')
VENDOR_URL = 'https://github.com/WordPress/two-factor/releases/download/0.17.0/two-factor.zip'
VENDOR_SHA = '59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473'


def require(ok):
    if not ok: raise ValueError('MFA installation guard')


def enabled_config(raw):
    require(raw.startswith(b'<?php') and b'CSF_MFA_ENABLED' not in raw and b'CSF_MFA_ENFORCE_ADMIN' not in raw)
    return raw[:5] + b"\n// Owner-approved adapter; mandatory administrator enforcement remains off.\ndefine( 'CSF_MFA_ENABLED', true );\n" + raw[5:]


def validate_entries(entries):
    require(isinstance(entries, list) and len(entries) == 25)
    found = set()
    for row in entries:
        require(set(row) == {'path', 'sha256', 'text'})
        path = row['path']
        require(re.fullmatch(r'wp-content/plugins/(two-factor|csf-mfa)/[a-zA-Z0-9_./-]+', path))
        require(all(p not in ('', '.', '..') for p in path.split('/')) and path not in found)
        require(hashlib.sha256(row['text'].encode()).hexdigest() == row['sha256'])
        found.add(path)
    require(sum('/two-factor/' in path for path in found) == 21)
    require(sum('/csf-mfa/' in path for path in found) == 4)
    require(sum(len(row['text'].encode()) for row in entries) < 1024**2)
    require('wp-content/plugins/two-factor/two-factor.php' in found and 'wp-content/plugins/csf-mfa/csf-mfa.php' in found)


def activation_result(value):
    allowed = ({'state':'activated','enforcement':False},
               {'state':'verified','enforcement':False,'provider_inventory_verified':True,'F2_methods_verified':True},
               {'state':'blocked'})
    require(value in allowed)
    return value


def vendor_entries(rows, download=None):
    # Fetch authoritative public bytes at the destination; no private artifact transfer.
    if download is None:
        with urllib.request.urlopen(VENDOR_URL, timeout=25) as response: raw = response.read(91838)
    else: raw = download()
    require(len(raw) == 91837 and hashlib.sha256(raw).hexdigest() == VENDOR_SHA)
    expected = {row['path'].removeprefix('wp-content/plugins/'): row for row in rows if '/two-factor/' in row['path']}
    seen = set()
    with zipfile.ZipFile(io.BytesIO(raw)) as archive:
        for item in archive.infolist():
            require(not item.flag_bits & 1 and not stat.S_ISLNK(item.external_attr >> 16))
            require(not item.filename.startswith('/') and '\\' not in item.filename and '..' not in item.filename.split('/'))
            if item.is_dir(): continue
            require(item.filename in expected and item.filename not in seen and item.file_size < 512000)
            seen.add(item.filename)
            row = expected[item.filename]; data = archive.read(item)
            require(hashlib.sha256(data).hexdigest() == row['sha256'])
            row['text'] = data.decode()
    require(seen == set(expected))
    return rows


def native_request(b, inspector, native, template, operation, before_hash, budget):
    listener, uid = native.pool_target(b.read_regular(inspector.POOL, 1024**2, budget)[0].decode(), inspector.DOMAIN, inspector.CONF.parent)
    directory = Path(tempfile.mkdtemp(prefix='dr-mfa-activation-', dir=b.PARENT))
    path = directory / 'request.php'
    try:
        values = {'root': str(b.ROOT), 'url': b.URL, 'uid': uid, 'operation': operation, 'before_plugins_sha256': before_hash}
        program = template.replace('__PARAMETERS__', base64.b64encode(json.dumps(values).encode()).decode()).encode()
        fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY | os.O_NOFOLLOW, 0o600)
        with os.fdopen(fd, 'wb') as stream: stream.write(program)
        os.chown(path, uid, -1); os.chown(directory, uid, -1)
        params = {'GATEWAY_INTERFACE': 'CGI/1.1', 'REQUEST_METHOD': 'GET', 'SCRIPT_FILENAME': str(path),
                  'SCRIPT_NAME': '/__private_mfa_activation.php', 'REQUEST_URI': '/__private_mfa_activation.php',
                  'SERVER_NAME': inspector.DOMAIN, 'HTTP_HOST': inspector.DOMAIN, 'SERVER_PORT': '443',
                  'SERVER_PROTOCOL': 'HTTP/1.1', 'HTTPS': 'on', 'REMOTE_ADDR': '127.0.0.1',
                  'DOCUMENT_ROOT': str(b.ROOT), 'CONTENT_LENGTH': '0'}
        # Framing is shared; activation has its own strict response schema.
        with socket.socket(listener[0], socket.SOCK_STREAM) as connection:
            connection.settimeout(min(30, budget.remaining())); connection.connect(listener[1])
            connection.sendall(native.fcgi_record(1, struct.pack('!HB5x', 1, 0)) + native.fcgi_record(4, native.fcgi_params(params)) + native.fcgi_record(4) + native.fcgi_record(5))
            parser = types.FunctionType(native.fcgi_response.__code__, {**native.fcgi_response.__globals__, 'runtime_result': activation_result})
            return parser(connection)
    finally:
        path.unlink(missing_ok=True); directory.rmdir()


def run(b, inspector, native, staging, entries, template, binding):
    validate_entries(entries)
    require(set(binding) == {'bundle_id', 'receipt_sha256', 'manifest_sha256', 'authorization'})
    require(binding['authorization'] == 'owner-approved-MFA-A-B')
    require(re.fullmatch('csf-gate-b-backup-[a-f0-9]{32}', binding['bundle_id']))
    budget = b.Budget(time.time() + 240, 240)
    require(b.ROOT.resolve(strict=True) == b.ROOT and b.PARENT.resolve(strict=True) == b.PARENT)
    source = b.PARENT / binding['bundle_id']; b.verify_receipt(source, binding['receipt_sha256'])
    receipt = json.loads(b.read_regular(source / 'receipt.json', 16384, budget)[0])
    require(receipt['manifest_sha256'] == binding['manifest_sha256'] and 0 <= time.time() - b.utc(receipt['ended']) < 3600)
    config, info = b.read_regular(b.ROOT / 'wp-config.php', 16*1024**2, budget)
    replacement = enabled_config(config)
    db = b.NativeDB(b.validate_config(config, credentials=True), budget)
    before = db.guard(None); catalog = b.schema_digest(db.catalog())
    require(all(name + '/' not in before['plugins'] for name in (*PLUGINS, 'csf-scheduling', 'csf-emergency')))
    require((source / 'wp-config.php.recovery').read_bytes() == config)
    require(b.free_bytes(b.PARENT) > 2*1024**3)
    parent = b.ROOT / 'wp-content/plugins'; require(parent.resolve(strict=True) == parent)
    for name in PLUGINS: require(not (parent/name).exists() and not (parent/name).is_symlink())
    require(parent.stat().st_dev == b.PARENT.stat().st_dev == (b.ROOT/'wp-config.php').stat().st_dev)
    for row in entries:
        if row['path'].endswith('.php'):
            result = subprocess.run([PHP, '-n', '-l'], input=row['text'].encode(), capture_output=True, timeout=10)
            require(result.returncode == 0)
    lint = subprocess.run([PHP, '-n', '-l'], input=replacement, capture_output=True, timeout=10)
    require(lint.returncode == 0)
    temp = Path(tempfile.mkdtemp(prefix='dr-mfa-files-', dir=b.PARENT))
    try:
        owner = parent.stat()
        for row in entries:
            relative = Path(row['path']).relative_to('wp-content/plugins')
            path = temp / relative; path.parent.mkdir(parents=True, exist_ok=True)
            with path.open('xb') as output: output.write(row['text'].encode())
            os.chown(path, owner.st_uid, owner.st_gid); os.chmod(path, 0o644)
        for name in PLUGINS:
            expected = {row['path'].split('/'+name+'/', 1)[1]: row for row in entries if '/'+name+'/' in row['path']}
            staging.verify_tree(temp/name, expected)
        for root, dirs, files in os.walk(temp): os.chown(root, owner.st_uid, owner.st_gid); os.chmod(root, 0o755)
        require(db.guard(None) == before and (b.ROOT/'wp-config.php').read_bytes() == config)
        # Publish only absent plugin paths. Never overwrite an independent installation.
        for name in PLUGINS: staging.publish(temp/name, parent/name)
        path = temp/'config.new'
        with path.open('xb') as output: output.write(replacement); output.flush(); os.fsync(output.fileno())
        os.chown(path, info.st_uid, info.st_gid); os.chmod(path, stat.S_IMODE(info.st_mode))
        require((b.ROOT/'wp-config.php').read_bytes() == config)
        os.replace(path, b.ROOT/'wp-config.php')
        activated = native_request(b, inspector, native, template, 'activate', b.sha(before['plugins'].encode()), budget)
        require(activated == {'state':'activated','enforcement':False})
        verified = native_request(b, inspector, native, template, 'verify', '', budget)
        require(verified == {'state':'verified','enforcement':False,'provider_inventory_verified':True,'F2_methods_verified':True})
        require(b.schema_digest(db.catalog()) == catalog)
        for row in entries: require(b.sha((b.ROOT/row['path']).read_bytes()) == row['sha256'])
        return {'state':'MFA_INSTALLED_VERIFIED','files_verified':25,'F2_methods_verified':True,'enforcement':False,'enrollment_performed':False,'schema_unchanged':True,'temporary_code_removed':True}
    finally:
        shutil.rmtree(temp)
