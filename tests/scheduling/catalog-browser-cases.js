'use strict';
module.exports=async({op,cli,check,base,instructor,arena,riders})=>{
 const p=op.p,url=base+'/wp-admin/admin.php?page=csf-scheduling&date=2027-01-27&view=week';
 await p.goto(url);
 const editor=p.locator('.csf-policy-editor');await editor.locator('summary').first().click();
 const root=p.locator('#csf-lesson-catalog'),rows=root.locator('.csf-catalog-rows .csf-catalog-row');
 check(await root.getByText('Paid and unpaid are classifications only:',{exact:false}).count()===1,'Classification has explicit nonfinancial meaning');
 const before=await rows.count();await root.locator('.csf-catalog-add').click();check(await rows.count()===before+1,'Add native lesson row');let row=rows.last();
 check(!await row.locator('[name$="[enabled]"]').isChecked()&&await row.locator('[name$="[duration_minutes]"]').inputValue()==='','New type disabled with unknown duration');
 await row.locator('[name$="[name]"]').fill('Synthetic draft type');await row.locator('[name$="[enabled]"]').check();
 for(const field of['capacity','duration_minutes','horse_source','resources_reviewed'])check(await row.locator(`[name$="[${field}]"]`).evaluate(el=>el.required),'Enabled type requires '+field);
 await row.locator('.csf-catalog-remove').click();check(await rows.count()===before,'Remove only unsaved row');
 await root.locator('.csf-catalog-add').click();await rows.last().locator('[name$="[name]"]').fill('Synthetic 30 / unpaid (disabled)');
 const sixty=rows.filter({has:p.locator('[name$="[key]"][value=catalog_student_60]')});await sixty.locator('[name$="[name]"]').fill('Student Lesson — 60 Minutes (renamed)');
 const form=p.locator('.csf-policy-form');await Promise.all([p.waitForNavigation(),form.getByRole('button',{name:'Review changes',exact:true}).click()]);
 check((await p.locator('#csf-facility-settings').innerText()).includes('Lesson type booking impact'),'Readable booking impact before apply');
 const review=p.locator('.csf-policy-review');check(await review.count()===1,'Metadata-only edit can be reviewed');await review.locator('[name=reviewed]').check();await Promise.all([p.waitForNavigation(),review.getByRole('button',{name:'Apply reviewed settings'}).click()]);
 check((await p.locator('#csf-facility-settings').innerText()).includes('committed and audited'),'Native reviewed policy commits');
 const added=JSON.parse(cli("$p=json_decode(CSF_Scheduling_Schema::settings()['policy_json'],true);foreach($p['services']as$id=>$s){if(($s['label']??'')==='Synthetic 30 / unpaid (disabled)')echo wp_json_encode(['id'=>$id,'service'=>$s]);}"));check(/^lesson_[a-f0-9]{32}$/.test(added.id),'New identity is opaque, not derived from punctuation or digits');check(!added.service.enabled&&added.service.duration_minutes===null&&added.service.horse_source===null&&added.service.required_resources===null,'Disabled new native draft retains unknown operating values');
 check(await p.locator('#csf-new-appointment [data-field=service_type] option[value=catalog_student_60]').innerText()==='Student Lesson — 60 Minutes (renamed)','Booking dropdown reflects friendly edited name');
 const f=p.locator('#csf-new-appointment');await p.locator('.csf-planner-slot[data-choice="2027-01-27T12:00-08:00"]').first().click();
 for(const minutes of[30,60]){await f.locator('[data-field=service_type]').selectOption('catalog_student_'+minutes);check((await f.locator('.csf-routine-summary').innerText()).includes(minutes===30?'12:30':'1:00'),'Friendly type determines '+minutes+' minute finish');}
 await f.locator('[data-field=title]').fill('Catalog browser fictional lesson');await f.locator('[data-field=single_rider]').selectOption(riders[0]);for(const id of[instructor,arena])await f.locator('[data-array=resources] input[value="'+id+'"]').check();await p.waitForFunction(()=>document.querySelector('#csf-new-appointment').dataset.slotStatus==='available');
 check(await f.locator('.csf-save').isDisabled(),'Friendly catalog retains explicit preview/save guard');
 await p.locator('.csf-policy-editor > summary').click();let active=rows.filter({has:p.locator('[name$="[key]"][value=pilot_ground_60]')});await active.locator('[name$="[duration_minutes]"]').fill('30');await Promise.all([p.waitForNavigation(),p.locator('.csf-policy-form').getByRole('button',{name:'Review changes',exact:true}).click()]);
 check((await p.locator('#csf-facility-settings').innerText()).includes('BLOCKED: reconcile'),'Future booking rule change has visible explanation');check(await p.locator('.csf-policy-review').count()===0,'Blocked impact provides no Apply button');
 for(const width of[390,768,1280]){await p.setViewportSize({width,height:900});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Catalog fits viewport '+width);await p.addScriptTag({content:require('fs').readFileSync(require.resolve('axe-core/axe.min.js'),'utf8')});const v=await p.evaluate(async()=>(await axe.run(document.querySelector('#csf-lesson-catalog'))).violations.map(v=>v.id));check(!v.length,'Catalog accessibility '+width+': '+v.join(','));}
 check(cli('echo CSF_SCHEDULING_VERSION;')==='0.4.6','Candidate version reported');
};
