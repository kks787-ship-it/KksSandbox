/* Native facility catalog drafts only. No network requests or policy writes. */
(() => {
 'use strict';
 const root = document.querySelector('#csf-lesson-catalog');
 if (!root) return;
 const rows = root.querySelector('.csf-catalog-rows');
 const template = root.querySelector('template');
 const status = root.querySelector('.csf-catalog-status');
 let index = rows.children.length;
 function sync(row) {
  const enabled = row.querySelector('[name$="[enabled]"]').checked;
  for (const key of ['capacity', 'duration_minutes', 'horse_source', 'resources_reviewed']) {
   row.querySelector(`[name$="[${key}]"]`).required = enabled;
  }
 }
 function add(example = {}) {
  if (rows.children.length >= 30) { status.textContent = 'Maximum 30 lesson types. Disable an existing type instead of deleting its history.'; return; }
  const fragment = template.content.cloneNode(true);
  const row = fragment.firstElementChild;
  for (const field of row.querySelectorAll('[name]')) field.name = field.name.replace('__INDEX__', String(index));
  index += 1;
  for (const [key, value] of Object.entries(example)) {
   const field = row.querySelector(`[name$="[${key === 'label' ? 'name' : key}]"]`);
   if (field && value !== null) field.value = value;
  }
  rows.append(fragment); sync(row);row.querySelector('legend').textContent=example.label||'New lesson type';
  status.textContent = 'Unsaved disabled draft added. Review operating requirements, then review and apply facility settings.';
  return row;
 }

 const nameKey=value=>value.normalize('NFKC').toLowerCase().replace(/[^a-z0-9]/g,'');
 function studentSetup(){
  const plans=[30,60].map(minutes=>{const label='Student Lesson — '+minutes+' Minutes';const matches=[...rows.children].filter(row=>nameKey(row.querySelector('[name$="[name]"]').value)===nameKey(label));return{minutes,label,matches};});
  if(plans.some(p=>p.matches.length>1)){status.textContent='More than one matching Student lesson exists. Resolve duplicate names before using setup; no rows were changed.';return;}
  if(plans.some(p=>p.matches.length&&p.matches[0].querySelector('[name$="[duration_minutes]"]').value!==''&&Number(p.matches[0].querySelector('[name$="[duration_minutes]"]').value)!==p.minutes)){status.textContent='An existing Student lesson name has a different duration. Review it directly before using setup; no rows were changed.';return;}
  if(rows.children.length+plans.filter(p=>!p.matches.length).length>30){status.textContent='Not enough catalog space for both proposals. Review existing types first; no rows were changed.';return;}
  for(const plan of plans){
   const row=plan.matches[0]||add({label:plan.label,category:'Student',classification:'student_lesson',duration_minutes:plan.minutes,sort_order:plan.minutes===30?0:1});
   row.querySelector('[name$="[duration_minutes]"]').value=String(plan.minutes);
   row.querySelector('[name$="[enabled]"]').checked=true;sync(row);row.classList.add('csf-student-proposal');
  }
  status.textContent='Student 30/60 proposals are NOT SAVED. Confirm capacity, horse source, travel and resource requirements for both, then Review changes and Apply. Horse welfare and suitability still apply, including limits below a 60-minute horse turn. Existing matching IDs were reused; other services were preserved.';
  plans[0].matches[0]?.scrollIntoView({block:'center'});
  const first=[...rows.children].find(row=>nameKey(row.querySelector('[name$="[name]"]').value)===nameKey(plans[0].label));first?.querySelector('[name$="[capacity]"]').focus();
 }
 root.addEventListener('click', e => {
  if (e.target.closest('.csf-student-setup')){studentSetup();return;}
  if (e.target.closest('.csf-catalog-add')) add();
  if (e.target.closest('.csf-catalog-examples')) {
   const existing = new Set([...rows.querySelectorAll('[name$="[name]"]')].map(el => el.value.toLowerCase()));
   for (const example of JSON.parse(root.querySelector('.csf-catalog-examples').dataset.examples)) {
    if (!existing.has(example.label.toLowerCase())) add(example);
   }
  }
  const remove = e.target.closest('.csf-catalog-remove');
  if (remove) { remove.closest('.csf-catalog-row').remove(); status.textContent = 'Unsaved draft removed.'; }
 });
 root.addEventListener('change', e => { const row = e.target.closest('.csf-catalog-row'); if (row) sync(row); });
 rows.querySelectorAll('.csf-catalog-row').forEach(sync);
})();
