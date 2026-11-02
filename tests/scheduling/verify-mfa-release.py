#!/usr/bin/env python3
"""Verify the actual pinned vendor ZIP and prepare an ignored, disposable test distribution."""
import hashlib
import json
import pathlib
import stat
import sys
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[2]
ZIP_SHA256 = '59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473'
archive = pathlib.Path(sys.argv[1])
assert archive.stat().st_size == 91837, 'Unexpected vendor artifact size'
assert hashlib.sha256(archive.read_bytes()).hexdigest() == ZIP_SHA256, 'Unreviewed vendor artifact'
expected = json.loads((ROOT / 'wp-content/plugins/csf-mfa/provider-files.json').read_text())['files']
files = {}
with zipfile.ZipFile(archive) as package:
    for member in package.infolist():
        path = pathlib.PurePosixPath(member.filename)
        assert not path.is_absolute() and '..' not in path.parts and chr(92) not in member.filename
        assert path.parts[0] == 'two-factor' and not stat.S_ISLNK(member.external_attr >> 16)
        assert not member.flag_bits & 1 and member.file_size <= 512000
        if member.is_dir():
            continue
        name = '/'.join(path.parts[1:])
        assert name not in files, 'Duplicate artifact member'
        files[name] = package.read(member)
assert set(files) == set(expected), 'Extra or missing provider files'
assert sum(map(len, files.values())) <= 1000000
for name, data in files.items():
    assert hashlib.sha256(data).hexdigest() == expected[name], 'Provider file mismatch: ' + name
destination = ROOT / '.wp-local/mfa-official/two-factor'
if destination.exists():
    assert {p.relative_to(destination).as_posix() for p in destination.rglob('*') if p.is_file()} == set(files)
for name, data in files.items():
    target = destination / name
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
print('OFFICIAL_ZIP_VERIFIED size=91837 sha256=' + ZIP_SHA256 + ' files=' + str(len(files)))
