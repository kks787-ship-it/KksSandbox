/* Routine lessons use the existing authenticated preview and command service. */
'use strict';
(() => {
 const config=window.CSFStaff;if(!config)return;
 async function request(op,values){
  let response,result;
  try{response=await fetch(config.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'csf_schedule_tools',_wpnonce:config.nonce,op,...values})});result=await response.json();}
  catch(cause){const error=new Error('Connection interrupted. Your selected time and draft are retained. No booking request was repeated.');error.uncertain=true;throw error;}
  if(!result||typeof result!=='object'||typeof result.success!=='boolean'){const error=new Error('The calendar response could not be verified. Your selected time is retained; check your session and retry the read-only check.');error.uncertain=true;throw error;}
  if(!response.ok||!result.success){const error=new Error(result?.data?.message||'Your session may have expired. Preserve your draft and sign in again.');error.uncertain=response.status===401||response.status===403||response.status>=500;throw error;}
  return result.data;
 }
 const display=iso=>new Intl.DateTimeFormat('en-US',{timeZone:config.timezone,month:'short',day:'numeric',hour:'numeric',minute:'2-digit',timeZoneName:'short'}).format(new Date(iso));
 document.querySelectorAll('.csf-routine-editor').forEach(form=>{
  const original=JSON.parse(form.elements.body.value),save=form.querySelector('.csf-save'),preview=form.querySelector('.csf-preview'),load=form.querySelector('.csf-load-slots'),more=form.querySelector('.csf-more-slots'),feedback=form.querySelector('.csf-feedback'),date=form.querySelector('.csf-lesson-date'),slot=form.querySelector('.csf-start-slot'),start=form.querySelector('.csf-routine-start'),summary=form.querySelector('.csf-routine-summary'),expiry=form.querySelector('[data-time=hold_expires]'),fold=form.querySelector('[data-fold]');
  const warning=document.createElement('label');warning.className='csf-clearance-ack';warning.hidden=true;warning.innerHTML='<input type="checkbox"> I understand this reservation does not clear the rider to participate.';feedback.after(warning);const acknowledge=warning.querySelector('input');acknowledge.addEventListener('change',controls);
  let selected=start.value?{local:start.value,choice:start.dataset.choice||'',resolve:!start.dataset.choice}:null;
  let generation=0,previewHash=null,validatedKey=null,lastAttempt=null,running=false,queued=false,timer=null,dirty=false,slots=[],next=null,clockChoices=[],checkedEnd=null,active=false,lastLane='';
  const value=key=>form.querySelector('[data-field='+key+']').value;
  const clock=form.querySelector('.csf-lesson-clock');
  function title(){return value('title').trim()||((form.querySelector('[data-field=service_type] option:checked')?.textContent||'Lesson')+' · '+(form.querySelector('[data-field=single_rider] option:checked')?.textContent||'Rider')).slice(0,180);}
  const service=()=>config.services[value('service_type')];
  function body(){return {...original,_routine:true,title:title(),service_type:value('service_type'),state:value('state'),single_rider:value('single_rider'),single_horse:value('single_horse'),resources:Array.from(form.querySelectorAll('[data-array=resources] input:checked'),el=>el.value),riders:[],rides:[],start:selected?{local:selected.local,choice:selected.choice||null}:null,end:null,hold_expires:value('state')==='held'&&expiry.value?{local:expiry.value,choice:fold.value||null}:null};}
  function key(){const b=body();delete b.title;return JSON.stringify(b);}
  function missing(){
   const b=body(),s=service(),items=[];
   if(!selected)items.push('a start time');else if(!selected.choice)items.push(clockChoices.length>1?'first or second clock-change occurrence':'a valid local time');
   if(!s?.enabled||!Number.isInteger(s.duration_minutes)||s.duration_minutes<15||s.duration_minutes%15)items.push('an enabled lesson type');
   if(!b.single_rider)items.push('rider');if(s&&s.horse_source!=='none'&&!b.single_horse)items.push('horse');
   for(const kind of s?.required_resources||[]){if(kind!=='horse'&&!b.resources.some(id=>config.resourceKinds?.[id]===kind))items.push(kind);}
   if(b.state==='held'&&!b.hold_expires)items.push('hold expiry');return items;
  }
  function controls(){save.textContent=original.expected?'Save lesson':value('state')==='confirmed'?'Schedule lesson':value('state')==='held'?'Hold lesson':'Request lesson (not reserved)';save.disabled=running||(!warning.hidden&&!acknowledge.checked)||previewHash!==JSON.stringify(body())||validatedKey!==key();preview.disabled=running||validatedKey!==key();load.disabled=running;more.disabled=running;load.textContent=form.dataset.slotStatus==='error'?'Retry selected time check':selected?'Browse alternative starts':'Show 15-minute start slots';}
  function visibility(){const held=value('state')==='held';const stateInfo=form.querySelector('.csf-reservation-state');if(stateInfo)stateInfo.textContent=held?'Held — temporary reservation. An expiry is required. For a normal reservation, choose Confirmed if available.':value('state')==='confirmed'?'Confirmed — reserves the time when saved; rider clearance is separate.':'Requested — does not reserve time.';form.querySelector('.csf-routine-hold').hidden=!held;expiry.disabled=!held;fold.disabled=!held;expiry.required=held;form.querySelector('.csf-routine-horse').hidden=service()?.horse_source==='none';}
  function showSelection(){if(clockChoices.length>1)form.querySelector('.csf-time-alternatives').open=true;
   start.value=selected?.local||'';start.dataset.choice=selected?.choice||'';
   const keep=selected?(selected.choice||selected.local):'';slot.replaceChildren();
   if(selected){const status=form.dataset.slotStatus||'pending';slot.add(new Option((selected.choice?display(selected.choice):selected.local.replace('T',' '))+' · '+(status==='available'?'checked':status==='unavailable'?'unavailable':'selected, awaiting check'),keep));}
   else slot.add(new Option('Choose a start, or click a calendar time block',''));
   for(const choice of clockChoices){if(choice===keep)continue;const option=new Option(display(choice)+' · choose clock-change occurrence',choice);option.dataset.clock='1';slot.add(option);}
   for(const item of slots){if(item.choice===keep)continue;const option=new Option(item.label+(item.available?'':' — unavailable'),item.choice);option.disabled=!item.available;slot.add(option);}
   slot.value=keep;
   if(selected?.choice){const minutes=service()?.duration_minutes,end=checkedEnd|| (Number.isInteger(minutes)?new Date(Date.parse(selected.choice)+minutes*60000).toISOString():null);summary.textContent=(value('state')==='requested'?'Requested — no time will be reserved. ':value('state')==='confirmed'?'Confirmed — reserves time when saved. ':'Held — temporary reservation when saved. ')+'Selected: '+display(selected.choice)+(end?' → '+display(end)+' · '+minutes+' elapsed teaching minutes':'')+'. '+(validatedKey===key()?'Availability checked. Review the details below, then save.':'Availability has not been confirmed.')+' No time is reserved.';}
   else summary.textContent=selected?'Selected local time: '+selected.local.replace('T',' ')+'. Choose an explicit valid occurrence; no time is reserved.':'Choose a start to calculate the finish. Long horse turns may need Advanced rotation.';
  }
  function invalidate(){generation++;previewHash=null;validatedKey=null;lastAttempt=null;checkedEnd=null;slots=[];next=null;more.hidden=true;form.dataset.slotStatus='pending';visibility();showSelection();controls();}
  function pendingMessage(){const items=missing();if(items.length){form.dataset.slotStatus='needs-details';feedback.textContent='Selected time retained. Choose '+items.join(', ')+'. Availability will be checked automatically.';showSelection();controls();return true;}return false;}
  function schedule(delay=250){if(!active||!selected)return;clearTimeout(timer);timer=setTimeout(()=>{if(running){queued=true;return;}automatic();},delay);}
  async function holdTime(){
   if(value('state')!=='held'||!expiry.value)return;
   const local=expiry.value,old=fold.value,version=generation,result=await request('times',{local});
   if(version!==generation||local!==expiry.value)return false;
   fold.replaceChildren();form.querySelector('.csf-fold').hidden=result.choices.length<2;
   if(result.choices.length!==1)fold.add(new Option('Choose first or second occurrence',''));
   result.choices.forEach((iso,i)=>fold.add(new Option((i===0?'First':'Second')+' occurrence · '+iso.slice(-6),iso)));if(result.choices.includes(old))fold.value=old;
   if(!result.choices.length)throw new Error('Hold expiry does not exist when clocks go forward.');return true;
  }
  async function alternatives(offset=0,append=false,context=key()){
   const result=await request('booking_slots',{body:JSON.stringify(body()),day:date.value,offset:String(offset),facility_revision:form.elements.facility_revision.value});
   if(context!==key())return;
   slots=append?slots.concat(result.slots):result.slots;next=result.next;more.hidden=next===null;
   const reasons=form.querySelector('.csf-slot-reasons ul');if(!append)reasons.replaceChildren();for(const item of result.slots){if(!item.available){const li=document.createElement('li');li.textContent=item.label+': '+item.reason;reasons.append(li);}}
   showSelection();
  }
  function finish(){running=false;controls();if(queued){queued=false;schedule(0);}}
  async function automatic(){
   if(!selected||running)return;
   if(selected.resolve){
    running=true;controls();const local=selected.local;
    try{const result=await request('times',{local});if(selected?.local!==local||!selected.resolve)return;selected.resolve=false;clockChoices=result.choices;if(result.choices.length===1){selected.choice=result.choices[0];clockChoices=[];lastAttempt=null;queued=true;}else{selected.choice='';form.dataset.slotStatus=result.choices.length?'needs-details':'unavailable';feedback.textContent=result.choices.length?'This local time occurs twice. Choose the first or second occurrence.':'The selected local time does not exist when clocks go forward. Choose another time.';}showSelection();}
    catch(error){if(selected?.local===local){selected.resolve=false;form.dataset.slotStatus='error';feedback.textContent=error.message;}}
    finally{finish();}return;
   }
   if(pendingMessage())return;
   let context=key();if(lastAttempt===context)return;
   running=true;form.dataset.slotStatus='checking';controls();showSelection();feedback.textContent='Checking selected time, resources and horse welfare…';
   try{
    if(await holdTime()===false){queued=true;return;}
    context=key();const checkedContext=context,probe=body(),wire=JSON.stringify(probe);
    const result=await request('preview',{body:JSON.stringify(probe),facility_revision:form.elements.facility_revision.value,command_id:crypto.randomUUID()});
    if(checkedContext!==key())return;
    if(result.times[0]!==selected.choice)throw new Error('The selected clock offset changed. Choose the explicit local occurrence again.');
    if(wire!==JSON.stringify(body())){queued=true;return;}warning.hidden=result.rider_cleared!==false;acknowledge.checked=false;validatedKey=checkedContext;previewHash=wire;lastAttempt=checkedContext;checkedEnd=result.times[1];form.dispatchEvent(new CustomEvent('csf:reviewed-appointment',{bubbles:true,detail:{body:JSON.parse(wire),revision:Number(form.elements.facility_revision.value)}}));form.dataset.slotStatus='available';feedback.textContent='Availability checked. '+result.impact+(result.rider_cleared===false?' PRE-RIDE BLOCK: rider clearance incomplete. A confirmed reservation does not clear this rider to participate.':'')+' '+(value('state')==='requested'?'This request will NOT reserve time.':'Saving rechecks all resources. No reservation exists until you save.');showSelection();
   }catch(error){
    if(context!==key())return;
    lastAttempt=context;form.dataset.slotStatus=error.uncertain?'error':'unavailable';feedback.textContent=error.message;showSelection();
    if(!error.uncertain){try{await alternatives(Math.max(0,Number(selected.local.slice(11,13))*4+Number(selected.local.slice(14,16))/15-2),false,context);if(context===key())feedback.textContent=error.message+' Select a suggested alternative, change resources, or use Advanced rotation. Your original time has not moved.';}catch(lookup){if(context===key())feedback.textContent=error.message+' Alternatives could not be checked. Your selection is retained.';}}
   }finally{finish();}
  }
  function changed(event){
   if(event.target===slot||event.target===acknowledge)return;
   dirty=true;active=true;
   if(event.target===date&&selected){clockChoices=[];selected={local:date.value+'T'+selected.local.slice(11,16),choice:'',resolve:true};}
   if(event.target===expiry)fold.replaceChildren(new Option('Validate hold expiry before saving',''));
   // A title affects final preview, but does not change resource availability.
   if(event.target.matches('[data-field=title]')){invalidate();lastAttempt=null;schedule();return;}
   invalidate();if(selected){pendingMessage();schedule();}
  }
  form.addEventListener('input',changed);form.addEventListener('change',changed);
  slot.addEventListener('change',()=>{
   const iso=slot.value,item=slots.find(x=>x.choice===iso&&x.available),clock=clockChoices.includes(iso);
   if(!item&&!clock)return;
   selected={local:item?.local||selected.local,choice:iso,resolve:false};clockChoices=[];active=true;dirty=true;invalidate();
   if(item){validatedKey=key();lastAttempt=key();checkedEnd=item.end;form.dataset.slotStatus='pending';feedback.textContent='Alternative selected. Checking the exact lesson…';lastAttempt=null;showSelection();controls();schedule(0);}else schedule(0);
  });
  async function browse(offset=0,append=false){
   if(running||!date.reportValidity())return;
   if(selected&&form.dataset.slotStatus==='error'){lastAttempt=null;if(!selected.choice)selected.resolve=true;schedule(0);return;}
   if(selected&&pendingMessage())return;
   running=true;controls();const context=key();
   try{await alternatives(offset,append,context);if(context===key())feedback.textContent=slots.some(x=>x.available)?'Suggested starts checked. Your selected time is unchanged; choose an alternative only if you want to move it.':'No available starts for these details. Review the reasons or use Advanced rotation.';}
   catch(error){if(context===key())feedback.textContent=error.message;}finally{finish();}
  }
  load.addEventListener('click',()=>browse(selected?Math.max(0,Number(selected.local.slice(11,13))*4+Number(selected.local.slice(14,16))/15-2):0));
  more.addEventListener('click',()=>{if(next!==null)browse(next,true);});
  preview.addEventListener('click',async()=>{
   if(running||validatedKey!==key()||!form.reportValidity())return;
   running=true;controls();feedback.textContent='Checking the final lesson and horse turn…';
   try{const wire=JSON.stringify(body()),version=generation,result=await request('preview',{body:wire,facility_revision:form.elements.facility_revision.value,command_id:form.elements.command_id.value});if(version!==generation||wire!==JSON.stringify(body())){feedback.textContent='Your draft changed during the final check. Review it and check again; nothing was saved.';return;}previewHash=wire;feedback.textContent=result.message+'\n'+display(result.times[0])+' → '+display(result.times[1])+'\n'+result.impact;}
   catch(error){previewHash=null;feedback.textContent=error.message;}finally{finish();}
  });
  form.addEventListener('submit',event=>{const wire=JSON.stringify(body());if(wire!==previewHash||validatedKey!==key()||running||(!warning.hidden&&!acknowledge.checked)){event.preventDefault();save.disabled=true;feedback.textContent='Check this schedule before saving. No appointment was saved.';return;}form.elements.body.value=wire;running=true;clearTimeout(timer);controls();});
  form.addEventListener('csf:prefill',event=>{
   const intent=event.detail;
   const resourceSelected=!intent.resource||value('single_horse')===intent.resource||Array.from(form.querySelectorAll('[data-array=resources] input:checked')).some(el=>el.value===intent.resource);
   const same=selected?.choice===intent.choice&&selected.local===intent.local&&lastLane===(intent.resource||'')&&resourceSelected;
   if(!intent.fields&&same){form.dispatchEvent(new CustomEvent('csf:edit',{bubbles:true}));return;}
   if(dirty&&!window.confirm('Use this selection in the current unsaved lesson draft? Your previous preview will be cleared.'))return;
   if(intent.fields){for(const[k,v]of Object.entries(intent.fields)){const el=form.querySelector('[data-field='+CSS.escape(k)+']');if(el)el.value=v;}for(const box of form.querySelectorAll('[data-array=resources] input'))box.checked=intent.resources.includes(box.value);}
   date.value=intent.local.slice(0,10);if(clock)clock.value=intent.local.slice(11,16);lastLane=intent.resource||'';
   if(intent.resource){const horse=form.querySelector('[data-field=single_horse]');if(Array.from(horse.options).some(o=>o.value===intent.resource))horse.value=intent.resource;else{const kind=config.resourceKinds?.[intent.resource];for(const box of form.querySelectorAll('[data-array=resources] input')){if(kind&&config.resourceKinds[box.value]===kind)box.checked=box.value===intent.resource;}}}
   selected={local:intent.local,choice:intent.choice||'',resolve:!intent.choice};clockChoices=[];dirty=true;active=true;invalidate();pendingMessage();schedule(0);form.dispatchEvent(new CustomEvent('csf:edit',{bubbles:true}));if(!form.closest('dialog'))form.scrollIntoView({block:'start'});form.querySelector('[data-field=service_type]').focus();
  });
  form.closest('details')?.addEventListener('toggle',()=>{if(form.closest('details').open&&selected){active=true;schedule(0);}});
  clock?.addEventListener('change',()=>{if(!clock.value||!date.value)return;selected={local:date.value+'T'+clock.value,choice:'',resolve:true};clockChoices=[];active=true;dirty=true;invalidate();pendingMessage();schedule(0);});
  form.addEventListener('csf:activate',()=>{active=true;lastAttempt=null;if(selected)schedule(0);});
  visibility();showSelection();controls();
 });
})();
