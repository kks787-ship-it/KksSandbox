#!/usr/bin/env python3
"""Local-only HTTP/privacy checks; synthetic records are deleted in finally."""
import json
from pathlib import Path
import subprocess
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[2]
WP = ['php', str(ROOT / '.wp-local/wp-cli.phar'), '--path=' + str(ROOT / '.wp-local/site'), '--allow-root']
BASE = 'http://localhost:8890'

def wp(*args):
    return subprocess.check_output(WP + list(args), text=True).strip()

def request(path, data=None):
    try:
        response = urllib.request.urlopen(BASE + path, data=data, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read().decode()

checks = 0

def check(ok, name):
    global checks
    if not ok:
        raise AssertionError(name)
    checks += 1
    print('PASS:', name)

if wp('eval', 'echo wp_get_environment_type();') != 'local':
    raise RuntimeError('Requires the disposable local WordPress site')
ids = []
try:
    for approved in ['1', '0']:
        post_id = wp('post', 'create', '--post_type=csf_horse_emergency', '--post_status=publish', '--post_title=Takeover synthetic horse', '--porcelain')
        ids.append(post_id)
        for key, value in {'public_approved': approved, 'microchip': '987654321012345', 'show_microchip': '0', 'handling': '<script>window.bad=true</script>'}.items():
            wp('post', 'meta', 'update', post_id, '_csf_' + key, value)
    token = wp('post', 'meta', 'get', ids[0], '_csf_token')
    hidden = wp('post', 'meta', 'get', ids[1], '_csf_token')
    check(len(token) == 8 and token != hidden, 'distinct eight-character tokens assigned')
    wp('post', 'update', ids[0], '--post_title=Takeover synthetic horse updated')
    check(token == wp('post', 'meta', 'get', ids[0], '_csf_token'), 'token survives record updates')
    status, headers, body = request('/h/' + token)
    check(status == 200 and 'Takeover synthetic horse updated' in body, 'approved profile publicly renders')
    check('987654321012345' not in body, 'microchip hidden without separate approval')
    check('<script>window.bad=true</script>' not in body and '&lt;script&gt;' in body, 'stored markup escaped in public profile')
    check('noindex' in headers.get('X-Robots-Tag', '') and headers.get('Referrer-Policy') == 'no-referrer' and 'no-cache' in headers.get('Cache-Control', ''), 'profile noindex, referrer and cache headers')
    check(request('/h/' + token + '/print')[0] == 200, 'print profile renders')
    hidden_status, _, hidden_body = request('/h/' + hidden)
    unknown_status, _, unknown_body = request('/h/22222222')
    check(hidden_status == unknown_status == 404 and 'Takeover synthetic horse' not in hidden_body, 'unapproved and unknown tags reveal no horse identity')
    check('Found a horse wearing this tag?' in hidden_body and 'Found a horse wearing this tag?' in unknown_body, 'inactive tags share the contact fallback')
    check(request('/wp-json/wp/v2/csf_horse_emergency')[0] == 404, 'no public emergency REST collection')
    check('Takeover synthetic horse' not in request('/?s=Takeover')[2], 'emergency horses excluded from search')
    status, headers, body = request('/emergency/')
    check(status == 200 and 'noindex' in headers.get('X-Robots-Tag', '') and '/h/' + token not in body, 'emergency landing noindex with no default horse directory')
    check(request('/wp-admin/admin-post.php', b'action=csf_emergency_change_status&csf_confirmed=1')[0] == 400, 'anonymous confirmation rejected')
    caps = json.loads(wp('eval', 'echo wp_json_encode(array(get_role("editor")->has_cap("csf_manage_emergency"), get_role("csf_emergency_coordinator")->has_cap("csf_manage_emergency")));'))
    check(caps == [False, True], 'emergency capability assigned to coordinator, absent from editor')
finally:
    for post_id in ids:
        wp('post', 'delete', post_id, '--force')
print(f'{checks} HTTP/privacy checks passed')
