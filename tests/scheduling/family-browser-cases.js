'use strict';
// Synthetic, isolated WordPress only; caller authenticates through the real MFA provider.
module.exports=async({op,cli,check,base,arena,instructor,riders})=>{
 const p=op.p,url=base+'/wp-admin/admin.php?page=csf-scheduling&screen=calendar&tool=family&date=2027-01-26';
 const state=()=>cli('global$wpdb;$rows=[];foreach(CSF_Scheduling_Schema::definitions()as$n=>$d){$t=CSF_Scheduling_Schema::table($n);$rows[$n]=$wpdb->get_results("SELECT * FROM `$t` ORDER BY 1",ARRAY_A);}echo hash("sha256",wp_json_encode($rows));');
 const count=()=>Number(cli('echo $GLOBALS["wpdb"]->get_var("SELECT COUNT(*) FROM fixture_csf_sched_appointments");'));
 const submit=async(action)=>Promise.all([p.waitForNavigation(),p.locator('[name=csf_family_action][value="'+action+'"]').click()]);
 async function fill(i,hour){
  const field=key=>p.locator('[name="lessons['+i+']['+key+']"]');
  await field('service_type').selectOption('pilot_ground_60');await field('rider').selectOption(riders[0]);await field('horse').selectOption('');await field('start').fill('2027-01-26T'+hour+':00');
  for(const id of [arena,instructor])await p.locator('[name="lessons['+i+'][resources][]"][value="'+id+'"]').check();
 }
 await p.goto(url);check(await p.locator('#csf-task-dialog').evaluate(e=>e.open)&&await p.locator('#csf-task-dialog #csf-family-panel').count()===1,'family editor opens in the calendar task drawer');await p.getByRole('button',{name:'Close panel',exact:true}).click();check(await p.locator('.csf-family-open').evaluate(e=>e===document.activeElement),'family drawer close restores focus to its calendar action');await p.locator('.csf-family-open').click();const before=count();await fill(0,'13');await p.locator('[name=same_family]').check();await submit('add');await fill(1,'14');await p.locator('[name=same_family]').check();const unchanged=state();await submit('review');
 check(state()===unchanged,'family native review writes no scheduling rows');check(await p.locator('#csf-task-dialog').evaluate(e=>e.open),'family review remains in the calendar task drawer');
 const form=p.locator('form').filter({has:p.locator('[name=csf_family_action][value=apply]')});check(await form.count()===1,'family native review produces one explicit save');
 const fields=await form.evaluate(el=>Object.fromEntries(new FormData(el))),review=JSON.parse(fields.family_review);
 check(review.body.items.length===2,'signed family review contains both individual lessons');
 const bad=await op.c.request.post(url,{form:{...fields,csf_family_action:'apply',family_signature:'0'.repeat(64)}});check(bad.status()===200&&state()===unchanged,'tampered family signature cannot commit');
 await submit('apply');check((await p.locator('[role=status]').allTextContents()).join(' ').includes('Family visit saved atomically')&&count()===before+2,'native family save commits exactly two lessons');
 const committed=state();await op.c.request.post(url,{form:{...fields,csf_family_action:'apply'}});check(state()===committed,'exact native family replay makes no additional writes');
 await p.getByRole('link',{name:'Open visit / book another lesson',exact:true}).click();check(await p.locator('[name=visit_uuid]').inputValue()===review.body.uuid&&Number(await p.locator('[name=visit_expected]').inputValue())>0,'authorized visit query opens current saved revision');
 check(await p.locator('[name="lessons[1][start]"]').inputValue()==='2027-01-26T14:00','native family edit retains second lesson');
 const beforeEdit=state();await p.locator('[name="lessons[1][start]"]').fill('2027-01-26T14:15');await p.locator('[name=same_family]').check();await submit('review');check(state()===beforeEdit&&await p.locator('[name=csf_family_action][value=apply]').count()===0,'separating shared lessons by insufficient independent buffer time rejects without writes');await p.locator('[name="lessons[1][start]"]').fill('2027-01-26T14:30');await p.locator('[name=same_family]').check();await submit('review');await submit('apply');check(count()===before+2&&(await p.locator('[role=status]').allTextContents()).join(' ').includes('Family visit saved atomically'),'native family edit updates existing lessons without duplication');
};
