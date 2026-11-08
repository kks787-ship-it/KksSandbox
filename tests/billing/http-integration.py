"""Real disposable WordPress/MariaDB and official TOTP over HTTPS; PayPal is mocked."""
import concurrent.futures, os, copy, http.cookiejar, json, pathlib, re, ssl, subprocess, urllib.error, urllib.parse, urllib.request, uuid
from html.parser import HTMLParser
ROOT=pathlib.Path('/tmp/csf-billing-fixture'); BASE='https://127.0.0.1:18443'; CHECKS=0
assert (ROOT/'operator.json').is_file(), 'Initialized isolated fixture required'
PHP=os.environ.get('CSF_BILLING_TEST_PHP',str(ROOT/'php'))
assert PHP in [str(ROOT/'php'),'/tmp/csf-gift-matrix/php8.1','/tmp/csf-gift-matrix/php8.3']
OPERATOR=json.loads((ROOT/'operator.json').read_text())['id']
def cli(code):
 guard='if(DB_NAME!=="csf_gift_fixture"||ABSPATH!=="/tmp/csf-billing-fixture/site/"){throw new RuntimeException("Wrong fixture");}'
 return subprocess.check_output([PHP,'.wp-local/wp-cli.phar','--path='+str(ROOT/'site'),'eval',guard+code],text=True).strip()
def check(ok,label):
 global CHECKS
 assert ok,label
 CHECKS+=1;print('PASS',label,flush=True)
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),urllib.request.HTTPSHandler(context=ssl.create_default_context(cafile=str(ROOT/'cert.pem'))))
 def request(self,url,body=None,headers=None):
  req=urllib.request.Request(urllib.parse.urljoin(BASE,url),data=body,headers=headers or {})
  try:r=self.opener.open(req,timeout=30)
  except urllib.error.HTTPError as error:r=error
  return r.status,r.read().decode(),dict(r.headers),r.url
 def api(self,action,body=None,headers=None):
  status,text,h,url=self.request('/?rest_route=/csf-billing/v1/'+action,json.dumps(body or {}).encode(),{'Origin':BASE,'Content-Type':'application/json',**(headers or {})})
  try:data=json.loads(text)
  except json.JSONDecodeError:raise AssertionError('Non-JSON API reply: '+text[:500])
  return status,data,{k.lower():v for k,v in h.items()}
 def clone(self):
  other=Client()
  for c in self.jar:other.jar.set_cookie(copy.copy(c))
  return other
