"""Read-only existing-account mapping. Stored private input never becomes public output.

Uses the existing exact-target recovery DB library; no WordPress bootstrap, cookies,
account/grant writes, MFA setup, password retrieval or login/SSO action.
"""
import json
import re
import sys
import time

PEOPLE=('technical_master','operations','head_trainer')

class Blocked(Exception):pass

def require(ok,code='INPUT_INVALID'):
    if not ok:raise Blocked(code)

def inputs(value):
    require(isinstance(value,dict) and set(value)=={'version','purpose','accounts','human_independence','update_custodian_ref','native_security_mail'})
    require(value['version']==1 and value['purpose']=='calendar-identity-review-only')
    require(value['human_independence'] in ('different_people','same_person_separate_accounts','undecided'))
    require(value['native_security_mail'] in ('hold','approve_scope_later'))
    require(isinstance(value['update_custodian_ref'],str) and len(value['update_custodian_ref'])<=256 and not any(ord(c)<32 or ord(c)==127 for c in value['update_custodian_ref']))
    require(isinstance(value['accounts'],dict) and set(value['accounts'])==set(PEOPLE))
    for person in PEOPLE:
        account=value['accounts'][person]
        require(isinstance(account,dict) and set(account)=={'login','controller_ref','recovery_ref'})
        require(isinstance(account['login'],str) and 0<len(account['login'].encode('utf-8'))<=60)
        for key in ('login','controller_ref','recovery_ref'):
            require(isinstance(account[key],str) and len(account[key])<=256 and not any(ord(c)<32 or ord(c)==127 for c in account[key]))
    require(len({value['accounts'][p]['login'] for p in PEOPLE})==3,'DUPLICATE_MAPPING')
    return value

def capabilities(raw):
    """Conservative flat boolean PHP capability array; no unserialize/eval/object support."""
    require(isinstance(raw,str),'ROLE_FORMAT_UNKNOWN')
    header=re.fullmatch(r'a:(\d+):\{(.*)\}',raw,re.S);require(header is not None,'ROLE_FORMAT_UNKNOWN')
    body=header[2];offset=0;out={}
    while offset<len(body):
        match=re.match(r's:(\d+):"([a-z_][a-z0-9_]{0,127})";b:([01]);',body[offset:])
        require(match is not None,'ROLE_FORMAT_UNKNOWN');name=match[2]
        require(int(match[1])==len(name.encode()) and name not in out,'ROLE_FORMAT_UNKNOWN')
        out[name]=match[3]=='1';offset+=match.end()
    require(len(out)==int(header[1]),'ROLE_FORMAT_UNKNOWN');return out

def verify(db,prefix,value):
    value=inputs(value);require(re.fullmatch('[A-Za-z0-9_]+',prefix) is not None,'TARGET_INVALID')
    ids=[];extra=False
    for person in PEOPLE:
        account=value['accounts'][person];encoded=account['login'].encode('utf-8').hex()
        # Exact binary login match. Private values remain only in server memory/client stdin.
        sql="SELECT JSON_OBJECT('id',u.ID,'roles',(SELECT meta_value FROM `"+prefix+"usermeta` m WHERE m.user_id=u.ID AND m.meta_key='"+prefix+"capabilities')) FROM `"+prefix+"users` u WHERE BINARY u.user_login=BINARY CONVERT(0x"+encoded+" USING utf8mb4);"
        raw=db.query(sql);rows=[json.loads(line) for line in raw.splitlines()]
        require(len(rows)==1,'MAPPING_NOT_UNIQUE');row=rows[0]
        require(type(row['id']) is int and row['id']>0,'MAPPING_NOT_UNIQUE');ids.append(row['id'])
        roles=capabilities(row['roles']);expected='administrator' if person=='technical_master' else 'subscriber'
        require(roles.get(expected) is True and not (person!='technical_master' and roles.get('administrator') is True),'NATIVE_ROLE_MISMATCH')
        extra=extra or bool(set(roles)-{expected})
    require(len(set(ids))==3,'DUPLICATE_MAPPING')
    # References are supplied, not independently verified human-control/recovery evidence.
    refs=all(value['accounts'][p]['controller_ref'] and value['accounts'][p]['recovery_ref'] for p in PEOPLE)
    return {'state':'mapping-matched','distinct_existing_accounts':True,'expected_native_roles':True,'additional_capabilities_need_review':extra,'private_references_supplied':bool(refs),'human_independence_choice_supplied':value['human_independence']!='undecided','controllers_independently_verified':False,'mfa_or_recovery_verified':False,'execution_authorized':False,'writes':False}

def main(b):
    try:
        raw=sys.stdin.buffer.read(8193);require(len(raw)<=8192)
        value=inputs(json.loads(raw));budget=b.Budget(time.time()+55,55)
        b.require(b.ROOT.resolve(strict=True)==b.ROOT)
        config=b.read_regular(b.ROOT/'wp-config.php',16*1024**2,budget)[0]
        db=b.NativeDB(b.validate_config(config,credentials=True),budget);db.guard(None)
        result=verify(db,b.PREFIX,value);code=0
    except Blocked as error:result={'state':'blocked','code':str(error),'writes':False,'execution_authorized':False};code=2
    except Exception:result={'state':'blocked','code':'PRIVATE_REVIEW_UNAVAILABLE','writes':False,'execution_authorized':False};code=2
    print(json.dumps(result,sort_keys=True));return code
