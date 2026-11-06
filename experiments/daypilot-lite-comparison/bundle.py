"""Package only the public experiment, fictional artifacts and licensed runtime."""
from pathlib import Path
import hashlib,json,zipfile
root=Path(__file__).resolve().parent
out=root/'.artifacts/DayPilot_Lite_Comparison_2026-11-06.zip'
reviewed=json.loads((root/'evidence/files.sha256.json').read_text())
files={}
for name,digest in reviewed.items():
 p=root/name
 if name.startswith('/')or'..'in Path(name).parts or p.is_symlink()or hashlib.sha256(p.read_bytes()).hexdigest()!=digest:raise RuntimeError('Reviewed file differs')
 files[name]=p
files['evidence/files.sha256.json']=root/'evidence/files.sha256.json'
vendor=json.loads((root/'evidence/vendor-provenance.json').read_text())
for name,record in vendor['files'].items():
 p=root/'node_modules/@daypilot/daypilot-lite-javascript'/name
 if p.is_symlink()or hashlib.sha256(p.read_bytes()).hexdigest()!=record['sha256']:raise RuntimeError('Pinned vendor file differs')
 files[p.relative_to(root).as_posix()]=p
validation=json.loads((root/'evidence/validation.json').read_text())
p=root/'.artifacts/model.json'
if p.is_symlink()or hashlib.sha256(p.read_bytes()).hexdigest()!=validation['generated_model_sha256']:raise RuntimeError('Reviewed fictional model differs')
files[p.relative_to(root).as_posix()]=p
native=json.loads((root/'evidence/native-source.json').read_text())
for p in (root/'.artifacts/native-assets').iterdir():
 if p.is_symlink()or not p.is_file()or hashlib.sha256(p.read_bytes()).hexdigest()!=native['files'].get('assets/'+p.name):raise RuntimeError('Native asset differs')
 files[p.relative_to(root).as_posix()]=p
for name in ('class-csf-scheduling-planner.php','class-csf-scheduling-time.php'):
 p=root.parent.parent/'wp-content/plugins/csf-scheduling/includes'/name
 if p.is_symlink()or hashlib.sha256(p.read_bytes()).hexdigest()!=native['files']['includes/'+name]:raise RuntimeError('Native source differs')
 files['native-source/'+name]=p
with zipfile.ZipFile(out,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9)as z:
 for name,p in sorted(files.items()):
  assert not name.startswith('/')and '..'not in Path(name).parts
  info=zipfile.ZipInfo(name,(2026,11,6,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
  z.writestr(info,p.read_bytes())
with zipfile.ZipFile(out)as z:assert z.testzip()is None
receipt={'artifact':out.name,'bytes':out.stat().st_size,'sha256':hashlib.sha256(out.read_bytes()).hexdigest(),'files':len(files),'scope':'fictional offline experiment only; no farm deployment','rebuild':'python3 bundle.py after generating fixtures and evidence'}
(root/'evidence/bundle.json').write_text(json.dumps(receipt,indent=2)+'\n')
print(json.dumps(receipt))
