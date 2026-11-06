/* Resource editing prefills reviewed forms only. No save, reservation or reopen on click. */
(() => {
 'use strict';
 const planner=document.querySelector('#csf-visual-planner'),root=document.querySelector('#csf-resource-management'),config=window.CSFStaff;
 if(!root||!config)return;
 let anchor=null;
 const panels=[...root.querySelectorAll('.csf-resource-panel')];
 const panel=id=>panels.find(p=>p.dataset.resource===id);
 function put(form,name,iso){const field=form.querySelector(`[name="${name}_local"]`),select=form.querySelector(`[name="${name}_choice"]`);field.value=iso.slice(0,16);select.replaceChildren(new Option('Selected exact occurrence · '+iso.slice(-6),iso));}
 function local(form,name,value){form.querySelector(`[name="${name}_local"]`).value=value;form.querySelector(`[name="${name}_choice"]`).replaceChildren(new Option('Use unique local time or review clock occurrence',''));}
 function nextDate(day){const d=new Date(day+'T00:00:00Z');d.setUTCDate(d.getUTCDate()+1);return d.toISOString().slice(0,10);}
 function closeDay(id,day){const p=panel(id);if(!p||!/^\d{4}-\d{2}-\d{2}$/.test(day))return;p.open=true;const form=p.querySelector('[data-kind=resource_closure_added]');local(form,'start',day+'T00:00');local(form,'end',nextDate(day)+'T00:00');anchor=null;form.querySelector('.csf-resource-selection').textContent='Entire local day selected. Review checks affected appointments before any change.';form.scrollIntoView({block:'center'});form.querySelector('[name=note]').focus();}
 root.addEventListener('click',e=>{const day=e.target.closest('.csf-close-resource-day');if(day)closeDay(day.dataset.resource,day.closest('.csf-resource-panel').querySelector('.csf-resource-day').value);});
 async function resolve(field){const form=field.closest('form'),name=field.name.startsWith('start_')?'start':'end',status=field.closest('fieldset').querySelector('[role=status]'),select=form.querySelector(`[name="${name}_choice"]`),value=field.value;select.replaceChildren(new Option('Review local time',''));if(!value)return;
  try{const response=await fetch(config.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'csf_schedule_tools',op:'times',_wpnonce:config.nonce,local:value})});const data=await response.json();if(field.value!==value)return;if(!response.ok||!data.success)throw Error('Local-time check failed; review your session before saving.');const choices=data.data.choices;
   select.replaceChildren();if(choices.length!==1)select.add(new Option(choices.length?'Choose first or second occurrence':'This local time does not exist',''));choices.forEach((iso,i)=>select.add(new Option((i?'Second':'First')+' occurrence · '+iso.slice(-6),iso)));status.textContent=choices.length===2?'Clock change: select the intended occurrence.':choices.length===0?'Choose a valid local time.':'Valid local time. No change saved.';
  }catch(error){status.textContent=error.message;}
 }
 root.addEventListener('change',e=>{if(e.target.matches('[name=start_local],[name=end_local]')){anchor=null;resolve(e.target);}if(e.target.matches('[name=repeat]'))e.target.closest('form').querySelector('[name=repeat_until]').required=e.target.checked;});
 root.querySelectorAll('[name=repeat]').forEach(el=>{el.closest('form').querySelector('[name=repeat_until]').required=el.checked;});
 if(!planner)return;
 const mode=planner.querySelector('.csf-resource-mode');if(!mode)return;
 const visible=()=>[...planner.querySelectorAll('.csf-resource-slot')].filter(b=>b.getClientRects().length);
 function rove(target){const cells=visible();cells.forEach(b=>b.tabIndex=b===(target||cells[0])?0:-1);}
 mode.addEventListener('click',()=>{const enabled=mode.getAttribute('aria-pressed')!=='true';mode.setAttribute('aria-pressed',String(enabled));planner.classList.toggle('csf-resource-editing',enabled);anchor=null;rove();planner.querySelector('.csf-resource-mode-status').textContent=enabled?'Resource selection mode. Choose an instructor/arena lane or filter; tap a quarter-hour, then a later endpoint to extend. Existing appointments stay visible and unchanged.':'Booking mode. No resource change was saved.';});
 planner.addEventListener('focusin',e=>{if(e.target.matches('.csf-resource-slot'))rove(e.target);});
 planner.addEventListener('keydown',e=>{if(!e.target.matches('.csf-resource-slot'))return;const same=visible().filter(b=>b.closest('.csf-planner-column')===e.target.closest('.csf-planner-column')),i=same.indexOf(e.target);const next=e.key==='ArrowDown'?same[i+1]:e.key==='ArrowUp'?same[i-1]:e.key==='Home'?same[0]:e.key==='End'?same.at(-1):null;if(next){e.preventDefault();next.focus();next.scrollIntoView({block:'nearest'});}});
 planner.addEventListener('click',e=>{const cell=e.target.closest('.csf-resource-slot');if(!cell)return;const p=panel(cell.dataset.resource);if(!p)return;p.open=true;
  if(cell.dataset.closures){anchor=null;for(const id of cell.dataset.closures.split(',')){const closure=[...p.querySelectorAll('.csf-resource-closure')].find(c=>c.dataset.closure===id);if(closure){closure.open=true;const date=closure.querySelector('[name=only_date][type=date]');if(date)date.value=cell.dataset.start.slice(0,10);closure.scrollIntoView({block:'center'});closure.querySelector('[name=note]').focus();}}return;}
  const form=p.querySelector('[data-kind=resource_closure_added]');const range=planner.querySelector('.csf-resource-range').checked;if(!range)anchor=null;if(anchor&&anchor.resource===cell.dataset.resource&&Date.parse(cell.dataset.end)>Date.parse(anchor.start)){put(form,'start',anchor.start);put(form,'end',cell.dataset.end);anchor=null;form.querySelector('.csf-resource-selection').textContent='Closure range selected. Review before applying.';}else{anchor={resource:cell.dataset.resource,start:cell.dataset.start};put(form,'start',cell.dataset.start);put(form,'end',cell.dataset.end);form.querySelector('.csf-resource-selection').textContent='Quarter-hour selected. Tap a later endpoint to extend, or review this interval.';}
  if(range&&anchor){planner.querySelector('.csf-resource-mode-status').textContent='Range start selected: '+anchor.start+'. Choose a later endpoint.';return;}anchor=null;form.scrollIntoView({block:'center'});form.querySelector('[name=note]').focus();
 });
 planner.querySelectorAll('.csf-planner-day-choice,.csf-planner-lane-choice').forEach(s=>s.addEventListener('change',()=>requestAnimationFrame(()=>rove())));
})();
