/* Compact shell over the existing native forms, previews and transactional POSTs. */
(() => {
 'use strict';const root=document.querySelector('.csf-calendar');if(!root)return;
 root.classList.add('csf-ux-ready');const calendar=document.querySelector('#csf-visual-planner');
 const dialog=document.createElement('dialog');dialog.id='csf-task-dialog';dialog.setAttribute('aria-label','Schedule lesson or block time');dialog.innerHTML='<button type="button" class="button csf-task-close">Close panel</button><div class="csf-task-body"></div>';root.append(dialog);
 let moved=null,placeholder=null,opener=null;
 function restore(){if(moved&&placeholder){placeholder.replaceWith(moved);moved=null;placeholder=null;}}
 function close(){if(dialog.open)dialog.close();restore();opener?.focus();}
 dialog.addEventListener('close',()=>{if(dialog.open)return;restore();opener?.focus();});
 dialog.querySelector('.csf-task-close').addEventListener('click',close);
 function open(node,focus){if(!node)return;if(moved===node&&dialog.open)return;const previous=document.querySelector('#csf-planner-dialog');if(previous?.open)previous.close();close();opener=focus||document.activeElement;placeholder=document.createComment('Compact panel origin');node.replaceWith(placeholder);moved=node;dialog.querySelector('.csf-task-body').append(node);dialog.showModal();dialog.scrollTop=0;node.querySelector('select,input:not([type=hidden]),button')?.focus();}
 document.addEventListener('csf:edit',event=>{if(event.target.id==='csf-new-appointment')open(document.querySelector('#csf-create'));});
 const block=document.querySelector('#csf-block-panel'),resource=block?.querySelector('.csf-block-resource');
 function selectResource(id){if(!block)return;if(id&&[...resource.options].some(x=>x.value===id))resource.value=id;block.querySelectorAll('.csf-resource-panel').forEach(p=>{p.hidden=p.dataset.resource!==resource.value;p.open=!p.hidden;});const review=block.querySelector('.csf-resource-review');if(review)review.hidden=review.dataset.resource!==resource.value;block.querySelectorAll('[name=calendar_resource]').forEach(x=>x.value=resource.value);}
 resource?.addEventListener('change',()=>selectResource(resource.value));if(resource)selectResource(block.querySelector('.csf-resource-review')?.dataset.resource||resource.value);
 document.addEventListener('click',event=>{
  const newLesson=event.target.closest('.csf-new-lesson');if(newLesson){event.preventDefault();open(document.querySelector('#csf-create'),newLesson);return;}
  const blockAction=event.target.closest('.csf-block-time');if(blockAction){event.preventDefault();open(block,blockAction);return;}
  const cell=event.target.closest('.csf-resource-slot');if(cell&&block){if(event.defaultPrevented)return;selectResource(cell.dataset.resource);open(block,cell);return;}
  const advanced=event.target.closest('.csf-advanced-rotation summary');if(advanced&&advanced.closest('#csf-create')&&!dialog.open)open(document.querySelector('#csf-create'),advanced);
 });
 if(block&&calendar){const button=document.createElement('button');button.type='button';button.className='button csf-pick-block';button.textContent='Select a range on the calendar';resource.closest('label').after(button);button.addEventListener('click',()=>{const id=resource.value,cells=[...calendar.querySelectorAll('.csf-resource-slot')].filter(x=>x.dataset.resource===id);if(!cells.length){const url=new URL(location.href);url.searchParams.set('resource',id);url.searchParams.set('tool','block');url.searchParams.set('screen','calendar');location.assign(url);return;}close();const mode=calendar.querySelector('.csf-resource-mode');if(mode.getAttribute('aria-pressed')!=='true')mode.click();calendar.querySelector('.csf-resource-range').checked=true;cells.find(x=>x.getClientRects().length)?.focus();});
  if(new URL(location.href).searchParams.get('tool')==='block'){const mode=calendar.querySelector('.csf-resource-mode');if(mode?.getAttribute('aria-pressed')!=='true')mode?.click();}
  if(block.querySelector('.csf-resource-review')||block.querySelector('#csf-resource-management > [role=status]'))open(block);
 }
 for(const form of root.querySelectorAll('.csf-routine-editor')){
  const kinds=window.CSFStaff.resourceKinds||{};
  function sync(){for(const select of form.querySelectorAll('[data-resource-kind]')){const checked=[...form.querySelectorAll('[data-array=resources] input:checked')].filter(x=>kinds[x.value]===select.dataset.resourceKind);select.value=checked[0]?.value||'';}}
  for(const select of form.querySelectorAll('[data-resource-kind]')){select.addEventListener('change',()=>{form.querySelectorAll('[data-array=resources] input').forEach(x=>{if(kinds[x.value]===select.dataset.resourceKind)x.checked=x.value===select.value;});form.dispatchEvent(new Event('change',{bubbles:true}));});}
  form.addEventListener('csf:prefill',()=>queueMicrotask(sync));form.addEventListener('change',sync);
  if(form.id==='csf-new-appointment'){for(const select of form.querySelectorAll('[data-resource-kind]')){const options=[...select.options].filter(x=>x.value&&!x.disabled);if(options.length===1){select.value=options[0].value;form.querySelector('[data-array=resources] input[value="'+CSS.escape(select.value)+'"]').checked=true;}}}sync();
  form.querySelector('.csf-preview').hidden=true;
  // Opening an existing simple lesson triggers the same exact automatic preview.
  form.closest('details')?.addEventListener('toggle',()=>{if(form.closest('details').open)form.dispatchEvent(new Event('csf:activate'));});
 }
 // Selected-resource weekly rows: Closed and Add Break are only presentation of native ranges.
 for(const form of root.querySelectorAll('[data-kind=resource_hours_saved]')){
  const mode=form.querySelector('[name=mode]');
  for(const day of form.querySelectorAll('fieldset')){const inputs=[...day.querySelectorAll('input[type=time]')];if(!inputs.length)continue;
   const pairs=[];for(let i=0;i<inputs.length;i+=2)pairs.push([inputs[i],inputs[i+1]]);
   const label=document.createElement('label');label.className='csf-day-closed';const closed=document.createElement('input');closed.type='checkbox';closed.checked=!inputs.some(x=>x.value);label.append(closed,document.createTextNode(' Closed'));day.querySelector('legend').after(label);
   const add=document.createElement('button');add.type='button';add.className='button csf-add-break';add.textContent='Add Break';day.append(add);let saved=inputs.map(x=>x.value);
   function update(){let shown=false;pairs.forEach(([a,z])=>{const visible=!closed.checked&&(!shown||a.value||z.value);if(visible)shown=true;for(const x of[a,z]){x.closest('label').hidden=!visible;x.disabled=closed.checked;x.required=!!visible&&mode.value==='custom';}});add.disabled=closed.checked;}
   closed.addEventListener('change',()=>{if(closed.checked){saved=inputs.map(x=>x.value);inputs.forEach(x=>x.value='');}else inputs.forEach((x,i)=>x.value=saved[i]);update();});
   add.addEventListener('click',()=>{const pair=pairs.find(([a])=>a.closest('label').hidden);if(pair){pair.forEach(x=>{x.closest('label').hidden=false;x.required=mode.value==='custom';});pair[0].focus();}else add.disabled=true;});mode.addEventListener('change',update);update();
  }
  function weekly(){form.querySelectorAll('fieldset').forEach(x=>x.hidden=mode.value!=='custom');}mode.addEventListener('change',weekly);weekly();
 }
 for(const f of root.querySelectorAll('[data-kind=resource_closure_added]')){const label=document.createElement('label');label.textContent='Reason ';const select=document.createElement('select');select.className='csf-block-reason';for(const[value,text]of[['','Other / enter a reason'],['maintenance','Maintenance'],['training','Protected schooling'],['closed','Unavailable']])select.add(new Option(text,value));label.append(select);f.querySelector('[name=note]').closest('label').before(label);select.addEventListener('change',()=>{f.querySelector('[name=note]').value=select.value==='maintenance'?'Resource maintenance':select.value==='training'?'Protected schooling time':select.value==='closed'?'Resource unavailable':'';f.querySelector('[name=category]').value=select.value==='training'?'protected_training':'unavailable';});}
 if(root.dataset.screen==='hours')root.querySelectorAll('.csf-resource-panel').forEach(x=>x.open=true);
})();
