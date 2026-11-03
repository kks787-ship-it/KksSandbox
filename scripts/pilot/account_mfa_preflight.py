"""Read-only, redacted sandbox/Toolkit compatibility facts; no WP bootstrap or SSO.

Receives existing target-bound DB library. Never returns account IDs, names, values,
passwords, tokens, raw CLI output, private paths or another site's inventory.
"""
import json
from pathlib import Path
import re
import subprocess
import time

HELP_ARGS=('/usr/sbin/plesk','ext','wp-toolkit','--help')

def help_features(text):
    return {name:bool(re.search(r'(?<![\w-])--'+flag+r'(?![\w-])',text)) for name,flag in (('cli_wrapper_documented','wp-cli'),('login_operation_documented','login'),('instance_info_documented','info'))}

def toolkit_metadata(run=subprocess.run):
    result={'help_read':False,'help_exit_code':None,'usage_output_observed':False,**help_features('')}
    try:
        p=run(list(HELP_ARGS),stdin=subprocess.DEVNULL,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=20,env={'PATH':'/usr/sbin:/usr/bin:/bin','LC_ALL':'C'})
        result['help_exit_code']=p.returncode
        if len(p.stdout)+len(p.stderr)<=512*1024:
            text=p.stdout.decode('utf-8','replace')+'\n'+p.stderr.decode('utf-8','replace')
            usage=bool(re.search(r'usage:|available commands|--instance-id',text,re.I))
            if p.returncode==0 or usage:
                result.update(help_read=p.returncode==0,usage_output_observed=usage,**help_features(text))
    except (OSError,subprocess.TimeoutExpired):pass
    return result

def run(b):
    budget=b.Budget(time.time()+55,55)
    b.require(b.ROOT.resolve(strict=True)==b.ROOT)
    raw=b.read_regular(b.ROOT/'wp-config.php',16*1024**2,budget)[0]
    db=b.NativeDB(b.validate_config(raw,credentials=True),budget)
    guard=db.guard(None)
    result={'state':'read-only-facts','toolkit':toolkit_metadata(),
            'scheduling_inactive':'csf-scheduling/' not in guard['plugins'],
            'adapter_inactive':'csf-mfa/' not in guard['plugins'],
            'provider_inactive':'two-factor/' not in guard['plugins'],
            'emergency_inactive':'csf-emergency/' not in guard['plugins'],
            'xmlrpc_endpoint_file_present':(b.ROOT/'xmlrpc.php').is_file(),
            'authenticated_sso_tested':False,'xmlrpc_authentication_tested':False,
            'account_identities_verified':False,'writes_performed':False}
    # Aggregate only; no credential hashes, account identifiers or user metadata values read.
    query="SELECT COUNT(*) FROM `"+b.PREFIX+"usermeta` WHERE meta_key='_application_passwords' AND meta_value NOT IN ('','a:0:{}');"
    count=db.query(query).strip();b.require(re.fullmatch(rb'\d+',count) is not None)
    result['nonempty_application_password_metadata_rows']=int(count)
    query="SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,LENGTH('"+b.PREFIX+"csf_sched_'))='"+b.PREFIX+"csf_sched_';"
    count=db.query(query).strip();b.require(re.fullmatch(rb'\d+',count) is not None)
    result['scheduling_tables']=int(count)
    return result

def main(b):
    try:result=run(b);code=0
    except Exception:result={'state':'blocked','code':'READ_ONLY_PREFLIGHT_BLOCKED','writes_performed':False};code=2
    print(json.dumps(result,sort_keys=True));return code
