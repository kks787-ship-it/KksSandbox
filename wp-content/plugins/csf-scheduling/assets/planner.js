/* Visual presentation only; the existing staff form owns every preview and save. */
'use strict';
(() => {
 const planner=document.querySelector('#csf-visual-planner');if(!planner)return;
 const dialog=planner.querySelector('dialog'),body=dialog.querySelector('.csf-planner-dialog-body'),form=document.querySelector('#csf-new-appointment');let moved=null,placeholder=null,opener=null;
 function close(){if(dialog.open)dialog.close();}
 dialog.addEventListener('close',()=>{if(moved&&placeholder){placeholder.replaceWith(moved);moved=null;placeholder=null;}opener?.focus();});
 planner.querySelector('.csf-planner-close').addEventListener('click',close);
 planner.addEventListener('click',event=>{
  const booking=event.target.closest('[data-event]');
  if(booking){const card=document.getElementById('csf-event-'+booking.dataset.event);if(!card)return;close();opener=booking;placeholder=document.createComment('Appointment details return here');card.replaceWith(placeholder);moved=card;body.append(card);dialog.showModal();return;}
  const slot=event.target.closest('.csf-planner-slot');
  if(slot&&!slot.disabled&&form){form.dispatchEvent(new CustomEvent('csf:prefill',{detail:{local:slot.dataset.local,choice:slot.dataset.choice,resource:slot.dataset.resource}}));}
 });
 document.addEventListener('click',event=>{const button=event.target.closest('.csf-planner-duplicate');if(!button||!form)return;const copy=JSON.parse(button.dataset.copy);opener=null;close();if(button.dataset.copyKind==='advanced'){const advanced=document.querySelector('#csf-new-appointment-advanced');if(advanced){advanced.closest('details').open=true;advanced.dispatchEvent(new CustomEvent('csf:prefill-advanced',{detail:copy}));}return;}const iso=copy.start,local=typeof iso==='string'?iso.slice(0,16):iso.local,choice=typeof iso==='string'?iso:iso.choice;form.dispatchEvent(new CustomEvent('csf:prefill',{detail:{local,choice,resource:'',fields:{title:copy.title,service_type:copy.service_type,single_rider:copy.single_rider,single_horse:copy.single_horse,state:'requested'},resources:copy.resources}}));});
 function visibleSlots(){return Array.from(planner.querySelectorAll('.csf-planner-slot:not(:disabled)')).filter(el=>el.closest('.csf-planner-column').getClientRects().length);}
 function rove(target){const slots=visibleSlots();slots.forEach(el=>el.tabIndex=el===(target||slots[0])?0:-1);}
 planner.addEventListener('focusin',event=>{if(event.target.matches('.csf-planner-slot'))rove(event.target);});
 planner.addEventListener('keydown',event=>{
  const current=event.target;if(!current.matches('.csf-planner-slot'))return;
  const slots=visibleSlots(),column=current.closest('.csf-planner-column'),same=slots.filter(el=>el.closest('.csf-planner-column')===column),index=same.indexOf(current);let target;
  if(event.key==='ArrowDown')target=same[index+1];if(event.key==='ArrowUp')target=same[index-1];if(event.key==='Home')target=same[0];if(event.key==='End')target=same[same.length-1];
  if(target){event.preventDefault();target.focus();target.scrollIntoView({block:'nearest',inline:'nearest'});}
 });
 const choice=planner.querySelector('.csf-planner-day-choice'),lane=planner.querySelector('.csf-planner-lane-choice');
 function selectDay(){planner.querySelectorAll('.csf-planner-column').forEach(column=>column.dataset.mobileActive=String((!choice||column.dataset.day===choice.value)&&(!lane||column.dataset.lane===lane.value)));}
 const scroll=planner.querySelector('.csf-planner-scroll');
 function jumpToWorkday(){let firstOpen=Infinity;planner.querySelectorAll('.csf-planner-column').forEach(column=>{if(!column.getClientRects().length)return;const open=column.querySelector('.csf-slot-candidate');if(open)firstOpen=Math.min(firstOpen,open.parentElement.offsetTop);});scroll.scrollTop=Number.isFinite(firstOpen)?Math.max(0,firstOpen-60):32*44-60;}
 choice?.addEventListener('change',()=>{selectDay();rove();requestAnimationFrame(jumpToWorkday);});lane?.addEventListener('change',()=>{selectDay();rove();requestAnimationFrame(jumpToWorkday);});selectDay();rove();requestAnimationFrame(jumpToWorkday);
})();
