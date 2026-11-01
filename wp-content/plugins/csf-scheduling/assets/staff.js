/* Private staff forms. No dates or booking decisions are trusted to the browser. */
'use strict';
(() => {
 const config=window.CSFStaff;
 if(!config)return;
 const clone=x=>JSON.parse(JSON.stringify(x));
 async function request(op,values={}){
  let response,result;try{response=await fetch(config.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'csf_schedule_tools',_wpnonce:config.nonce,op,...values})});}catch(error){throw new Error('Connection interrupted. Your edits are still here. Check your connection, then check the schedule again. No booking request was repeated.');}
  try{result=await response.json();}catch(error){throw new Error('The calendar could not return a readable result. Your edits are still here. Check your session or ask the pilot operator before trying again.');}
  if(!response.ok||!result.success)throw new Error(result?.data?.message||'Your session may have expired. Preserve your draft, refresh and sign in again.');
  return result.data;
 }
 function read(scope,seed={}){
  const out=clone(seed),owned=el=>el.closest('.csf-row')===(scope.classList.contains('csf-row')?scope:null);
  scope.querySelectorAll('[data-field]').forEach(el=>{if(owned(el))out[el.dataset.field]=el.type==='checkbox'?el.checked:el.value;});
  scope.querySelectorAll('[data-time]').forEach(el=>{if(!owned(el))return;const select=el.closest('.csf-time').querySelector('[data-fold]');out[el.dataset.time]=el.value?{local:el.value,choice:select.value||null}:null;});
  scope.querySelectorAll('[data-array]').forEach(group=>{if(group.dataset.array!=='pool'){const key=group.dataset.array,selected=Array.from(group.querySelectorAll('input:checked'),el=>el.value),previous=Array.isArray(out[key])?out[key]:[];out[key]=previous.filter(id=>selected.includes(id)).concat(selected.filter(id=>!previous.includes(id)));}});
  if(out.resource_uuid==='')out.resource_uuid=null;
  if(out.rider==='')out.rider=null;
  return out;
 }
 function body(form){
  const original=JSON.parse(form.elements.body.value),kind=form.dataset.kind;
  const out=original.data?{...original,data:read(form,original.data)}:read(form,original);
  const rows=form.querySelector('.csf-rows');
  if(rows){const prior=original.rides||original.data?.intervals||[],values=Array.from(rows.children,(el,index)=>read(el,prior[index]||{}));if(rows.dataset.rows==='rides')out.rides=values;else out.data.intervals=values;}
  if(kind==='appointment_saved'&&out.state!=='held')out.hold_expires=null;
  return out;
 }
 async function resolveTime(input){
  if(!input.value)return;
  const wrapper=input.closest('.csf-time'),select=wrapper.querySelector('[data-fold]'),status=wrapper.querySelector('.csf-time-status'),old=select.value;
  const local=input.value,result=await request('times',{local});if(input.value!==local)return;
  select.replaceChildren();wrapper.querySelector('.csf-fold').hidden=result.choices.length<2;
  if(!result.choices.length){select.add(new Option('Choose a different local time',''));status.textContent='This time does not exist when clocks go forward.';return;}
  if(result.choices.length>1)select.add(new Option('Choose first or second occurrence',''));
  result.choices.forEach((iso,i)=>select.add(new Option((result.choices.length>1?(i===0?'First occurrence':'Second occurrence'):'Validated local time')+' · '+iso.slice(-6),iso)));
  if(result.choices.includes(old))select.value=old;
  status.textContent=result.choices.length>1?'This time occurs twice. Choose explicitly.':'Server-validated in '+config.timezone;
 }
 function minutes(local){return Date.parse(local+'Z')/60000;}
 function localPlus(local,n){const stamp=minutes(local);return Number.isFinite(stamp)?new Date((stamp+n)*60000).toISOString().slice(0,16):'';}
 document.querySelectorAll('.csf-editor').forEach(form=>{
  let previewHash=null,busy=false;let shiftedStart=form.querySelector('[data-time=start]')?.value;
  const feedback=form.querySelector('.csf-feedback'),save=form.querySelector('.csf-save'),preview=form.querySelector('.csf-preview');
  function invalidate(){previewHash=null;if(preview)save.disabled=true;}
  function impact(){
   if(!preview)return;
   const b=body(form),service=config.services[b.service_type],totals=new Map();
   b.rides.forEach(ride=>{const n=minutes(ride.end?.local)-minutes(ride.start?.local);if(Number.isFinite(n))totals.set(ride.horse,(totals.get(ride.horse)||0)+n);});
   const names=Array.from(totals,([id,n])=>{const option=form.querySelector('[data-field=horse] option[value="'+CSS.escape(id)+'"]');return (option?.textContent||'Horse')+': '+n+' local-clock minutes';});
   form.querySelector('.csf-impact').textContent=b.riders.length+' riders / capacity '+(service?.capacity??'not configured')+' · service duration '+(service?.duration_minutes??'unknown')+' minutes. Required: '+(service?.required_resources||[]).join(', ')+'.\n'+names.join('\n')+'\nHorse policy: daily '+(config.workload.daily_minutes??'unknown')+', consecutive '+(config.workload.consecutive_minutes??'unknown')+', recovery break '+(config.workload.break_minutes??'unknown')+' minutes. Server preview checks actual instants, travel and existing workloads.';
  }
  form.addEventListener('input',e=>{invalidate();if(e.target.matches('[data-time]')){const sel=e.target.closest('.csf-time').querySelector('[data-fold]');sel.replaceChildren(new Option('Check local time before saving',''));}impact();});
  form.addEventListener('change',e=>{invalidate();if(e.target.matches('[data-time]'))resolveTime(e.target).catch(err=>{feedback.textContent=err.message;});impact();});
  form.addEventListener('click',e=>{
   if(e.target.matches('.csf-shift')){
    const start=form.querySelector('[data-time=start]').value,delta=minutes(start)-minutes(shiftedStart);
    if(!Number.isFinite(delta)){feedback.textContent='Enter a valid new appointment start first.';return;}
    form.querySelectorAll('[data-time]').forEach(input=>{if(input.dataset.time==='hold_expires'||input===form.querySelector('[data-time=start]'))return;input.value=localPlus(input.value,delta);input.closest('.csf-time').querySelector('[data-fold]').replaceChildren(new Option('Recheck moved local time',''));});shiftedStart=start;invalidate();impact();
   }
   if(e.target.matches('.csf-remove-row')){const fieldset=e.target.closest('.csf-row');const focus=form.querySelector('.csf-add-row');fieldset.remove();invalidate();impact();focus.focus();}
   if(e.target.matches('.csf-add-row')){
    const row=form.querySelector('.csf-row-template').content.firstElementChild.cloneNode(true),container=form.querySelector('.csf-rows'),previous=container.lastElementChild;
    if(preview){
     const start=form.querySelector('[data-time=start]').value,end=form.querySelector('[data-time=end]').value,buffer=Number(form.querySelector('[data-recovery]').value||0);
     const next=previous?localPlus(previous.querySelector('[data-time=end]').value,buffer):start;
     row.querySelector('[data-time=start]').value=next;row.querySelector('[data-time=end]').value=previous?localPlus(next,30):end;
    }
    container.append(row);invalidate();impact();row.querySelector('select,input').focus();
   }
  });
  if(preview)preview.addEventListener('click',async()=>{
   if(busy||!form.reportValidity())return;
   busy=true;preview.disabled=true;save.disabled=true;feedback.textContent='Checking local times, reservations and horse welfare…';
   try{
    await Promise.all(Array.from(form.querySelectorAll('[data-time]')).filter(el=>el.value).map(resolveTime));
    const b=body(form),pool=Array.from(form.querySelector('[data-array=pool]').querySelectorAll('input:checked'),el=>el.value);
    if(b.rides.some(ride=>!pool.includes(ride.horse)))throw new Error('Choose each assigned horse in the horse pool before previewing.');
    const hash=JSON.stringify(b),result=await request('preview',{body:hash,facility_revision:form.elements.facility_revision.value,command_id:form.elements.command_id.value});
    if(JSON.stringify(body(form))!==hash)throw new Error('The draft changed during checking. Preview again.');
    feedback.textContent=result.message+'\nValidated times: '+result.times.join(' – ')+'\n'+result.impact;previewHash=hash;save.disabled=false;
   }catch(error){previewHash=null;feedback.textContent=error.message;}
   finally{busy=false;preview.disabled=false;}
  });
  form.addEventListener('submit',event=>{
   const b=body(form),hash=JSON.stringify(b);
   if(preview&&hash!==previewHash){event.preventDefault();save.disabled=true;feedback.textContent='Check this schedule before saving. No appointment was saved.';return;}
   form.elements.body.value=hash;save.disabled=true;
  });
  impact();
 });
})();
