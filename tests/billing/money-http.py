"""Service/real DB probes using a genuine restored password/TOTP browser cookie and nonce."""
import concurrent.futures,copy,http.cookiejar,json,secrets,urllib.request,urllib.error,uuid

def money_checks(opener,base,nonce):
 token=secrets.token_hex(8);count=0
 def call(action,**data):
  # Separate clients share a copied authenticated session, not a live CookieJar.
  jar=http.cookiejar.CookieJar()
  for handler in opener.handlers:
   if isinstance(handler,urllib.request.HTTPCookieProcessor):
    for cookie in handler.cookiejar:jar.set_cookie(copy.copy(cookie))
  client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
  req=urllib.request.Request(base+'/fixture-money',json.dumps(dict(token=token,action=action,**data)).encode(),{'Content-Type':'application/json','X-WP-Nonce':nonce})
  try:r=client.open(req,timeout=30)
  except urllib.error.HTTPError as e:r=e
  return json.loads(r.read())
 def check(ok,label):
  nonlocal count
  assert ok,label;count+=1
 def uid():return str(uuid.uuid4())
 def cash(id,revision,transition,request,amount,command=None,evidence=None):return call('cash',id=id,revision=revision,transition=transition,request=request,amount=amount,command=command or uid(),evidence=evidence or uid(),attested=True)
 setup=call('setup');check(setup['ok'],'real setup');ids=setup['result']
 try:
  id=ids['workflow'];first=uid();r=call('redeem',id=id,revision=1,rides=1,command=first,ride=uid());check(r['ok'] and r['result']['value_minor']==33,'real proportional debit')
  r=call('redeem',id=id,revision=2,rides=1,command=uid(),ride=uid());check(r['ok'] and r['result']['value_minor']==34,'real rounded debit')
  r=call('correct',id=id,revision=3,original=first,command=uid());check(r['ok'],'real exact correction')
  request=uid();r=cash(id,4,'reserve',request,67);check(r['ok'],'real reserve remaining value')
  check(not call('redeem',id=id,revision=5,rides=1,command=uid(),ride=uid())['ok'],'reserved cash prevents ride spend')
  check(cash(id,5,'uncertain',request,67)['ok'],'uncertain cash remains held')
  command=uid();evidence=uid();r=cash(id,6,'record_paid',request,67,command,evidence);check(r['ok'] and r['result']['remaining_minor']==0,'attested payout consumes exact remaining value')
  check(cash(id,6,'record_paid',request,67,command,evidence)==r,'real payout replay exact')
  check(not cash(id,7,'record_paid',request,67)['ok'],'real duplicate payout denied')
  check(not call('redeem',id=id,revision=7,rides=1,command=uid(),ride=uid())['ok'],'cash prevents remaining ride double spend')
  id=ids['race'];request=uid()
  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
   a=pool.submit(cash,id,1,'reserve',request,101)
   b=pool.submit(call,'redeem',id=id,revision=1,rides=1,command=uid(),ride=uid())
   results=[a.result(),b.result()]
  check(sum(r['ok'] for r in results)==1,'two-worker cash versus ride has one winner')
  state=call('state',id=id)['result'];check(state['revision']==2 and ((state['redeemed']==0 and state['value']['pending']) or (state['redeemed']==1 and state['value']['pending'] is None)),'race balance and reservation coherent')
  id=ids['replay'];request=uid();command=uid();evidence=uid()
  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
   futures=[pool.submit(cash,id,1,'reserve',request,101,command,evidence) for _ in range(2)];results=[f.result() for f in futures]
  check(results[0]['ok'] and results[0]==results[1],'two-worker same reservation replay once')
  check(call('state',id=id)['result']['revision']==2,'same command increments once')
  check(cash(id,2,'cancel_unpaid',request,101)['ok'],'confirmed unpaid release')
  check(not cash(id,3,'reserve',request,101)['ok'],'used reservation identity cannot restart')
 finally:check(call('cleanup')['ok'],'scratch financial tables removed')
 return count
