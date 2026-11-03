"""One local-only capture candidate. Execution requires a separate owner approval.

No Gateway SDK, external transfer, restore, installation, account or application writes.
Private source discovery is retained only inside the approved new recovery set.
"""
import json
import os
from pathlib import Path
import re
import signal
import sys
import time

# One release = one destination. Existing/partial bundles never overwritten or retried.
CANDIDATE_TOKEN='0ae84798d9b1c7a26a6df8370b3415ae'
AUTHORIZATION='one-local-backup-capture'


def run(b,inspector,params,root=None,parent=None,discovery=None,db_factory=None):
    b.require(set(params)=={'actor_ref','approval_ref','start','end','authorization'})
    b.require(params['authorization']==AUTHORIZATION)
    for key in ('actor_ref','approval_ref'):
        b.require(isinstance(params[key],str) and re.fullmatch('[A-Za-z0-9_.:-]{1,128}',params[key]))
    start,end=b.utc(params['start']),b.utc(params['end'])
    b.require(start<=time.time()<end and 0<end-start<=1800)
    budget=b.Budget(end,1770);root=root or b.ROOT;parent=parent or b.PARENT
    b.require(root.resolve(strict=True)==root and parent.resolve(strict=True)==parent)
    # Never touch a pre-existing attempt, even if it has no receipt.
    bundle=parent/('csf-gate-b-backup-'+CANDIDATE_TOKEN)
    b.require(not bundle.exists() and not bundle.is_symlink())
    raw,_=b.read_regular(root/'wp-config.php',16*1024**2,budget)
    secret=b.validate_config(raw,credentials=True)
    try:
        db=(db_factory or b.NativeDB)(secret,budget)
        discover=discovery or (lambda:inspector.discover(b,budget,root,include_private=True))
        mapping=discover()
        b.require(set(mapping)=={'files','bytes','map_sha256','http_auth_verified','pool_file_verified','private_files','private_symlinks'})
        b.require(mapping['http_auth_verified'] is True and mapping['pool_file_verified'] is True)
        protected=[item for item in mapping['private_files'] if not Path(item['path']).is_relative_to(root)]
        # Verify compatibility, zero stored objects and exact12-table InnoDB source phase first.
        db.versions();guard=db.guard(None);b.load_generator()(db.catalog())
        value={**{k:params[k] for k in ('actor_ref','approval_ref','start','end')},'mode':'capture',
               'source_pin':b.IMPLEMENTATION,'run_token':CANDIDATE_TOKEN,'custody':'local-only',
               'config_reviewed':True,'wp_config_sha256':b.sha(raw),'plugins_sha256':b.sha(guard['plugins'].encode()),
               'protected':protected,'db_secret':secret}
        b.bindings(value,'capture')
        def scope_stable():
            # Revalidate canonical references, contents and alias topology, not just old files.
            return b.canonical(discover())==b.canonical(mapping)
        receipt=b.capture(value,db,budget,root,parent,
                          protected_metadata={'symlinks':mapping['private_symlinks'],'map_sha256':mapping['map_sha256']},
                          scope_check=scope_stable)
        if receipt['state']!='complete':return {'state':receipt['state'],'code':'CAPTURE_REVIEW_REQUIRED' if receipt['state']=='review-required' else 'CAPTURE_INCOMPLETE'}
        budget.check()
        private_receipt,_=b.read_regular(bundle/'receipt.json',16384,budget)
        receipt_digest=b.sha(private_receipt)
        b.verify_receipt(bundle,receipt_digest);budget.check()
        return {'state':'complete','code':'LOCAL_CAPTURE_VERIFIED','bundle_id':bundle.name,
                'manifest_sha256':receipt['manifest_sha256'],'receipt_sha256':receipt_digest,
                'offserver_verified':False,'restore_verified':False}
    finally:secret.clear()


def main(b,inspector):
    os.umask(0o077)
    def stop(*_):raise b.Stop('interrupted')
    signal.signal(signal.SIGALRM,stop);signal.signal(signal.SIGTERM,stop);signal.signal(signal.SIGINT,stop)
    signal.setitimer(signal.ITIMER_REAL,1770)
    try:
        data=sys.stdin.buffer.read(16385);b.require(len(data)<=16384)
        receipt=run(b,inspector,json.loads(data))
        status=0 if receipt.get('state')=='complete' else 2
    except (Exception,KeyboardInterrupt):
        receipt={'state':'blocked','code':'CAPTURE_NOT_ACCEPTED'};status=2
    finally:signal.setitimer(signal.ITIMER_REAL,0)
    print(json.dumps(receipt,sort_keys=True));return status
