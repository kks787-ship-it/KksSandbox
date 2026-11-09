/* Optional private offer preparation. Never auto-confirms a buyer's request. */
'use strict';
(() => {
 const config=window.CSFBillingOffers;if(!config)return;
 document.querySelectorAll('.csf-routine-editor').forEach(form=>{
  const original=JSON.parse(form.elements.body.value);if(original.expected!==0)return;
  const panel=document.createElement('fieldset');const legend=document.createElement('legend');legend.textContent='Gift certificate lesson offer';panel.append(legend);
  const label=document.createElement('label');label.textContent='Purchase reference ';const order=document.createElement('input');order.type='text';order.autocomplete='off';label.append(order);panel.append(label);
  const button=document.createElement('button');button.type='button';button.textContent='Prepare offer for buyer review';button.disabled=true;panel.append(button);
  const status=document.createElement('p');status.setAttribute('role','status');panel.append(status);form.append(panel);
  let reviewed=null,created=false,busy=false;const command=crypto.randomUUID();
  form.addEventListener('csf:reviewed-appointment',event=>{reviewed=event.detail;button.disabled=created||reviewed.body.state!=='confirmed';});
  form.addEventListener('input',event=>{if(event.target===order)return;reviewed=null;button.disabled=true;});
  form.addEventListener('change',event=>{if(event.target===order)return;reviewed=null;button.disabled=true;});
  form.addEventListener('submit',event=>{if(created){event.preventDefault();status.textContent='This draft has a buyer offer. Confirm the consented offer in Private gift certificates; do not save it separately.';}},true);
  button.addEventListener('click',async()=>{
   if(!reviewed||created||busy)return;if(!/^[a-f0-9-]{36}$/.test(order.value.trim())){status.textContent='Enter the purchase reference from the private certificate record.';return;}
   busy=true;button.disabled=true;const draft=reviewed;
   try{const response=await fetch(config.api+'offer-create',{method:'POST',credentials:'same-origin',cache:'no-store',redirect:'error',headers:{'Content-Type':'application/json','X-WP-Nonce':config.nonce},body:JSON.stringify({command,order_id:order.value.trim(),appointment:draft.body,revision:draft.revision})});const r=await response.json();if(!response.ok)throw Error(r.message||'Offer was not acknowledged. Check the same reference before retrying.');created=true;status.textContent='Offer reference: '+r.offer_id+'. Give this reference to the buyer for review in their purchase session. No lesson or credit is reserved. Confirm after consent in Private gift certificates.';form.querySelector('.csf-save').disabled=true;}
   catch(error){status.textContent=error.message;}finally{busy=false;button.disabled=created||!reviewed;}
  });
 });
})();
