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
 const fixture=JSON.parse(execFileSync('docker',['--host=unix:///var/run/docker.sock','exec',webTarget,'cat','/test/permissions-fixture.json'],{encoding:'utf8'}));
 const cli=code=>execFileSync('docker',['--host=unix:///var/run/docker.sock','exec',webTarget,'php','/repo/.wp-local/wp-cli.phar','--path=/test/site','--allow-root','--user=1','eval',code],{encoding:'utf8'}).trim();
 const snapshot=()=>cli('global $wpdb;$r=[];foreach(CSF_Scheduling_Schema::definitions()as$n=>$d){$t=CSF_Scheduling_Schema::table($n);$r[$n]=$wpdb->get_results("SELECT * FROM `$t` ORDER BY 1",ARRAY_A);}echo hash("sha256",wp_json_encode($r));');
 const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message+' '+e.stack));
 await page.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());
 async function login(p,user){await p.goto(base+'/wp-login.php');await p.locator('#user_login').fill(user);await p.locator('#user_pass').fill('fixture-only');await Promise.all([p.waitForNavigation(),p.locator('#wp-submit').click()]);await p.evaluate(()=>new Promise(resolve=>{if(window.jQuery){window.jQuery(()=>setTimeout(resolve,0));}else{resolve();}}));}
 async function employee(id){await page.goto(base+'/wp-admin/admin.php?page=csf-team');const f=page.locator('form').filter({has:page.locator('select[name=employee]')});await f.locator('select[name=employee]').selectOption(String(id));await Promise.all([page.waitForNavigation(),f.getByRole('button',{name:'Select employee',exact:true}).click()]);}
 async function preview(){await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Preview employee changes',exact:true}).click()]);await page.getByRole('heading',{name:'Review changes before saving',exact:true}).waitFor();}
 async function save(){await page.locator('input[name=confirm_sensitive]').check();await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Confirm reviewed changes',exact:true}).click()]);check(await page.getByText('Permissions saved and audited.',{exact:true}).isVisible(),'native reviewed save acknowledged');}
 await login(page,'fixture_operator');
 for(const width of [390,768,1280]){
  await page.setViewportSize({width,height:900});const before=snapshot();await page.goto(base+'/wp-admin/admin.php?page=csf-team');check(await page.getByRole('heading',{name:'Team & Permissions',exact:true}).isVisible(),'private Team & Permissions '+width);
  check(await page.locator('.csf-team').evaluate(el=>el.scrollWidth<=el.clientWidth+1),'no permission-panel horizontal overflow '+width);
  await page.addScriptTag({path:require.resolve('axe-core/axe.min.js')});const a=await page.evaluate(async()=>axe.run('.csf-team',{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa']}}));check(a.violations.length===0,'axe permission interface '+width+' '+a.violations.map(v=>v.id).join(','));check(snapshot()===before,'GET owner dashboard preserves every owned table '+width);
 }
 await employee(fixture.trainer);
 let row=page.locator('.csf-permission').filter({has:page.locator('select[name="override[appointment_confirm]"]')});
 check(await row.locator('[data-effective]').isChecked(),'inherited Head Trainer confirmation shown effective');await row.locator('[data-effective]').focus();await page.keyboard.press('Space');check(await row.locator('[data-override]').inputValue()==='deny'&&!await row.locator('[data-effective]').isChecked(),'keyboard checkbox creates explicit deny and matching effective result');check(await row.locator('[data-current]').innerText()==='Granted'&&await row.locator('[data-result]').innerText()==='Denied','unsaved override distinguishes current access from proposed denial');
 const before=snapshot();await preview();check(snapshot()===before,'native permission preview makes no database changes');check(await page.getByText('Revoked: Confirm and edit confirmed appointments',{exact:true}).isVisible(),'preview names revoked permission');await save();
 check(cli('wp_set_current_user('+fixture.trainer+');echo CSF_Scheduling_Access::can("appointment_confirm")?"yes":"no";')==='no','saved effective deny matches server authorization');
 const staff=await browser.newPage();await staff.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());await login(staff,'permission_trainer');let response=await staff.goto(base+'/wp-admin/admin.php?page=csf-team');check(response.status()===403,'trainer direct team URL denied');
 const denied=await staff.request.post(base+'/wp-admin/admin.php?page=csf-team',{form:{csf_team_action:'preview_member',employee:String(fixture.trainer),group:'operations',revision:'0',_wpnonce:'forged'}});check(denied.status()===403,'forged trainer permission POST denied on server');
 await staff.goto(base+'/wp-admin/admin.php?page=csf-scheduling&date=2027-01-26');check(await staff.getByRole('heading',{name:'Digital Ranch Staff Calendar',exact:true}).isVisible(),'trainer calendar remains accessible after confirmation-only denial');
 check(await staff.locator('select[data-field=state] option[value=confirmed]').count()===0,'trainer UI removes confirmation after override');
 await employee(fixture.trainer);await page.locator('input[name=active]').uncheck();await preview();await save();await staff.goto(base+'/wp-admin/admin.php?page=csf-scheduling');check(!await staff.getByRole('heading',{name:'Digital Ranch Staff Calendar',exact:true}).count(),'revocation invalidates existing staff session');
 await employee(fixture.trainer);check(await page.locator('[data-current]').evaluateAll(nodes=>nodes.every(n=>n.textContent==='Denied')),'inactive employee effective access displays denied despite configured group defaults');await page.locator('input[name=active]').check();await page.locator('select[name="override[appointment_confirm]"]').selectOption('inherit');await preview();await save();
 await employee(1);const masterBefore=snapshot();await page.locator('input[name=active]').uncheck();await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Preview employee changes',exact:true}).click()]);check(await page.getByText('Save not acknowledged. Verify current permissions before retrying.',{exact:true}).isVisible(),'last-master removal blocked in native UI');check(snapshot()===masterBefore,'last-master rejection preserves authorization and audit');
 await employee(fixture.trainer);const form=page.locator('form').filter({has:page.locator('input[value=preview_member]')});const nonce=await form.locator('input[name=_wpnonce]').inputValue();const rev=await form.locator('input[name=revision]').inputValue();const forgedBefore=snapshot();await page.request.post(base+'/wp-admin/admin.php?page=csf-team',{form:{csf_team_action:'preview_member',employee:String(fixture.trainer),group:'head_trainer',revision:rev,_wpnonce:nonce,'override[manage_options]':'grant'}});check(snapshot()===forgedBefore,'valid-nonce unknown capability request cannot alter rights');
 await employee(fixture.trainer);
 const groupBox=page.locator('details').filter({has:page.locator('input[name=group_id][value=head_trainer]')}).first();await groupBox.locator(':scope > summary').click();const groupForm=groupBox.locator('form').first();
 await groupForm.locator('input[name=copy_group]').check();await groupForm.locator('input[name=group_label]').fill('Browser Assistant');const copyBefore=snapshot();
 await Promise.all([page.waitForNavigation(),groupForm.getByRole('button',{name:'Preview group and affected members',exact:true}).click()]);await page.getByRole('heading',{name:'Review changes before saving',exact:true}).waitFor();check(snapshot()===copyBefore,'native group-copy preview preserves state');check(await page.getByText('Permission group: Browser Assistant',{exact:true}).isVisible(),'custom group review shows friendly name');await save();
 check(cli('echo in_array("Browser Assistant",array_column(CSF_Scheduling_Permissions::state()["groups"],"label"),true)?"yes":"no";')==='yes','owner creates custom group without editing a technical group key');
 const transfer=target=>cli('echo wp_json_encode(fixture_master('+target+'));');
 check(!JSON.parse(transfer(fixture.owner)).errors,'guarded fixture transfer selects separate Subscriber owner');
 const ownerPage=await browser.newPage();ownerPage.on('pageerror',e=>errors.push(e.message+' '+e.stack));await ownerPage.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());await login(ownerPage,'permission_owner');await ownerPage.goto(base+'/wp-admin/admin.php?page=csf-team');check(await ownerPage.getByRole('heading',{name:'Team & Permissions',exact:true}).isVisible(),'Subscriber master opens actual native owner dashboard');
 const usersPage=await ownerPage.goto(base+'/wp-admin/users.php');check(usersPage.status()===403,'Subscriber master is denied WordPress user administration');
 check(!JSON.parse(transfer(1)).errors,'guarded recovery restores fixture operator after Subscriber dashboard test');
 check(errors.length===0,'owner interface has no JavaScript exceptions: '+errors.join(' | '));console.log('PERMISSIONS_BROWSER_PASS '+checks+' checks');
 }finally{await browser.close();relay.close();}
})().catch(e=>{console.error(e);relay.close();process.exitCode=1;});
