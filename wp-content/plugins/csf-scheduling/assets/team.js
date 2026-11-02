/* Private form behavior only. The server resolves and rechecks all permissions. */
'use strict';
document.querySelectorAll('.csf-permission').forEach(row=>{
 const select=row.querySelector('[data-override]'),box=row.querySelector('[data-effective]'),result=row.querySelector('[data-result]');
 if(!select||!box)return;
 const update=()=>{
  box.checked=select.value==='grant'||(select.value==='inherit'&&row.dataset.default==='1');
  const form=box.form,active=form?.querySelector('[name=active]'),verified=form?.querySelector('[name=identity_verified]');
  const ready=(active?active.checked:row.dataset.active==='1')&&(verified?verified.checked:row.dataset.verified==='1')&&row.dataset.mfa==='1';
  result.textContent=box.checked&&ready?'Granted if save succeeds':'Denied';
 };
 box.form?.querySelectorAll('[name=active],[name=identity_verified]').forEach(field=>field.addEventListener('change',update));
 select.addEventListener('change',update);box.addEventListener('change',()=>{select.value=box.checked?'grant':'deny';update();});update();
});

document.querySelectorAll('[data-copy]').forEach(box=>box.addEventListener('change',()=>{
 const name=box.form.querySelector('[name=group_label]');
 name.value=box.checked?'':name.dataset.originalLabel;
 if(box.checked){name.placeholder='For example, Assistant Trainer';name.focus();}
}));
