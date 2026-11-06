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
  rows.append(fragment); sync(row);
  status.textContent = 'Unsaved disabled draft added. Review operating requirements, then review and apply facility settings.';
 }
 root.addEventListener('click', e => {
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
