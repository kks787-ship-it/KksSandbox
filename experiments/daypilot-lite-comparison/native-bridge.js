'use strict';
const form=document.querySelector('#csf-new-appointment');
form?.addEventListener('csf:prefill',e=>{const out=document.querySelector('#native-selection');out.textContent='Preview only · '+e.detail.choice+' · '+(e.detail.resource||'resources not assigned')+' · NOT RESERVED';out.focus();parent.postMessage({type:'native-preview',iso:e.detail.choice},location.origin);});
document.querySelectorAll('a').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();parent.postMessage({type:'native-navigation',date:new URL(a.href).searchParams.get('date'),view:new URL(a.href).searchParams.get('view')},location.origin);}));
