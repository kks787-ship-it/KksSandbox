/* Routine lessons use the existing authenticated preview and command service. */
'use strict';
(() => {
 const config=window.CSFStaff;if(!config)return;
 async function request(op,values){
  let response,result;
  try{response=await fetch(config.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'csf_schedule_tools',_wpnonce:config.nonce,op,...values})});result=await response.json();}
  catch(error){throw new Error('Connection interrupted. Your draft is still here. No booking request was repeated.');}
  if(!response.ok||!result.success)throw new Error(result?.data?.message||'Your session may have expired. Preserve your draft and sign in again.');
  return result.data;
 }
 const display=iso=>new Intl.DateTimeFormat('en-US',{timeZone:config.timezone,month:'short',day:'numeric',hour:'numeric',minute:'2-digit',timeZoneName:'short'}).format(new Date(iso));
 document.querySelectorAll('.csf-routine-editor').forEach(form=>{
  const original=JSON.parse(form.elements.body.value),save=form.querySelector('.csf-save'),preview=form.querySelector('.csf-preview'),load=form.querySelector('.csf-load-slots'),feedback=form.querySelector('.csf-feedback'),date=form.querySelector('.csf-lesson-date'),slot=form.querySelector('.csf-start-slot'),start=form.querySelector('.csf-routine-start'),summary=form.querySelector('.csf-routine-summary'),expiry=form.querySelector('[data-time=hold_expires]'),fold=form.querySelector('[data-fold]');
  const more=form.querySelector('.csf-more-slots');
  let generation=0,previewHash=null,busy=false,slots=[],next=null,pending=null,dirty=false;
  function value(key){return form.querySelector('[data-field='+key+']').value;}
  function body(){return {...original,_routine:true,title:value('title'),service_type:value('service_type'),state:value('state'),single_rider:value('single_rider'),single_horse:value('single_horse'),resources:Array.from(form.querySelectorAll('[data-array=resources] input:checked'),el=>el.value),riders:[],rides:[],start:start.value?{local:start.value,choice:start.dataset.choice||null}:null,end:null,hold_expires:value('state')==='held'&&expiry.value?{local:expiry.value,choice:fold.value||null}:null};}
  function visibility(){const held=value('state')==='held',horse=config.services[value('service_type')]?.horse_source!=='none';form.querySelector('.csf-routine-hold').hidden=!held;expiry.disabled=!held;fold.disabled=!held;expiry.required=held;form.querySelector('.csf-routine-horse').hidden=!horse;}
  function invalidate(clear=true){generation++;previewHash=null;save.disabled=true;if(clear){slots=[];next=null;more.hidden=true;slot.replaceChildren(new Option('Check the selected date and resources first',''));start.value='';start.dataset.choice='';summary.textContent='Choose a start to calculate the finish. Long lessons may require shorter turns in Advanced rotation.';}visibility();}
  form.addEventListener('input',event=>{if(event.target===slot)return;dirty=true;if(event.target===date)pending=null;invalidate();if(event.target===expiry)fold.replaceChildren(new Option('Validate hold expiry before saving',''));});
  form.addEventListener('change',event=>{dirty=true;if(event.target===date)pending=null;if(event.target!==slot)invalidate();});
  slot.addEventListener('change',()=>{invalidate(false);const choice=slots.find(item=>item.choice===slot.value&&item.available);start.value=choice?.local||'';start.dataset.choice=choice?.choice||'';summary.textContent=choice?display(choice.choice)+' → '+display(choice.end)+' · '+config.services[value('service_type')].duration_minutes+' elapsed minutes. Horse turn is calculated automatically. Availability is advisory until saved.':'Choose an available start.';});
  async function holdTime(){if(value('state')!=='held'||!expiry.value)return;const old=fold.value,local=expiry.value,result=await request('times',{local});if(local!==expiry.value)throw new Error('Hold expiry changed. Check again.');fold.replaceChildren();form.querySelector('.csf-fold').hidden=result.choices.length<2;if(result.choices.length!==1)fold.add(new Option('Choose first or second occurrence',''));result.choices.forEach((iso,i)=>fold.add(new Option((i===0?'First':'Second')+' occurrence · '+iso.slice(-6),iso)));if(result.choices.includes(old))fold.value=old;if(!result.choices.length)throw new Error('Hold expiry does not exist when clocks go forward.');}
  async function loadPage(offset=0,reset=offset===0){
   if(busy)return;
   if(!date.reportValidity())return;
   busy=true;load.disabled=true;more.disabled=true;preview.disabled=true;invalidate(reset);const version=generation;
   feedback.textContent='Checking the date, selected resources and horse welfare…';
   try{await holdTime();const wire=JSON.stringify(body()),result=await request('booking_slots',{body:wire,day:date.value,offset:String(offset),facility_revision:form.elements.facility_revision.value});if(version!==generation||wire!==JSON.stringify(body()))throw new Error('The draft changed during checking. Show start slots again.');slots.push(...result.slots);next=result.next;more.hidden=next===null;if(reset)slot.replaceChildren(new Option('Choose an available start',''));const reasons=form.querySelector('.csf-slot-reasons ul');if(reset)reasons.replaceChildren();result.slots.forEach(item=>{const option=new Option(item.label+(item.available?'':' — unavailable'),item.choice);option.disabled=!item.available;slot.add(option);if(!item.available){const li=document.createElement('li');li.textContent=item.label+': '+item.reason;reasons.append(li);}});const preselected=pending&&slots.find(item=>item.choice===pending.choice&&item.available);if(preselected){slot.value=preselected.choice;slot.dispatchEvent(new Event('change',{bubbles:true}));pending=null;}feedback.textContent=slots.some(item=>item.available)?'Choose a start, then check the schedule before saving.'+(next!==null?' Show later starts for the rest of the day.':''):(slots.length?'No available starts in this part of the day. Review the reasons below, show later starts, or use Advanced rotation for shorter horse turns.':'No opening hours on this date. Choose another date.');}
   catch(error){feedback.textContent=error.message;}
   finally{busy=false;load.disabled=false;more.disabled=false;preview.disabled=false;}
  }
  load.addEventListener('click',()=>loadPage(pending?Number(pending.local.slice(11,13))*4+Number(pending.local.slice(14,16))/15:0,true));
  more.addEventListener('click',()=>{if(next!==null)loadPage(next);});
  preview.addEventListener('click',async()=>{
   if(busy||!form.reportValidity())return;busy=true;preview.disabled=true;load.disabled=true;save.disabled=true;feedback.textContent='Checking the final lesson and horse turn…';
   try{await holdTime();const wire=JSON.stringify(body()),version=generation,result=await request('preview',{body:wire,facility_revision:form.elements.facility_revision.value,command_id:form.elements.command_id.value});if(version!==generation||wire!==JSON.stringify(body()))throw new Error('The draft changed during checking. Check again.');previewHash=wire;save.disabled=false;feedback.textContent=result.message+'\n'+display(result.times[0])+' → '+display(result.times[1])+'\n'+result.impact;}
   catch(error){previewHash=null;feedback.textContent=error.message;}
   finally{busy=false;preview.disabled=false;load.disabled=false;}
  });
  form.addEventListener('submit',event=>{const wire=JSON.stringify(body());if(wire!==previewHash){event.preventDefault();save.disabled=true;feedback.textContent='Check this schedule before saving. No appointment was saved.';return;}form.elements.body.value=wire;save.disabled=true;load.disabled=true;preview.disabled=true;});
  form.addEventListener('csf:prefill',event=>{
   const intent=event.detail;
   if(dirty&&!window.confirm('Use this selection in the current unsaved lesson draft? Your previous preview will be cleared.'))return;
   invalidate();
   if(intent.fields){for(const[key,v]of Object.entries(intent.fields)){const el=form.querySelector('[data-field='+CSS.escape(key)+']');if(el)el.value=v;}for(const box of form.querySelectorAll('[data-array=resources] input'))box.checked=intent.resources.includes(box.value);}
   date.value=intent.local.slice(0,10);
   if(intent.resource){const horse=form.querySelector('[data-field=single_horse]');if(Array.from(horse.options).some(o=>o.value===intent.resource)){horse.value=intent.resource;}else{const kinds=config.resourceKinds||{},kind=kinds[intent.resource];for(const box of form.querySelectorAll('[data-array=resources] input')){if(kind&&kinds[box.value]===kind)box.checked=box.value===intent.resource;}}}
   pending={local:intent.local,choice:intent.choice};dirty=true;visibility();summary.textContent='Requested start: '+display(intent.choice)+'. Complete the lesson details, then show start slots to check this choice. No time is reserved.';feedback.textContent='Calendar selection copied to this draft. Review every field before previewing.';form.scrollIntoView({block:'start'});form.querySelector('[data-field=title]').focus();
  });
  visibility();
 });
})();