class Forms(HTMLParser):
 def __init__(self,text):super().__init__();self.forms=[];self.current=None;self.feed(text)
 def handle_starttag(self,tag,attrs):
  attrs=dict(attrs)
  if tag=='form':self.current={'action':attrs.get('action','/wp-login.php'),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and attrs.get('name') and attrs.get('type') not in ['checkbox','radio']:
   self.current['fields'][attrs['name']]=attrs.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
def login(client,user,password):
 _,html,_,_=client.request('/wp-login.php?redirect_to='+urllib.parse.quote(BASE+'/wp-admin/admin.php?page=csf-gift-certificates',safe=''))
 form=next(f for f in Forms(html).forms if 'log' in f['fields']);form['fields'].update(log=user,pwd=password)
 return client.request(form['action'],urllib.parse.urlencode(form['fields']).encode(),{'Content-Type':'application/x-www-form-urlencoded','Origin':BASE})
def reset_rate():cli('global $wpdb;$wpdb->query("DELETE FROM gift_fixture_csf_billing_throttle");')
cli(f'$u=get_userdata({OPERATOR});if($u->user_login!=="gift_operator"){{throw new RuntimeException("Wrong synthetic actor");}}$u->add_cap("csf_billing_redeem");')
reset_rate();customer=Client();status,session,headers=customer.api('session');check(status==200,'real schema/session endpoint')
csrf={'X-CSF-CSRF':session['csrf']};cookie=next(c for c in customer.jar if c.name=='__Host-csf-gift-session');check(cookie.secure and cookie.path=='/' and 'HttpOnly' in cookie._rest and cookie._rest.get('SameSite')=='Lax','secure HttpOnly cookie attributes')
check('no-store' in headers.get('cache-control',''),'private responses are uncached')
check(customer.api('session',headers={'Origin':'https://attacker.invalid'})[0]==403,'cross-origin denied')
check(customer.api('prepare')[0]==403,'missing CSRF denied')
p=session['products'][0];terms=session['terms'];body={'command':str(uuid.uuid4()),'product_id':p['id'],'product_revision':p['revision'],'accepted':True,'terms_version':terms['version'],'terms_hash':terms['sha256']}
check(customer.api('prepare',{**body,'terms_hash':'0'*64},csrf)[0]==409,'stale terms rejected')
check(customer.api('prepare',{**body,'accepted':False},csrf)[0]==409,'unchecked consent rejected')
status,prepared,_=customer.api('prepare',body,csrf);check(status==200,'immutable accepted purchase created');order=prepared['order_id']
check(customer.api('prepare',body,csrf)[1]==prepared,'prepare replay stable')
status,created,_=customer.api('create',{'order_id':order,'command':str(uuid.uuid4())},csrf);check(status==200,'sandbox provider order created')
provider=urllib.parse.parse_qs(urllib.parse.urlparse(created['approval_url']).query)['token'][0]
check(created['approval_url'].startswith('https://www.sandbox.paypal.com/checkoutnow?'),'only sandbox approval destination')
check(customer.api('create',{'order_id':order,'command':str(uuid.uuid4())},csrf)[1]==created,'bound order resumes without recreation')
check(customer.api('capture',{'order_id':order,'command':str(uuid.uuid4())},csrf)[0]==409,'unapproved order cannot capture')
customer.request('/?gift_fixture_approve='+provider)
status,receipt,_=customer.api('capture',{'order_id':order,'command':str(uuid.uuid4())},csrf);check(status==200 and receipt['purchased']==2 and receipt['redeemed']==0,'verified mocked capture issues against real InnoDB')
check(bool(re.fullmatch(r'CSF-[A-F0-9]{32}',receipt['certificate_number'])),'unique random certificate number')
check(customer.api('capture',{'order_id':order,'command':str(uuid.uuid4())},csrf)[1]['certificate_id']==receipt['certificate_id'],'capture retry issues no second certificate')
stranger=Client();other=stranger.api('session')[1];result=stranger.api('receipt',{'order_id':order},{'X-CSF-CSRF':other['csrf']});check(result[0]==409 and receipt['certificate_number'] not in json.dumps(result[1]),'wrong session cannot read private certificate')
check(customer.api('list')[0]==403,'anonymous management denied')
admin=Client();status,html,_,_=login(admin,'fixture_admin','synthetic-local-admin-only');check('data-mode="admin"' not in html,'technical administrator has no implicit finance access')
staff=Client();status,html,_,_=login(staff,'gift_operator','synthetic-operator-only');forms=Forms(html).forms;challenge=next((f for f in forms if 'authcode' in f['fields']),None);check(challenge is not None,'official TOTP challenge required')
code=cli(f'$p=Two_Factor_Totp::get_instance();echo Two_Factor_Totp::calc_totp($p->get_user_totp_key({OPERATOR}),floor(time()/30));')
challenge['fields']['authcode']=code
status,html,_,_=staff.request(challenge['action'],urllib.parse.urlencode(challenge['fields']).encode(),{'Content-Type':'application/x-www-form-urlencoded','Origin':BASE})
check('data-mode="admin"' in html,'real password and TOTP authorize private management page')
nonce=re.search(r'data-nonce="([A-Za-z0-9]+)"',html).group(1);auth={'X-WP-Nonce':nonce}
detail=staff.api('detail',{'order_id':order},auth);check(detail[0]==200,'cookie MFA and REST nonce authorize details');check(len(detail[1]['history'])>=6,'UUID-linked audit history is populated in real WordPress')
check(staff.api('detail',{'order_id':order})[0]==403,'missing REST nonce denies staff request')
command=str(uuid.uuid4());ride=str(uuid.uuid4());debit={'command':command,'certificate_id':receipt['certificate_id'],'rides':1,'revision':1,'ride_reference':ride}
status,result,_=staff.api('redeem',debit,auth);check(status==200 and result['remaining']==1,'real atomic redemption')
check(staff.api('redeem',debit,auth)[1]==result,'same redemption command replays once')
check(staff.api('redeem',{**debit,'command':str(uuid.uuid4()),'revision':2},auth)[0]==409,'new command cannot debit same ride reference')
correction={'command':str(uuid.uuid4()),'certificate_id':receipt['certificate_id'],'redemption_command':command,'revision':2,'reason':'mistaken_ride'}
(ROOT/'audit-failure').write_text('synthetic fault')
try:check(staff.api('correct',correction,auth)[0]==409,'injected audit failure rejects correction')
finally:(ROOT/'audit-failure').unlink()
current=staff.api('detail',{'order_id':order},auth)[1];check(current['redeemed']==1 and current['revision']==2,'audit failure rolled back balance and revision')
status,result,_=staff.api('correct',correction,auth);check(status==200 and result['remaining']==2,'correction after rollback commits once')
check(staff.api('correct',{**correction,'command':str(uuid.uuid4()),'revision':3},auth)[0]==409,'second correction rejected')
(ROOT/'race-delay').write_text('synthetic concurrency hold')
try:
 def race(lane):return staff.clone().api('redeem',{'command':str(uuid.uuid4()),'certificate_id':receipt['certificate_id'],'rides':2,'revision':3,'ride_reference':str(uuid.uuid4())},{**auth,'X-Fixture-Lane':'B' if lane else 'A'})
 with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(race,range(2)))
