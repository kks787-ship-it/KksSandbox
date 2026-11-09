"""Actual WordPress REST over loopback TLS, with real official TOTP and closed checkout.
The TLS terminator and temporary overlays are confined to the disposable restored fixture.
"""
import time
import http.client,http.server,http.cookiejar,json,pathlib,secrets,ssl,threading,urllib.request,urllib.error,urllib.parse,uuid

def management_rest_checks(site,work,run,evalphp,Forms):
 site=pathlib.Path(site);work=pathlib.Path(work);config=site/'wp-config.php';router=site/'fixture-router.php';overlay=site/'wp-content/mu-plugins/management-rest-prefix.php'
 assert str(site)=='/tmp/csf-combined-restored' and not overlay.exists()
 saved=config.read_text();saved_router=router.read_text();proof=secrets.token_hex(32);base='https://127.0.0.1:18891';checks=0;token=secrets.token_hex(8)
 cert=work/'tls-cert.pem';key=work/'tls-key.pem'
 run(['/usr/bin/openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1'])
 key.chmod(0o600);cert.chmod(0o600)
 class Proxy(http.server.BaseHTTPRequestHandler):
  def log_message(self,*args):pass
  def proxy(self):
   length=int(self.headers.get('Content-Length','0'))
   if length>16384:self.send_error(413);return
   body=self.rfile.read(length) if length else None
   headers={k:v for k,v in self.headers.items() if k.lower() not in ['host','connection','x-csf-fixture-tls']};headers['Host']='127.0.0.1:18891';headers['X-CSF-Fixture-TLS']=proof
   connection=http.client.HTTPConnection('127.0.0.1',18890,timeout=30)
   try:
    connection.request(self.command,self.path,body,headers);response=connection.getresponse();data=response.read();self.send_response(response.status)
    for k,v in response.getheaders():
     if k.lower() not in ['connection','transfer-encoding','content-length']:self.send_header(k,v)
    self.send_header('Content-Length',str(len(data)));self.end_headers();self.wfile.write(data)
   finally:connection.close()
  do_GET=proxy;do_POST=proxy
 server=http.server.ThreadingHTTPServer(('127.0.0.1',18891),Proxy);context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(cert,key);server.socket=context.wrap_socket(server.socket,server_side=True)
 thread=threading.Thread(target=server.serve_forever,daemon=True)
 client_context=ssl.create_default_context(cafile=str(cert))
 def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),urllib.request.HTTPSHandler(context=client_context))
 browser=client();anonymous=client()
 def request(client,path,data=None,headers=None):
  req=urllib.request.Request(base+path,data,headers or {})
  try:response=client.open(req,timeout=30)
  except urllib.error.HTTPError as e:response=e
  return response.status,response.read().decode(),dict(response.headers)
 def api(action,body=None,nonce=None,who=None,origin=base):
  headers={'Content-Type':'application/json','Origin':origin}
  if nonce is not None:headers['X-WP-Nonce']=nonce
  status,text,h=request(who or browser,'/?rest_route=/csf-billing/v1/'+action,json.dumps(body or {}).encode(),headers)
  return status,json.loads(text),h
 def check(ok,label):
  nonlocal checks
  assert ok,label;checks+=1
 def action_path(url):
  parsed=urllib.parse.urlparse(url);return parsed.path+('?' + parsed.query if parsed.query else '')
 def configure(enabled):
  addition="define('WP_HOME','"+base+"');define('WP_SITEURL','"+base+"');define('CSF_BILLING_WEBHOOK_QUALIFIED',true);"
  if enabled:addition+="define('CSF_BILLING_MANAGEMENT_ENABLED',true);"
  config.write_text(saved.replace('<?php','<?php '+addition,1))
 seeded=False;started=False
 try:
  router.write_text(saved_router.replace('<?php',"<?php if(($_SERVER['REMOTE_ADDR']??'')==='127.0.0.1'&&hash_equals('"+proof+"',$_SERVER['HTTP_X_CSF_FIXTURE_TLS']??'')){$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']='18891';}",1))
  configure(False);thread.start();started=True
  private=['list','detail','redeem','correct','recover','cash'];public=['session','prepare','create','capture','receipt','webhook']
  for action in private+public:check(api(action,who=anonymous)[0]==404,'both gates off: '+action+' absent')
  configure(True)
  for action in public:check(api(action,who=anonymous)[0]==404,'management cannot expose public '+action)
  check(api('list',who=anonymous)[0]==403,'anonymous management rejected rather than 404')
  _,html,_=request(browser,'/wp-login.php');form=next(f for f in Forms(html).forms if 'log' in f['fields']);form['fields'].update(log='synthetic_admin',pwd='synthetic-only-password',redirect_to=base+'/wp-admin/')
  _,html,_=request(browser,action_path(form['action']),urllib.parse.urlencode(form['fields']).encode(),{'Content-Type':'application/x-www-form-urlencoded'})
  form=next(f for f in Forms(html).forms if 'authcode' in f['fields'])
  check(api('list',nonce='invalid')[0]==403,'password without TOTP cannot manage')
  # Honor official TOTP replay protection after the preceding restored-session test.
  time.sleep(31-time.time()%30)
  form['fields']['authcode']=evalphp(site,"$p=Two_Factor_Totp::get_instance();echo Two_Factor_Totp::calc_totp($p->get_user_totp_key(1),floor(time()/30));")
  request(browser,action_path(form['action']),urllib.parse.urlencode(form['fields']).encode(),{'Content-Type':'application/x-www-form-urlencoded'})
  _,text,_=request(browser,'/fixture-proof');proof_state=json.loads(text);check(proof_state['verified'] is True,'real TLS password/TOTP proof');nonce=proof_state['nonce']
  def fixture(action,**body):
   status,text,_=request(browser,'/fixture-money',json.dumps(dict(token=token,action=action,**body)).encode(),{'Content-Type':'application/json','X-WP-Nonce':nonce});result=json.loads(text);assert status==200 and result['ok'],result;return result['result']
  ids=fixture('setup');seeded=True;id=ids['workflow'];order=fixture('state',id=id)['order_id']
  # Only billing tables use this fresh prefix; WordPress identities/nonces remain real.
  overlay.write_text("<?php if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||DB_NAME!=='billing_restore'||ABSPATH!=='/tmp/csf-combined-restored/'){return;}if(str_contains($_SERVER['REQUEST_URI']??'','rest_route=/csf-billing/v1/')){global $wpdb;$wpdb->prefix='money_probe_"+token+"_';}")
  # Existing restored tree belongs to www-data; the temporary nonsecret overlay must be readable.
  overlay.chmod(0o644)
  check(api('list',nonce=nonce)[0]==200,'real private REST list works with checkout disabled')
  status,detail,headers=api('detail',{'order_id':order},nonce);check(status==200 and detail['value']['paid_minor']==101 and 'no-store' in headers.get('Cache-Control',''),'real private detail returns uncached balance')
  check(api('list',nonce='invalid')[0]==403,'bad REST nonce denied');check(api('list',nonce=nonce,origin='https://attacker.invalid')[0]==403,'wrong origin denied')
  req=str(uuid.uuid4());command=str(uuid.uuid4());body={'command':command,'certificate_id':id,'revision':1,'transition':'reserve','request':req,'amount_minor':101,'evidence':str(uuid.uuid4()),'attested':True}
  status,result,_=api('cash',body,nonce);check(status==200 and result['pending']['amount_minor']==101 and result['payment_executed'] is False,'real REST cash reserve works with sales closed')
  check(api('cash',body,nonce)[1]==result,'real REST cash replay exact')
  body.update(command=str(uuid.uuid4()),revision=2,transition='cancel_unpaid',evidence=str(uuid.uuid4()))
  check(api('cash',body,nonce)[0]==200,'real REST confirmed-unpaid release works')
  for action in public:check(api(action,nonce=nonce)[0]==404,'authenticated management cannot expose '+action)
  check('Online gift certificate purchases are not available yet.' in request(browser,'/?p='+evalphp(site,"echo get_page_by_path('gifts')->ID;"))[1],'public gift page remains closed')
 finally:
  if overlay.exists():overlay.unlink()
  if seeded:fixture('cleanup')
  config.write_text(saved);router.write_text(saved_router)
  if started:server.shutdown();thread.join(timeout=5)
  server.server_close();key.unlink(missing_ok=True);cert.unlink(missing_ok=True)
 return checks
