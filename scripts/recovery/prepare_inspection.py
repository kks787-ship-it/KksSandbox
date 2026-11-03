"""Generate validated non-secret inspection parameters locally; never contacts a server."""
import argparse
import datetime as dt
import json
import re
import secrets


def prepare(actor_ref,approval_ref,now=None):
    for value in (actor_ref,approval_ref):
        if not isinstance(value,str) or not re.fullmatch('[A-Za-z0-9_.:-]{1,128}',value):
            raise ValueError('Invalid non-secret reference')
    now=now or dt.datetime.now(dt.timezone.utc)
    if now.tzinfo is None:raise ValueError('UTC-aware time required')
    now=now.astimezone(dt.timezone.utc).replace(microsecond=0)
    result={'actor_ref':actor_ref,'approval_ref':approval_ref,'start':now.strftime('%Y-%m-%dT%H:%M:%SZ'),
            'end':(now+dt.timedelta(seconds=120)).strftime('%Y-%m-%dT%H:%M:%SZ'),'run_token':secrets.token_hex(16)}
    if re.fullmatch('[a-f0-9]{32}',result['run_token']) is None:raise ValueError('Invalid generated token')
    return result

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--actor-ref',required=True);parser.add_argument('--approval-ref',required=True)
    args=parser.parse_args();print(json.dumps(prepare(args.actor_ref,args.approval_ref),sort_keys=True))
