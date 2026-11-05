'use strict';
// Called only by the existing isolated WordPress/real-TOTP browser harness.
module.exports=async function({browser,cli,check,probe,login,renew,invitation,reset,snapshot,run,native,base}){
 const write=(m,approve=true)=>cli(`if(ABSPATH!=="/test/site/"||DB_NAME!=="csf_fixture"){throw new RuntimeException("Fixture only");}$m=json_decode(${JSON.stringify(JSON.stringify(m))},true);file_put_contents('/test/identity-invitation.json',wp_json_encode($m));chmod('/test/identity-invitation.json',0600);${approve?"file_put_contents('/test/pilot-approval',CSF_Scheduling_Identity::digest($m));chmod('/test/pilot-approval',0600);":""}`);
 const make=()=>{const m=invitation(1,'pilot_bootstrap');write(m);return m;};
 const absent=()=>cli('echo CSF_Scheduling_Identity::invitation()===null?"denied":"available";')==='denied';
 const fixture=JSON.parse(cli('echo file_get_contents("/test/mfa-private.json");'));
 cli(`if(file_exists('/test/admin-enforced')){unlink('/test/admin-enforced');}foreach([${fixture.trainer},${fixture.outside}]as$id){delete_user_meta($id,'_two_factor_enabled_providers');WP_Session_Tokens::get_instance($id)->destroy_all();}`);
 reset();const op=await login(browser,'fixture_operator',1);
 check((await probe(op.c)).mfa&&!(await probe(op.c)).calendar,'real TOTP is valid with enforcement off; no unbootstrapped calendar access');
 let m=invitation(1,'pilot_bootstrap');
 check(absent(),'pilot invitation defaults denied without exact separate approval');
 write(m);check(!absent(),'exact owner approval enables one initial development invitation');
 const savedInitial=snapshot();
 check(!JSON.parse(cli(`echo wp_json_encode(CSF_Scheduling_Identity::authorize('identity_requested',json_decode(${JSON.stringify(JSON.stringify(m))},true)));`)),'CLI cannot authorize the pilot, even as an administrator');
 const cookies=JSON.parse(cli('$t=WP_Session_Tokens::get_instance(1)->create(time()+3600);echo wp_json_encode([["name"=>LOGGED_IN_COOKIE,"value"=>wp_generate_auth_cookie(1,time()+3600,"logged_in",$t),"url"=>home_url()],["name"=>AUTH_COOKIE,"value"=>wp_generate_auth_cookie(1,time()+3600,"auth",$t),"url"=>home_url()]]);'));
 const passwordOnly=await browser.newContext();await passwordOnly.addCookies(cookies);check(!(await probe(passwordOnly)).mfa,'password-only administrator has no MFA proof');
 check(!(await run({c:passwordOnly},'identity_requested',m)).success,'password-only administrator cannot start pilot bootstrap');await passwordOnly.close();
 for(const [label,change]of[
  ['site',x=>x.site='http://wrong.invalid'],['environment',x=>x.environment='production'],
  ['release',x=>x.release='0'.repeat(64)],['expired',x=>{x.issued-=700;x.expires-=700;}],
  ['oversized window',x=>x.expires=x.issued+601],['future issue',x=>{x.issued+=600;x.expires+=600;}],
  ['different owner',x=>x.owner=fixture.trainer],['transfer',x=>x.action='transfer'],
  ['prior owner',x=>x.previous_master=1],['duplicate commands',x=>x.commands.identity_consumed=x.commands.identity_requested]
 ]){const bad=structuredClone(m);change(bad);write(bad);check(absent(),'approved pilot still rejects '+label);}
 write(m);const changed=structuredClone(m);changed.verification_record_hash='f'.repeat(64);write(changed,false);check(absent(),'private invitation tampering invalidates exact owner approval');write(m);
 cli('chmod("/test/identity-invitation.json",0644);');check(absent(),'publicly readable invitation refused');cli('chmod("/test/identity-invitation.json",0600);');
 cli('file_put_contents("/test/pilot-production","yes");');check(absent(),'production cannot use pilot bootstrap even with matching approval');cli('unlink("/test/pilot-production");');
 cli('file_put_contents("/test/admin-enforced","yes");');check(absent(),'enforced installations cannot substitute the one-controller exception');cli('unlink("/test/admin-enforced");');
 const ordinary=invitation(1,'bootstrap');write(ordinary);check(absent(),'ordinary ownership still forbids same-account approvals');
 const independent=invitation(fixture.trainer,'bootstrap');write(independent);check(!(await run(op,'identity_requested',independent)).success,'ordinary two-account bootstrap still requires mandatory enforcement');
 m=make();check(!(await run(op,'identity_consumed',m)).success,'pilot cannot skip reviewed approval steps');
 // Re-sign only a synthetic fixture proof to isolate the five-minute freshness rule.
 cli('$all=CSF_MFA_Session::meta(1,"session_tokens");foreach($all as&$session){if(isset($session[CSF_MFA_Session::KEY])){$p=&$session[CSF_MFA_Session::KEY];$p["issued"]-=301;$p["expires"]-=301;unset($p["signature"]);$p["signature"]=hash_hmac("sha256",wp_json_encode($p),wp_salt("auth"));}}update_user_meta(1,"session_tokens",$all);');
 check((await probe(op.c)).mfa&&!(await run(op,'identity_requested',m)).success,'otherwise valid MFA older than five minutes cannot approve pilot');
 await renew(browser,op,'fixture_operator');
 for(const needle of ['UPDATE `fixture_csf_sched_authorization`','UPDATE `fixture_usermeta`','INSERT INTO `fixture_csf_sched_history`','UPDATE `fixture_csf_sched_settings`','INSERT INTO `fixture_csf_sched_audit`','INSERT INTO `fixture_csf_sched_commands`','COMMIT']){
  reset();m=make();check((await run(op,'identity_requested',m)).success&&(await run(op,'identity_owner_approved',m)).success,'pilot approvals committed before '+needle);
  const before=snapshot();cli(`file_put_contents('/test/security-fault',${JSON.stringify(needle)});`);
  const bad=await run(op,'identity_consumed',m);cli('if(!file_exists("/test/security-fault-hit")){throw new RuntimeException("Fault not exercised");}unlink("/test/security-fault");unlink("/test/security-fault-hit");');
  check(!bad.success&&snapshot()===before,'pilot '+needle+' failure rolls back registry, mirrors, ledger and audit');
 }
 reset();m=make();await run(op,'identity_requested',m);await run(op,'identity_owner_approved',m);
 cli('$p="/test/pilot-approval";file_put_contents($p,str_repeat("0",64));');const beforeRevoked=snapshot();check(!(await run(op,'identity_consumed',m)).success&&snapshot()===beforeRevoked,'revoking owner approval between steps prevents consumption');
 reset();m=make();await run(op,'identity_requested',m);await run(op,'identity_owner_approved',m);
 cli('WP_Session_Tokens::get_instance(1)->destroy_all();');const beforeSession=snapshot();check(!(await run(op,'identity_consumed',m)).success&&snapshot()===beforeSession,'revoked controller session cannot finish bootstrap');
 await renew(browser,op,'fixture_operator');reset();m=make();
 const noNonce=await op.c.request.post(base+'/wp-admin/admin.php?page=csf-identity',{form:{step:'identity_requested',reviewed:'1'}});check(noNonce.status()===403,'native pilot form requires its nonce');
 const beforeReview=snapshot();const reviewNonce=await probe(op.c);await op.c.request.post(base+'/wp-admin/admin.php?page=csf-identity',{form:{step:'identity_requested',_wpnonce:reviewNonce.nonce}});check(snapshot()===beforeReview,'native pilot form requires explicit review in addition to nonce');
 await op.p.goto(base+'/wp-admin/admin.php?page=csf-identity');check((await op.p.locator('body').innerText()).includes('Single-controller development pilot'),'native review clearly identifies single-controller scope');
 for(const width of [390,768,1280]){await op.p.setViewportSize({width,height:900});check(await op.p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'ownership review fits width '+width);}
 await native(op,'Start operator review');await native(op,'Approve as proposed owner');
 const both=await Promise.all([run(op,'identity_consumed',m),run(op,'identity_consumed',m)]);
 check(both.some(x=>x.success),'one simultaneous consume commits');
 check(Number(cli(`global$wpdb;echo$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".CSF_Scheduling_Schema::table('commands')." WHERE uuid=%s",${JSON.stringify(m.commands.identity_consumed)}));`))===1,'simultaneous consume has exactly one immutable result');
 const consumed=snapshot();check((await run(op,'identity_consumed',m)).success&&snapshot()===consumed,'exact retry recovers result without another ownership mutation');
 check(Number(cli('echo CSF_Scheduling_Permissions::state()["master"];'))===1&&(await probe(op.c)).calendar,'single MFA controller can open the pilot calendar');
 check(cli('echo CSF_MFA_Admin::enabled()?"on":"off";')==='off','pilot leaves mandatory administrator enforcement off');
 check(cli(`$s=CSF_Scheduling_Permissions::state();echo count($s['members']).':'.(isset($s['members'][${fixture.trainer}])?'granted':'paused').':'.(isset($s['members'][${fixture.outside}])?'granted':'paused');`)==='1:paused:paused','paused accounts receive no membership or grants');
 check(cli('echo get_userdata(1)->roles[0];')==='administrator','existing native administrator role preserved');
 const role=await probe(op.c);const deny=await op.c.request.post(base+'/?rest_route=/wp/v2/users/1',{headers:{'X-WP-Nonce':role.rest_nonce},data:{roles:[]}});check(deny.status()===403,'last-master native-role protection remains intact');
 const newInvite=make();const masterState=snapshot();check(!(await run(op,'identity_requested',newInvite)).success&&snapshot()===masterState,'new pilot cannot replace an existing master');
 // Lost-commit simulation on a fresh synthetic baseline only.
 reset();m=make();await run(op,'identity_requested',m);await run(op,'identity_owner_approved',m);
 cli('file_put_contents("/test/security-fault","commit_ack");');check(!(await run(op,'identity_consumed',m)).success,'lost COMMIT acknowledgement is reported uncertain');cli('unlink("/test/security-fault");unlink("/test/security-fault-hit");');
 check(Number(cli('echo CSF_Scheduling_Permissions::state()["master"];'))===1,'lost response may represent a committed pilot grant');
 await op.p.goto(base+'/wp-admin/admin.php?page=csf-identity');const status=op.p.locator('form').filter({has:op.p.getByRole('button',{name:'Check result',exact:true})});await status.locator('input[name=command]').fill(m.commands.identity_consumed);await status.locator('input[name=reviewed]').check();await Promise.all([op.p.waitForNavigation(),status.getByRole('button',{name:'Check result',exact:true}).click()]);check((await op.p.getByRole('status').innerText()).includes('committed and audited'),'original pilot command resolves uncertainty through native screen');
 check(savedInitial!==snapshot(),'transactional pilot fixture exercised real database changes');
};
