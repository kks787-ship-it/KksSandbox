#!/usr/bin/env python3
"""Fetch only hash-pinned public test sources into the ignored local fixture directory.

This prepares a source-built test fixture, not the official vendor ZIP and not an installer.
Honors the inherited proxy and TLS policy. Never runs against a WordPress installation.
"""
import hashlib
import json
import pathlib
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[2]
manifest = json.loads((ROOT / 'wp-content/plugins/csf-mfa/provider-files.json').read_text())
destination = ROOT / '.wp-local/mfa-provider/two-factor'
for name, expected in manifest['files'].items():
    target = destination / name
    if target.is_file() and hashlib.sha256(target.read_bytes()).hexdigest() == expected:
        continue
    if name == 'includes/qrcode-generator/qrcode.js':
        url = 'https://raw.githubusercontent.com/kazuhikoarase/qrcode-generator/83b7e8fe3fddd3b0368dbafd6ce56995bd25e3c8/js/dist/qrcode.js'
    else:
        url = 'https://raw.githubusercontent.com/WordPress/two-factor/' + manifest['source_commit'] + '/' + name
    with urllib.request.urlopen(url, timeout=30) as response:
        data = response.read()
    if hashlib.sha256(data).hexdigest() != expected:
        raise RuntimeError('Unreviewed provider source: ' + name)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
print('Verified', len(manifest['files']), 'pinned local provider files; no installation performed.')
