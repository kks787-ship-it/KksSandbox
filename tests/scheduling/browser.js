'use strict';
// Disposable local fixture only; synthetic credentials must never be used on any remote host.
const {chromium}=require('playwright');
const net=require('net');
const {spawn,execFileSync}=require('child_process');
const fs=require('fs');
// The Docker daemon has a separate network namespace from the executor. Relay local
// browser sockets through docker exec, without exposing container or host ports.
const webTarget=process.env.CSF_TEST_WEB_CONTAINER;
if(!/^csf-foundation-[0-9]+-web$/.test(webTarget||''))throw new Error('Disposable web container required');
const relay=net.createServer(socket=>{
 const php='$s=stream_socket_client("tcp://127.0.0.1:8899");$in=STDIN;while(true){$r=[$in,$s];$w=null;$e=null;if(!stream_select($r,$w,$e,30)){break;}foreach($r as$f){$b=fread($f,65536);if($b===false||$b===""){exit;}if($f===$in){for($o=0;$o<strlen($b);$o+=$n){$n=fwrite($s,substr($b,$o));if(!$n){exit;}}}else{for($o=0;$o<strlen($b);$o+=$n){$n=fwrite(STDOUT,substr($b,$o));if(!$n){exit;}}fflush(STDOUT);}}}';
 const child=spawn('docker',['--host=unix:///var/run/docker.sock','exec','-i',webTarget,'php','-r',php],{stdio:['pipe','pipe','pipe']});
 socket.pipe(child.stdin);child.stdout.pipe(socket);child.stdin.on('error',()=>{});socket.on('error',()=>{});socket.on('close',()=>child.kill());child.on('error',()=>socket.destroy());child.on('close',()=>socket.end());
});
const base='http://localhost:8899';
let checks=0;
function check(ok,message){if(!ok)throw new Error(message);console.log('PASS '+message);checks++;}
(async()=>{
 await new Promise(resolve=>relay.listen(8899,'127.0.0.1',resolve));
 const browser=await chromium.launch({executablePath:'/usr/bin/chromium',headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try{
 const fixture=JSON.parse(execFileSync('docker',['--host=unix:///var/run/docker.sock','exec',webTarget,'cat','/test/calendar-fixture.json'],{encoding:'utf8'}));
 const page=await browser.newPage();const errors=[];const networkErrors=[];page.on('pageerror',e=>errors.push(e.message));page.on('requestfailed',r=>{if(r.url().startsWith(base+'/')&&r.failure().errorText!=='net::ERR_ABORTED'){networkErrors.push(r.failure().errorText+' '+new URL(r.url()).pathname);}});
 await page.route('**/*',route=>new URL(route.request().url()).origin===base?route.continue():route.abort());
 let transportRecoveries=0;
 async function recoverTransport(){
  for(let attempt=0;attempt<3;attempt++){
   if(!networkErrors.length){if(errors.length)throw new Error('Browser script error: '+errors.join(' | '));return;}
   if(attempt===2||networkErrors.some(e=>!e.startsWith('net::ERR_NETWORK_CHANGED ')))throw new Error('Local browser transport failed: '+networkErrors.join(' | '));
   // Docker/managed-network notifications can abort Chromium subresources. Retry only
   // this specific transport failure, with GET, never by repeating a booking POST.
   transportRecoveries++;console.log('NETWORK_CHANGED_GET_RECOVERY '+transportRecoveries);errors.length=0;networkErrors.length=0;await page.waitForTimeout(1000);await page.goto(page.url(),{waitUntil:'load'});
  }
 }

 await page.goto(base+'/wp-admin/admin.php?page=csf-scheduling');await recoverTransport();check(page.url().includes('wp-login.php'),'anonymous staff page requires login');
 await page.locator('#user_login').fill('fixture_operator');await page.locator('#user_pass').fill('fixture-only');await Promise.all([page.waitForURL(url=>url.pathname.startsWith('/wp-admin/'),{waitUntil:'load'}),page.locator('#wp-submit').click()]);await recoverTransport();
 check(await page.locator('.csf-calendar').isVisible()&&errors.length===0,'authenticated calendar entry has no browser errors: '+errors.join(' | '));
 for(const width of [390,768,1280]){
 await page.setViewportSize({width,height:900});await page.goto(base+'/wp-admin/admin.php?page=csf-scheduling&date=2027-01-18&view=week');await recoverTransport();
 check(await page.locator('h1').innerText()==='Digital Ranch Staff Calendar','private calendar rendered at '+width);
 check(await page.locator('.appointments article').count()===2,'week displays both committed race appointments at '+width);
 check(await page.locator('body').evaluate(el=>el.scrollWidth<=window.innerWidth),'no horizontal overflow at '+width);
 }
 await page.locator('select[name=view]').selectOption('day');await Promise.all([page.waitForURL(/view=day/),page.getByRole('button',{name:'Show calendar'}).click()]);await recoverTransport();check(await page.locator('.appointments article').count()===1,'day limits visible appointments');
 const first=page.locator('.appointments article').first();await first.getByText('Edit or confirm appointment',{exact:true}).click();const form=first.locator('form').first();await form.locator('[data-field=title]').fill('Browser edited appointment');await Promise.all([page.waitForNavigation(),form.getByRole('button',{name:'Save appointment'}).click()]);await recoverTransport();check(await page.locator('.appointments article').filter({hasText:'Browser edited appointment'}).count()===1,'staff edit persisted via nonce form');check(await page.locator('h2').filter({hasText:'Browser edited appointment'}).count()===1,'edited title displayed');
 // Requested appointment creation through the normal form builder.
 const create=page.locator('#csf-new-appointment');
 await create.locator('[data-field=title]').fill('Browser requested appointment');await create.locator('[data-field=service_type]').selectOption('lesson');await create.locator('[data-field=state]').selectOption('requested');await create.locator('[data-field=start]').fill('2027-01-18T13:00-08:00');await create.locator('[data-field=end]').fill('2027-01-18T14:00-08:00');
 await create.locator('[data-field=resources]').selectOption([fixture.instructor,fixture.arena]);await create.locator('[data-field=rider]').selectOption(fixture.rider);await create.locator('[data-field=horse]').selectOption(fixture.horse);
 await Promise.all([page.waitForNavigation(),create.getByRole('button',{name:'Create appointment',exact:true}).click()]);await recoverTransport();check(await page.locator('.appointments article').filter({hasText:'Browser requested appointment'}).count()===1,'normal form requested appointment persisted: '+await page.locator('.notice-error').allTextContents());
 check(await page.locator('.appointments article').count()===2,'requested appointment appears in day view');
 const target=page.locator('.appointments article').filter({hasText:'Browser requested appointment'});await target.getByText('Edit or confirm appointment',{exact:true}).click();const confirm=target.locator('form').first();await confirm.locator('[data-field=state]').selectOption('confirmed');await Promise.all([page.waitForNavigation(),confirm.getByRole('button',{name:'Save appointment'}).click()]);await recoverTransport();check((await target.innerText()).includes('Status: confirmed'),'requested appointment confirmed through staff form');await target.getByText('Cancel appointment',{exact:true}).click();const cf=target.locator('form').nth(1);await cf.locator('[data-field=reason]').fill('Synthetic browser cancellation');await Promise.all([page.waitForNavigation(),cf.getByRole('button',{name:'Record cancellation'}).click()]);await recoverTransport();check((await page.locator('body').innerText()).includes('Status: canceled'),'cancellation retained in UI');
 // CSRF must be rejected with no trusted nonce.
 const response=await page.request.post(base+'/wp-admin/admin.php?page=csf-scheduling',{form:{csf_calendar_command:'appointment_saved',body:'{}',command_id:'invalid',facility_revision:'0'}});check(response.status()===403,'missing nonce rejected with HTTP403');
 await page.addScriptTag({content:fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8')});const violations=await page.evaluate(async()=> (await axe.run(document.querySelector('.csf-calendar'))).violations.map(v=>v.id));check(violations.length===0,'calendar accessibility scan: '+violations.join(','));
 check(errors.length===0&&networkErrors.length===0,'no browser JavaScript or local transport errors: '+errors.concat(networkErrors).join(' | '));console.log('BROWSER_PASS '+checks+' checks; transport GET recoveries '+transportRecoveries);
 }finally{await browser.close();relay.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
