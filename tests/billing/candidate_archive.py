"""Bounded synthetic archive candidate; not the qualified farm bundle contract."""
import gzip,hashlib,io,pathlib,tarfile
MAX_BYTES=512*1024*1024

def restore_archive(archive_path,destination,manifest):
 destination=pathlib.Path(destination)
 if destination.exists() or destination.is_symlink():raise ValueError('destination_exists')
 with gzip.open(archive_path,'rb') as compressed:
  payload=compressed.read(MAX_BYTES+1)
 if len(payload)>MAX_BYTES:raise ValueError('expanded_archive_limit')
 with tarfile.open(fileobj=io.BytesIO(payload),mode='r:') as archive:
  entries=archive.getmembers()
  if len(entries)>10000:raise ValueError('member_limit')
  seen=set();found={};total=0
  for item in entries:
   path=pathlib.PurePosixPath(item.name)
   if path.is_absolute() or not path.parts or path.parts[0]!='site' or '..' in path.parts or not(item.isdir() or item.isfile()):raise ValueError('unsafe_member')
   key=str(path)
   if key in seen:raise ValueError('duplicate_member')
   seen.add(key)
   if item.isfile():
    total+=item.size
    if total>MAX_BYTES or item.size<0:raise ValueError('size_limit')
    relative=str(pathlib.PurePosixPath(*path.parts[1:]))
    with archive.extractfile(item) as stream:found[relative]=hashlib.file_digest(stream,'sha256').hexdigest()
  if found!=manifest:raise ValueError('manifest_mismatch')
  # All members/hashes are checked before any destination write. No overwrite/reuse.
  destination.mkdir(mode=0o700)
  for item in entries:
   path=pathlib.PurePosixPath(item.name);target=destination.joinpath(*path.parts[1:])
   if item.isdir():target.mkdir(parents=True,exist_ok=True,mode=0o700)
   else:
    target.parent.mkdir(parents=True,exist_ok=True,mode=0o700)
    with archive.extractfile(item) as source,target.open('xb') as output:
     import shutil
     shutil.copyfileobj(source,output)
    target.chmod(0o600)

def failure_checks(work):
 work=pathlib.Path(work);checks=0;manifest={'one':hashlib.sha256(b'ok').hexdigest()}
 def make(entries):
  stream=io.BytesIO()
  with tarfile.open(fileobj=stream,mode='w:gz') as archive:
   for name,kind,data in entries:
    entry=tarfile.TarInfo(name);entry.type=kind;entry.size=len(data) if kind==tarfile.REGTYPE else 0
    archive.addfile(entry,io.BytesIO(data) if entry.isfile() else None)
  return stream.getvalue()
 good=make([('site/one',tarfile.REGTYPE,b'ok')]);bad_cases=[
  make([('site/one',tarfile.REGTYPE,b'changed')]),
  make([('site/one',tarfile.REGTYPE,b'ok'),('site/extra',tarfile.REGTYPE,b'bad')]),
  make([('site/../outside',tarfile.REGTYPE,b'ok')]),
  make([('/site/one',tarfile.REGTYPE,b'ok')]),
  make([('site/one',tarfile.SYMTYPE,b'')]),
  make([('site/one',tarfile.REGTYPE,b'ok'),('site/one',tarfile.REGTYPE,b'ok')]),good[:25]]
 for i,data in enumerate(bad_cases):
  path=work/('invalid-'+str(i)+'.tar.gz');path.write_bytes(data);dest=work/('invalid-dest-'+str(i))
  try:restore_archive(path,dest,manifest)
  except (ValueError,tarfile.TarError,EOFError):pass
  else:raise AssertionError('Invalid archive accepted')
  assert not dest.exists();checks+=1
 path=work/'valid.tar.gz';path.write_bytes(good);dest=work/'valid-dest';restore_archive(path,dest,manifest)
 assert (dest/'one').read_bytes()==b'ok';checks+=1
 (dest/'one').write_bytes(b'newer')
 try:restore_archive(path,dest,manifest)
 except ValueError:pass
 else:raise AssertionError('Destination reused')
 assert (dest/'one').read_bytes()==b'newer';checks+=1
 return checks
