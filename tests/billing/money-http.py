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

  id=ids['fulfillment'];p=call('pdf',id=id);check(p['ok'] and p['result']['valid'],'actual database persisted PDF')
  check(call('pdf',id=id)==p,'actual database PDF stable replay')
  command=uid();rotation=call('rotate',id=id,revision=1,command=command);check(rotation['ok'],'actual database number rotation')
  check(call('rotate',id=id,revision=1,command=command)==rotation,'actual database rotation replay')
  check(call('pdf',id=id)['result']['artifact_id']!=p['result']['artifact_id'],'actual replacement PDF generation')
  check(call('state',id=id)['result']['value']['paid_minor']==101,'rotation preserves paid balance')
  import datetime
  start=datetime.datetime.now(datetime.timezone.utc)+datetime.timedelta(days=1);end=start+datetime.timedelta(hours=1)
  body=dict(uuid=uid(),expected=0,title='Synthetic SQL booking',service_type='lesson',state='confirmed',start=start.isoformat(timespec='minutes'),end=end.isoformat(timespec='minutes'),hold_expires=None,resources=['synthetic'],riders=['synthetic'],rides=[{'horse':'synthetic'}])
  id=ids['booking'];args=dict(id=id,body=body,transition='reserve',command=uid())
  check(not call('booking_sql',**args,fail=True)['ok'],'actual outer rollback after reservation')
  state=call('state',id=id)['result'];check(state['reserved']==[] and state['redeemed']==0 and state['revision']==1,'actual failed booking leaves credit unchanged')
  check(call('booking_sql',**args)['ok'],'actual joined reservation')
  state=call('state',id=id)['result'];check(len(state['reserved'])==1 and state['redeemed']==0,'actual reservation not consumption')
  check(not cash(id,state['revision'],'reserve',uid(),101)['ok'],'actual cash cannot consume booking reservation')
  # Spend remaining two slots, then race two bookings for a newly released last slot.
  check(call('redeem',id=id,revision=state['revision'],rides=2,command=uid(),ride=uid())['ok'],'actual debit excludes reserved ordinal')
  cancel=dict(uuid=body['uuid'],state='canceled',initiator='facility')
  check(call('booking_sql',id=id,body=cancel,transition='cancel',command=uid())['ok'],'actual cancellation releases reserved value')
  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
   futures=[pool.submit(call,'booking_sql',id=id,body=dict(body,uuid=uid()),transition='reserve',command=uid()) for _ in range(2)];results=[f.result() for f in futures]
  check(sum(r['ok'] for r in results)==1,'actual two-worker last-slot reservation has one winner')
  state=call('state',id=id)['result'];check(len(state['reserved'])==1 and state['redeemed']==2,'actual race preserves reserved and used balances')

  id=ids['calendar'];fixture=call('calendar_setup');check(fixture['ok'],'real scheduling schema and commands initialized under genuine MFA: '+str(fixture.get('error','')))
  body=fixture['result']['body'];revision=fixture['result']['revision']
  def command(body,revision,transition,command=None,**extra):return call('calendar_command',id=id,body=body,revision=revision,transition=transition,command=command or uid(),**extra)
  def status():return call('calendar_state',booking=body['uuid'])['result']
  failed=command(body,revision,'reserve',fault='history');check(failed['ok'] and not failed['result']['committed'],'real scheduler late history failure rejected')
  state=call('state',id=id)['result'];check(status()['appointment'] is None and status()['revision']==revision and state['revision']==1 and state['reserved']==[],'failed real booking rolls back appointment and credit')
  lost=uid();r=command(body,revision,'reserve',lost,fault='lost_commit');check(r['ok'] and r['result']['reason']=='commit_unknown','real lost commit acknowledgement stays uncertain')
  r=command(body,revision,'reserve',lost);check(r['ok'] and r['result']['committed'],'same real scheduler command reconciles committed booking')
  check(command(body,revision,'reserve',lost)==r,'real scheduler booking replay exact')
  state=call('state',id=id)['result'];check(state['revision']==2 and len(state['reserved'])==1 and state['redeemed']==0,'reconciled booking reserves once without consuming')
  conflict=command(dict(body,uuid=uid()),status()['revision'],'reserve');check(conflict['ok'] and conflict['result']['reason']=='resource_conflict','real scheduler rejects occupied lesson')
  check(call('state',id=id)['result']==state,'availability failure leaves credit unchanged')
  body['expected']=1;early=command(body,status()['revision'],'complete');check(early['ok'] and not early['result']['committed'],'real future lesson cannot consume credit')
  aged=call('calendar_age',body=body);check(aged['ok'],'synthetic-only elapsed-time setup')
  body=aged['result']['body'];revision=aged['result']['revision']
  fail=command(body,revision,'complete',fault='history');check(fail['ok'] and not fail['result']['committed'],'real completion late failure rejected')
  check(call('state',id=id)['result']==state,'failed completion restores reservation and used balance')
  complete=uid();done=command(body,revision,'complete',complete);check(done['ok'] and done['result']['committed'],'real scheduler completion commits')
  used=call('state',id=id)['result'];check(used['redeemed']==1 and used['reserved']==[],'completed booking consumes reserved ordinal once')
  check(command(body,revision,'complete',complete)==done,'real completed command replay never debits twice')
  body['expected']=2;revision=status()['revision'];fail=command(body,revision,'correct',fault='history');check(fail['ok'] and not fail['result']['committed'],'real correction late failure rejected')
  check(call('state',id=id)['result']==used,'failed correction leaves prior debit intact')
  fixed=command(body,revision,'correct');check(fixed['ok'] and fixed['result']['committed'],'real correction commits through scheduler')
  restored=call('state',id=id)['result'];check(restored['redeemed']==0 and len(restored['reserved'])==1,'correction restores reservation instead of free-spend credit')
  now=(datetime.datetime.now(datetime.timezone.utc)-datetime.timedelta(minutes=1)).isoformat(timespec='minutes')
  cancel=dict(uuid=body['uuid'],expected=3,initiator='facility',reason='Synthetic facility cancellation',notice=now,effective=now);revision=status()['revision']
  fail=command(cancel,revision,'cancel',fault='history');check(fail['ok'] and not fail['result']['committed'],'real cancellation late failure rejected')
  check(status()['appointment']['state']=='confirmed' and call('state',id=id)['result']==restored,'failed cancellation preserves booking and reservation together')
  cancel_id=uid();canceled=command(cancel,revision,'cancel',cancel_id);check(canceled['ok'] and canceled['result']['committed'],'real reviewed facility cancellation commits')
  released=call('state',id=id)['result'];check(status()['appointment']['state']=='canceled' and released['redeemed']==0 and released['reserved']==[],'real cancellation leaves no stranded reserved credit')
  check(command(cancel,revision,'cancel',cancel_id)==canceled,'real cancellation exact replay')

  offered=dict(fixture['result']['body'],uuid=uid(),expected=0);offer=uid();revision=status()['revision'];credit=call('state',id=id)['result']
  r=call('calendar_offer_create',id=id,body=offered,revision=revision,command=offer);check(r['ok'] and not r['result']['reserved'],'real reviewed offer reserves nothing')
  check(not call('calendar_offer_view',id=id,offer=offer,session='b'*64)['ok'],'real offer rejects wrong buyer session')
  view=call('calendar_offer_view',id=id,offer=offer);check(view['ok'] and 'riders' not in view['result'] and 'resources' not in view['result'],'real customer view excludes private scheduler identities')
  r=call('calendar_offer_confirm',id=id,offer=offer,revision=revision,command=uid());check(r['ok'] and not r['result']['committed'],'real confirmation requires buyer consent')
  check(not call('calendar_offer_accept',id=id,offer=offer,command=uid(),attested=True,wrong_number=True)['ok'],'real buyer certificate mismatch rejected')
  consent=uid();r=call('calendar_offer_accept',id=id,offer=offer,command=consent,attested=True);check(r['ok'],'real buyer certificate consent accepted')
  check(call('calendar_offer_accept',id=id,offer=offer,command=consent,attested=True)==r,'real customer consent replay exact')
  check(call('state',id=id)['result']==credit,'real buyer consent leaves available credit unchanged')
  r=call('calendar_offer_confirm',id=id,offer=offer,revision=revision,command=uid(),fault='history');check(r['ok'] and not r['result']['committed'],'consented real booking late failure rejected')
  check(call('state',id=id)['result']==credit and call('calendar_offer_view',id=id,offer=offer)['result']['state']=='awaiting_staff_confirmation','late failure rolls back offer consumption and credit together')
  confirm=uid();r=call('calendar_offer_confirm',id=id,offer=offer,revision=revision,command=confirm);check(r['ok'] and r['result']['committed'],'staff confirms exact buyer-consented lesson through real scheduler')
  check(call('calendar_offer_confirm',id=id,offer=offer,revision=revision,command=confirm)==r,'real consented booking replay exact')
  state=call('state',id=id)['result'];check(len(state['reserved'])==1 and state['redeemed']==0 and call('calendar_offer_view',id=id,offer=offer)['result']['state']=='reserved','customer state reconciles confirmed reservation without consumption')
 finally:check(call('cleanup')['ok'],'scratch financial tables removed')
 return count