finally:(ROOT/'race-delay').unlink()
check(sorted(r[0] for r in results)==[200,409],'two concurrent last-balance commands yield one winner')
check(len({r[2].get('x-fixture-worker') for r in results})==2,'race executed on two separate PHP workers')
check(staff.api('detail',{'order_id':order},auth)[1]['redeemed']==2,'race never overspends purchased rides')
# Lost-create response: exact original merchant order, private authorization and stable retry.
reset_rate();lost_body={**body,'command':str(uuid.uuid4())};lost_id=customer.api('prepare',lost_body,csrf)[1]['order_id']
(ROOT/'create-response-lost').write_text('synthetic timeout')
try:check(customer.api('create',{'order_id':lost_id,'command':str(uuid.uuid4())},csrf)[0]==409,'lost create response remains held')
finally:(ROOT/'create-response-lost').unlink()
lost=staff.api('detail',{'order_id':lost_id},auth)[1];check(lost['status']=='create_uncertain','lost create claim persisted in real DB')
lost_provider=cli(f'$s=get_option("gift_fixture_provider");foreach($s as $k=>$v){{if($v["purchase_units"][0]["custom_id"]==="{lost_id}"){{echo $k;}}}}')
recovery={'command':str(uuid.uuid4()),'order_id':lost_id,'provider_order_id':provider,'revision':lost['order_revision']}
check(staff.api('recover',recovery,auth)[0]==409,'another purchase cannot be linked')
customer.request('/?gift_fixture_approve='+lost_provider)
recovery['provider_order_id']=lost_provider
linked=staff.api('recover',recovery,auth);check(linked[0]==200,'private lost-create recovery verifies and links merchant order')
check(staff.api('recover',recovery,auth)[1]==linked[1],'lost-create linking exact retry remains stable')
check(customer.api('capture',{'command':str(uuid.uuid4()),'order_id':lost_id},csrf)[1]['purchased']==2,'recovered order issues only after verified completion')
# Independent completion can precede local approval; two HTTP workers must issue once.
reset_rate();external_id=customer.api('prepare',{**body,'command':str(uuid.uuid4())},csrf)[1]['order_id']
external=customer.api('create',{'order_id':external_id,'command':str(uuid.uuid4())},csrf)[1]
external_provider=urllib.parse.parse_qs(urllib.parse.urlparse(external['approval_url']).query)['token'][0]
customer.request('/?gift_fixture_approve='+external_provider)
cli(f'(new CSF_Billing_PayPal())->capture("{external_provider}","{uuid.uuid4()}");')
def issuance_race(lane):return customer.clone().api('capture',{'order_id':external_id,'command':str(uuid.uuid4())},{**csrf,'X-Fixture-Lane':'B' if lane else 'A'})
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:issued=list(pool.map(issuance_race,range(2)))
check(all(r[0]==200 for r in issued),'concurrent verified completion reconciles pending local approval')
check(len({r[1]['certificate_id'] for r in issued})==1,'same capture issues exactly one certificate across workers')
check(len({r[2].get('x-fixture-worker') for r in issued})==2,'issuance race uses distinct PHP workers')
cli(f'get_userdata({OPERATOR})->remove_cap("csf_billing_redeem");')
check(staff.api('redeem',debit,auth)[0]==403,'revoked grant blocks replay')
event={'id':'SYNTHETIC-REFUND-'+order,'event_type':'PAYMENT.CAPTURE.REFUNDED','resource':{'supplementary_data':{'related_ids':{'capture_id':'CAP'+provider}}}}
hook={'paypal-auth-algo':'SHA256withRSA','paypal-cert-url':'https://api.sandbox.paypal.com/v1/notifications/certs/SYNTHETIC-CERT','paypal-transmission-id':'synthetic-id','paypal-transmission-sig':'synthetic-valid-signature','paypal-transmission-time':'2026-11-08T00:00:00Z'}
check(customer.api('webhook',event,{**hook,'paypal-transmission-sig':'invalid'})[0]==503,'unverified provider event rejected')
check(customer.api('webhook',event,hook)[0]==200,'verified mock refund event accepted')
frozen=customer.api('receipt',{'order_id':order},csrf)[1];check(frozen['status']=='frozen','refund freezes real credit ledger')
check(customer.api('webhook',event,hook)[0]==200,'same provider event safely replays')
check(customer.api('receipt',{'order_id':order},csrf)[1]['revision']==frozen['revision'],'event replay does not mutate revision')
late={'id':'SYNTHETIC-LATE-'+order,'event_type':'PAYMENT.CAPTURE.COMPLETED','resource':{'id':'CAP'+provider}}
check(customer.api('webhook',late,hook)[0]==503,'late completion cannot revive frozen credits')
# Only expire this synthetic proof. Do not reset MFA credentials or replace provider verification.
cli(f'$s=get_user_meta({OPERATOR},"session_tokens",true);foreach($s as &$v){{if(isset($v[CSF_MFA_Session::KEY])){{$p=$v[CSF_MFA_Session::KEY];$p["issued"]=time()-120;$p["expires"]=time()-60;unset($p["signature"]);$p["signature"]=hash_hmac("sha256",wp_json_encode($p),wp_salt("auth"));$v[CSF_MFA_Session::KEY]=$p;}}}}update_user_meta({OPERATOR},"session_tokens",$s);')
check(staff.api('detail',{'order_id':order},auth)[0]==403,'expired real MFA proof denies reads')
(ROOT/'http-evidence.json').write_text(json.dumps({'assertions':CHECKS,'order_id':order,'certificate_id':receipt['certificate_id'],'php':cli('echo PHP_VERSION;'),'wordpress':cli('echo get_bloginfo("version");'),'database':cli('global $wpdb;echo $wpdb->get_var("SELECT VERSION()");'),'provider':'explicit mocked PayPal transport; official MFA real','browser':'not used; HTTP integration only'},indent=2))
print('REAL_HTTP_DATABASE_MFA_ASSERTIONS='+str(CHECKS))
