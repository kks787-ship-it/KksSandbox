/* Progressive directory navigation. Native authorized forms remain the only writers. */
(() => {
 'use strict';
 const root = document.querySelector('#csf-farm-setup');
 if (!root) return;
 const groups = [...root.querySelectorAll(':scope > .csf-directory-group')];
 if (!groups.length) return;
 const toolbar = document.createElement('div');
 toolbar.className = 'csf-directory-toolbar';
 const navigation = document.createElement('nav');
 navigation.setAttribute('aria-label', 'Farm records');
 const searchLabel = document.createElement('label');
 searchLabel.textContent = 'Find by name';
 const search = document.createElement('input');
 search.type = 'search'; search.placeholder = 'Search this list'; search.autocomplete = 'off';
 searchLabel.append(search);
 const count = document.createElement('p'); count.className = 'csf-directory-count'; count.setAttribute('role', 'status');
 toolbar.append(navigation, searchLabel, count); groups[0].before(toolbar);
 const empty = document.createElement('p'); empty.className = 'csf-directory-empty';
 empty.textContent = 'No matching records. Clear the search or choose another list.'; empty.hidden = true; toolbar.after(empty);
 let active = groups.find(g => g.dataset.directoryGroup === 'horses') || groups[0];
 const buttons = new Map();
 for (const key of ['riders', 'horses', 'resources', 'blocks']) {
  const group = groups.find(g => g.dataset.directoryGroup === key); if (!group) continue;
  const button = document.createElement('button'); button.type = 'button'; button.className = 'button';
  button.textContent = group.querySelector('h3').textContent;
  button.setAttribute('aria-controls', group.querySelector('h3').id + '-panel'); group.id = group.querySelector('h3').id + '-panel';
  button.addEventListener('click', () => { active = group; search.value = ''; update(); });
  buttons.set(group, button); navigation.append(button);
 }
 function update() {
  const term = search.value.trim().toLocaleLowerCase(); let shown = 0, total = 0;
  for (const group of groups) {
   group.hidden = group !== active; buttons.get(group)?.setAttribute('aria-pressed', String(group === active));
   const records = [...group.querySelectorAll('.csf-directory-record')];
   for (const record of records) {
    record.hidden = !record.dataset.directoryName.toLocaleLowerCase().includes(term);
    if (group === active) { total++; if (!record.hidden) shown++; }
   }
  }
  const blocks = active.dataset.directoryGroup === 'blocks'; searchLabel.hidden = blocks;
  count.textContent = blocks ? 'Review current protections or add a protective block.' : `${shown} of ${total} records`;
  empty.hidden = blocks || shown > 0;
 }
 search.addEventListener('input', update); update();
 // Modal is optional: without native dialog support, normal details still work.
 if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
 const dialog = document.createElement('dialog'); dialog.className = 'csf-directory-dialog'; dialog.setAttribute('aria-labelledby', 'csf-record-title');
 const heading = document.createElement('h2'); heading.id = 'csf-record-title';
 const close = document.createElement('button'); close.type = 'button'; close.className = 'button'; close.textContent = 'Close record';
 const note = document.createElement('p'); note.className = 'csf-directory-draft-note'; note.textContent = 'Closing keeps your unsaved edits on this page. Review and save explicitly to apply changes.';
 const body = document.createElement('div'); dialog.append(close, heading, note, body); root.append(dialog);
 let moved, marker, opener;
 function restore() { if (moved && marker) marker.replaceWith(moved); moved = marker = null; opener?.focus(); }
 dialog.addEventListener('close', restore); close.addEventListener('click', () => dialog.close());
 for (const record of root.querySelectorAll('.csf-directory-record')) {
  const summary = record.querySelector(':scope > summary'); summary.setAttribute('aria-haspopup', 'dialog');
  summary.addEventListener('click', event => {
   event.preventDefault(); if (dialog.open) return;
   opener = summary; moved = record.querySelector(':scope > .csf-record-body');
   marker = document.createComment('Record form position'); moved.replaceWith(marker); body.append(moved);
   heading.textContent = record.dataset.directoryName;
   const primary = moved.querySelector(':scope > details'); if (primary) primary.open = true;
   dialog.showModal(); dialog.scrollTop = 0; close.focus();
  });
 }
})();
