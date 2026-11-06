'use strict';
// Disposable local fixture with real provider sessions; never pointed at a farm.
module.exports=async({browser,cli,check,login,base})=>{
 const op=await login(browser,'fixture_operator',1),p=op.p;
 const url=base+'/wp-admin/admin.php?page=csf-scheduling';
 const state=()=>JSON.parse(cli('global$wpdb;$s=CSF_Scheduling_Schema::settings();$t=CSF_Scheduling_Schema::table("audit");echo wp_json_encode(["policy"=>json_decode($s["policy_json"],true),"revision"=>(int)$s["revision"],"audit"=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`")]);'));
 async function click(form,name){await Promise.all([p.waitForNavigation(),form.getByRole('button',{name,exact:true}).click()]);}
 async function openEditor(){const d=p.locator('.csf-policy-editor');if(!await d.evaluate(e=>e.open))await d.locator(':scope > summary').click();}
 const savedFields=async()=>p.locator('.csf-policy-review').evaluate(f=>Object.fromEntries(new FormData(f)));
 async function review(){await openEditor();await click(p.locator('.csf-policy-form'),'Review facility settings');return savedFields();}
 async function apply(fields){return op.c.request.post(url,{form:{...fields,reviewed:'1'}});}
 await p.goto(url);const initial=state();check(initial.policy.service_hours===null&&initial.policy.services.length===0,'fixture begins with incomplete actual-policy shape');
 check(await p.locator('.csf-policy-form').count()===1,'verified owner has native facility form');
 check(!(await p.locator('.csf-policy-form').innerText()).includes('JSON'),'normal settings have no structured-data editor');
 await click(p.locator('.csf-policy-example'),'Load fictional pilot example (not saved)');
 check(JSON.stringify(state())===JSON.stringify(initial),'loading fictional example makes no policy/audit change');
 check(await p.locator('[name="settings[timezone]"]').inputValue()==='America/Los_Angeles','fictional timezone loads in ordinary select');
 // Split hours exercise round-trip preservation rather than reducing a day to one interval.
 await p.locator('[name="settings[hours][1][0][end]"]').fill('12:00');
 await p.locator('[name="settings[hours][1][1][start]"]').fill('13:00');
 await p.locator('[name="settings[hours][1][1][end]"]').fill('18:00');
 for(const width of [390,768,1280]){
  await p.setViewportSize({width,height:900});
  check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'native policy form fits width '+width);
  await p.addScriptTag({content:require('fs').readFileSync(require.resolve('axe-core/axe.min.js'),'utf8')});const violations=await p.evaluate(async()=>(await axe.run(document.querySelector('#csf-facility-settings'))).violations.map(v=>v.id));check(!violations.length,'facility form accessibility '+width+': '+violations.join(','));
 }
 const a=await review();
 check((await p.locator('.csf-policy-table').innerText()).includes('08:00–12:00, 13:00–18:00'),'review shows readable split hours');
 check(JSON.stringify(state())===JSON.stringify(initial),'review makes no reservation/policy/audit change');
 for(const width of [390,768,1280]){
  await p.setViewportSize({width,height:900});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'policy comparison fits width '+width);
 }
 const denied=await op.c.request.post(url,{form:{...a,reviewed:'1',_wpnonce:'invalid'}});
 check(denied.status()===403&&JSON.stringify(state())===JSON.stringify(initial),'invalid nonce cannot change policy');
 const altered=JSON.parse(a.review);altered.policy.workload.daily_minutes=999;
 const tamper=await apply({...a,review:JSON.stringify(altered)});
 check((await tamper.text()).includes('This review changed')&&JSON.stringify(state())===JSON.stringify(initial),'tampered hidden policy cannot cross review');
 const missing=await op.c.request.post(url,{form:a});
 check((await missing.text()).includes('Review the settings before saving')&&JSON.stringify(state())===JSON.stringify(initial),'explicit owner checkbox required');
 let response=await apply(a);check((await response.text()).includes('Facility settings committed and audited'),'native owner commit succeeds');
 await p.goto(url);check(!await p.locator('.csf-policy-editor').evaluate(e=>e.open),'configured settings collapse so the calendar stays easy to reach');
 let current=state();check(current.revision===initial.revision+1&&current.audit===initial.audit+1,'one policy revision and audit appended');
 check(current.policy.service_hours[0].length===0&&current.policy.service_hours[1].length===2,'Sunday closure and split Monday hours preserved');
 check(Object.keys(current.policy.services).length===3&&current.policy.academy.weekday===1&&current.policy.preferred_offsite_day===3,'pilot services, Monday Academy and Wednesday preference round-trip');
 response=await apply(a);check((await response.text()).includes('committed and audited')&&JSON.stringify(state())===JSON.stringify(current),'exact repeated native request has one result/audit');
 await p.goto(url);const stale=await review();
 await p.goto(url);await openEditor();await p.locator('[name="settings[daily_minutes]"]').fill('150');const next=await review();await apply(next);const afterNext=state();
 response=await apply(stale);check((await response.text()).includes('Settings changed in another tab')&&JSON.stringify(state())===JSON.stringify(afterNext),'stale tab cannot overwrite a newer policy');
 await p.goto(url);check(await p.locator('[name="settings[hours][1][1][start]"]').inputValue()==='13:00','editing retains second opening interval');
 await openEditor();await p.locator('[name="settings[travel_buffer_minutes]"]').fill('61');const uncertain=await review();
 cli('file_put_contents("/test/security-fault","commit_ack");');
 try{response=await apply(uncertain);}finally{cli('unlink("/test/security-fault");if(file_exists("/test/security-fault-hit"))unlink("/test/security-fault-hit");');}
 check((await response.text()).includes('Settings not confirmed'),'lost commit acknowledgement is not reported as success');
 const committed=state();response=await apply(uncertain);
 check((await response.text()).includes('committed and audited')&&JSON.stringify(state())===JSON.stringify(committed),'uncertain original command resolves without duplicate audit');
 const f=JSON.parse(cli('echo file_get_contents("/test/mfa-private.json");'));const trainer=await login(browser,'mfa_trainer',f.trainer);
 await trainer.p.goto(url);check(await trainer.p.locator('.csf-policy-form').count()===0,'trainer without policy permission never sees owner form');
 response=await trainer.c.request.post(url,{form:{...uncertain,reviewed:'1'}});
 check(response.status()===403&&JSON.stringify(state())===JSON.stringify(committed),'trainer cannot replay an owner review');
 // Typed parser failures are evaluated in this disposable site only, not a privileged HTTP path.
 const parsed=cli('$p=CSF_Scheduling_Settings::pilot();if(is_wp_error(CSF_Scheduling_Policy::validate($p))||CSF_Scheduling_Policy::missing($p)){throw new RuntimeException("Invalid pilot policy");}echo "valid";');
 check(parsed==='valid','fictional policy satisfies existing domain readiness without new engine rules');
 await require('./facility-workflow-cases')({op,cli,check,base,browser,login});
 await trainer.c.close();await op.c.close();
};
