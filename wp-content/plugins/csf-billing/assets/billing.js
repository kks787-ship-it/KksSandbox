/* Local sandbox checkout. No certificate secret or session bearer is stored in browser storage. */
(() => {
  'use strict';
  for (const root of document.querySelectorAll('.csf-gift-app')) {
    const content = root.querySelector('.csf-gift-content');
    const status = root.querySelector('.csf-gift-status');
    let csrf = '', busy = false;
    const uuid = () => crypto.randomUUID();
    const node = (tag, text, attrs = {}) => {
      const el = document.createElement(tag);
      if (text !== null) el.textContent = text;
      for (const [key, value] of Object.entries(attrs)) el.setAttribute(key, value);
      return el;
    };
    const button = (text, action) => {
      const el = node('button', text, {type: 'button'});
      el.addEventListener('click', () => run(action));
      return el;
    };
    const field = (form, text, type, attrs = {}) => {
      const label = node('label', text);
      const input = node('input', null, {type, ...attrs});
      label.append(input); form.append(label); return input;
    };
    const api = async (action, body = {}) => {
      const response = await fetch(root.dataset.api + action, {
        method: 'POST', credentials: 'same-origin', cache: 'no-store', redirect: 'error',
        headers: {'Content-Type': 'application/json', 'X-CSF-CSRF': csrf, ...(root.dataset.nonce ? {'X-WP-Nonce': root.dataset.nonce} : {})},
        body: JSON.stringify(body)
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'Request failed. Check status before making another payment.');
      return result;
    };
    const run = async (action) => {
      if (busy) return;
      busy = true; root.setAttribute('aria-busy', 'true'); status.textContent = 'Working…';
      try { await action(); status.textContent = 'Updated.'; }
      catch (error) { status.textContent = error.message; status.setAttribute('role', 'alert'); }
      finally { busy = false; root.removeAttribute('aria-busy'); }
    };
    const offerFlow = (data, admin) => {
      const form = node('form', null); const offer = field(form, 'Staff lesson offer reference', 'text', {required: '', autocomplete: 'off'});
      form.append(node('button', 'Review lesson offer', {type: 'submit'})); content.append(form);
      const panel = node('div', null); content.append(panel);
      form.addEventListener('submit', event => { event.preventDefault(); run(async () => {
        const id = offer.value.trim(); const reviewed = await api(admin ? 'offer-review' : 'booking-offer', {order_id: data.order_id, offer_id: id});
        panel.replaceChildren(node('p', `${reviewed.service}: ${reviewed.start} → ${reviewed.end}. Status: ${reviewed.state}.`));
        if (admin && reviewed.state === 'awaiting_staff_confirmation') {
          panel.append(button('Confirm this consented lesson', async () => { await api('offer-confirm', {order_id:data.order_id,offer_id:id,revision:reviewed.revision,command:confirmation}); panel.replaceChildren(node('p','Lesson confirmation recorded. Credit is reserved, not consumed.')); }));
          const confirmation = uuid();
        } else if (!admin && reviewed.consent_required) {
          const consent = node('form', null); const number = field(consent, 'Certificate number for this lesson', 'text', {required:'',autocomplete:'off',spellcheck:'false'});
          const agree = field(consent, ' I request use of this certificate for the exact lesson shown. Staff must confirm it; this request does not reserve a lesson or consume credit.', 'checkbox', {required:''});
          consent.append(node('button','Request certificate-funded lesson',{type:'submit'})); panel.append(consent); const command = uuid();
          consent.addEventListener('submit', event => { event.preventDefault(); if(!agree.checked)return; run(async()=>{const code=number.value.trim();number.value='';const result=await api('booking-consent',{order_id:data.order_id,offer_id:id,command,certificate_number:code,attested:true});panel.replaceChildren(node('p',`Status: ${result.state}. No card payment was requested. Review status before retrying.`));}); });
        }
      }); });
    };
    const receipt = (data, admin = false) => {
      content.replaceChildren();
      const title = node('h3', 'Purchase record', {tabindex: '-1'}); content.append(title);
      content.append(node('p', `Reference: ${data.order_id}`), node('p', `Status: ${data.status}`), node('p', data.package));
      if (data.certificate_number) {
        content.append(node('p', `Certificate: ${data.certificate_number}`), node('p', `Purchased rides: ${data.purchased}; redeemed: ${data.redeemed}; remaining: ${data.purchased - data.redeemed}.`));
        if (data.reserved_rides) content.append(node('p', `Reserved rides: ${data.reserved_rides}; available rides: ${data.purchased - data.redeemed - data.reserved_rides}. Reserved value is not spent.`));
        if (data.value) { const v = data.value; const spent = v.used_slots.reduce((n, i) => n + v.allocation[i], 0); const remaining = v.paid_minor - spent - v.cash_paid_minor; content.append(node('p', `Paid value: $${(v.paid_minor / 100).toFixed(2)} USD; ride value used: $${(spent / 100).toFixed(2)}; cash paid (recorded): $${(v.cash_paid_minor / 100).toFixed(2)}; remaining value: $${(remaining / 100).toFixed(2)}.`)); if (v.pending) content.append(node('p', `Cash request ${v.pending.id}: ${v.pending.state}. Value is reserved, not yet recorded as paid. Rides and corrections are held.`)); if (!v.policy.approved) content.append(node('p', 'Ride valuation awaits approval. Paid value remains preserved.')); }
        if (!admin && data.printable_html) {
          const preview = node('iframe', null, {title: 'Printable test gift certificate', sandbox: 'allow-modals allow-same-origin', style: 'width:100%;height:650px;border:0'});
          preview.srcdoc = data.printable_html; content.append(preview);
          content.append(button('Print or save this test certificate', async () => { preview.contentWindow.focus(); preview.contentWindow.print(); }));
        }
      }
      if (data.status === 'issued' && (!admin || data.can_manage)) content.append(button('Download private test PDF', async () => {
        const result = await api(admin ? 'private-pdf' : 'pdf', {order_id: data.order_id});
        const bytes = Uint8Array.from(atob(result.pdf_base64), ch => ch.charCodeAt(0));
        const digest = Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', bytes))).map(n => n.toString(16).padStart(2, '0')).join('');
        if (digest !== result.sha256) throw new Error('Certificate file could not be verified.');
        const url = URL.createObjectURL(new Blob([bytes], {type: 'application/pdf'}));
        const link = node('a', 'Download', {href: url, download: 'gift-certificate-test.pdf'}); link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
      }));
      content.append(node('h4', `Accepted terms — ${data.terms.version}`), node('p', `Accepted: ${data.accepted_at}`), node('pre', data.terms.text, {class: 'csf-gift-terms'}));
      if (!admin && !['issued', 'frozen'].includes(data.status)) {
        if (['prepared', 'awaiting_approval', 'approved'].includes(data.status)) content.append(button('Resume PayPal sandbox approval', async () => {
          const result = await api('create', {order_id: data.order_id, command: uuid()});
          const url = new URL(result.approval_url);
          if (url.origin !== 'https://www.sandbox.paypal.com' || url.pathname !== '/checkoutnow') throw new Error('Unexpected payment destination.');
          location.assign(url.href);
        }));
        content.append(button('Complete test payment / check status', async () => receipt(await api('capture', {order_id: data.order_id, command: uuid()}))));
      }
      if (!admin && data.status === 'issued') {
        content.append(node('p', 'Save this receipt now. This browser session expires after two hours. No email has been sent.'));
        content.append(button('Start a separate test purchase', async () => { sessionStorage.removeItem('csf-gift-pending'); await start(); }));
      }
      if (data.status === 'frozen') content.append(node('p', 'Certificate use is on hold. Contact Beth with the purchase reference.'));
      if(data.status==='issued' && data.booking_requests_enabled && (!admin || data.can_manage)) offerFlow(data,admin);
      if (admin) adminActions(data);
      title.focus();
    };
    const adminActions = data => {
      if (data.certificate_id) {
        const form = node('form', null);
        const rides = field(form, 'Completed rides to redeem', 'number', {min: '1', step: '1', required: ''}); rides.value = '1';
        const reference = uuid();
        const attest = field(form, ' I confirm these rides occurred and have checked the history for an existing entry.', 'checkbox', {required: ''});
        const submit = node('button', 'Record completed rides', {type: 'submit'}); form.append(submit);
        const command = uuid();
        form.addEventListener('submit', event => { event.preventDefault(); if (!attest.checked) return; run(async () => {
          await api('redeem', {command, certificate_id: data.certificate_id, rides: Number(rides.value), revision: data.revision, ride_reference: reference});
          receipt(await api('detail', {order_id: data.order_id}), true);
        }); });
        if (data.status === 'issued' && data.can_redeem) content.append(form);
        const correction = node('form', null);
        const corrected = new Set((data.history || []).filter(h => h.kind === 'ride_debit_corrected').map(h => JSON.parse(h.details).original_command));
        const originalLabel = node('label', 'Completed-ride entry to correct'); const original = node('select', null, {required: ''});
        for (const entry of data.history || []) {
          const details = JSON.parse(entry.details);
          if (entry.kind === 'rides_redeemed' && details.command && !corrected.has(details.command)) original.append(node('option', `${entry.created_at} — ${details.rides} ride(s) recorded`, {value: details.command}));
        }
        originalLabel.append(original); correction.append(originalLabel);
        const label = node('label', 'Correction reason'); const reason = node('select', null);
        reason.append(node('option', 'Mistaken ride entry', {value: 'mistaken_ride'}), node('option', 'Duplicate entry', {value: 'duplicate_entry'})); label.append(reason); correction.append(label);
        field(correction, ' I confirm this reverses a mistaken ledger debit, not a payment refund.', 'checkbox', {required: ''});
        correction.append(node('button', 'Correct the debit once', {type: 'submit'})); const correctionCommand = uuid();
        correction.addEventListener('submit', event => { event.preventDefault(); run(async () => {
          await api('correct', {command: correctionCommand, certificate_id: data.certificate_id, redemption_command: original.value.trim(), revision: data.revision, reason: reason.value});
          receipt(await api('detail', {order_id: data.order_id}), true);
        }); }); if (data.can_manage && original.options.length) content.append(correction);
      }
      if (data.certificate_id && data.can_manage) {
        const rotation = node('form', null);
        const attest = field(rotation, ' I reviewed a compromised number and need to invalidate it. The balance will stay on this certificate; replacement delivery remains held.', 'checkbox', {required: ''});
        rotation.append(node('button', 'Replace compromised certificate number', {type: 'submit'})); const command = uuid();
        rotation.addEventListener('submit', event => { event.preventDefault(); if (!attest.checked) return; run(async () => { await api('rotate', {command, certificate_id: data.certificate_id, revision: data.revision, attested: true}); receipt(await api('detail', {order_id: data.order_id}), true); }); }); content.append(rotation);
      }
      if (data.certificate_id && data.value && data.can_manage) {
        const v = data.value; const remaining = v.paid_minor - v.used_slots.reduce((n, i) => n + v.allocation[i], 0) - v.cash_paid_minor;
        if (remaining > 0) {
          const cash = node('form', null); cash.append(node('h4', 'Cash redemption record'), node('p', 'This form does not send money or issue a PayPal refund. Reserve the balance before any separately authorized payout. Keep uncertain payouts reserved until independently reconciled.'));
          const transition = node('select', null, {required: '', 'aria-label': 'Cash record action'});
          const choices = v.pending ? [['uncertain', 'Keep reserved: payout outcome uncertain'], ['record_paid', 'Record cash already paid in full'], ['cancel_unpaid', 'Release reservation: independently confirmed unpaid']] : [['reserve', 'Reserve full remaining value for requested cash redemption']];
          choices.forEach(([value, label]) => transition.append(node('option', label, {value}))); cash.append(transition);
          const evidence = field(cash, 'Private request / reconciliation / payout evidence reference (UUID; no personal or bank details)', 'text', {required: ''});
          const attested = field(cash, ' I checked the history and confirm the selected outcome and full amount against independent evidence.', 'checkbox', {required: ''});
          const amount = v.pending ? v.pending.amount_minor : remaining; cash.append(node('p', `Exact amount: $${(amount / 100).toFixed(2)} USD.`));
          if (data.redeemed > 0 && remaining < 500) cash.append(node('p', 'Remaining value after a ride purchase is below $5. Review the bearer’s cash request promptly. This flag does not execute payment.'));
          cash.append(node('button', 'Save cash-redemption record', {type: 'submit'})); const command = uuid(); const request = v.pending ? v.pending.id : uuid();
          cash.addEventListener('submit', event => { event.preventDefault(); if (!attested.checked) return; run(async () => { await api('cash', {command, certificate_id: data.certificate_id, revision: data.revision, transition: transition.value, request, amount_minor: amount, evidence: evidence.value.trim(), attested: true}); receipt(await api('detail', {order_id: data.order_id}), true); }); }); content.append(cash);
        }
      }
      if (data.status === 'create_uncertain' && data.can_manage) {
        const recovery = node('form', null);
        recovery.append(node('p', 'Find this purchase reference in the PayPal sandbox merchant records. Linking an existing order will verify its merchant, package amount, and purchase reference; it does not start another payment.'));
        const providerOrder = field(recovery, 'Existing PayPal sandbox order reference', 'text', {required: ''});
        recovery.append(node('button', 'Verify and link existing order', {type: 'submit'})); const command = uuid();
        recovery.addEventListener('submit', event => { event.preventDefault(); run(async () => {
          await api('recover', {command, order_id: data.order_id, provider_order_id: providerOrder.value.trim(), revision: data.order_revision});
          receipt(await api('detail', {order_id: data.order_id}), true);
        }); }); content.append(recovery);
      }
      content.append(node('h4', 'Audit history'));
      const history = node('ol', null);
      for (const item of data.history || []) history.append(node('li', `${item.created_at} — ${item.kind} — actor ${item.actor} — ${item.details}`));
      content.append(history, button('Back to purchases', list));
    };
    const list = async () => {
      const data = await api('list'); content.replaceChildren();
      content.append(node('p', 'Most recent 50 purchases. Use a purchase reference to find an older record.'));
      const form = node('form', null); const id = field(form, 'Purchase reference', 'text', {required: ''}); form.append(node('button', 'Open purchase', {type: 'submit'}));
      form.addEventListener('submit', e => { e.preventDefault(); run(async () => receipt(await api('detail', {order_id: id.value.trim()}), true)); }); content.append(form);
      if (data.reviews?.length) {
        content.append(node('h3', 'Payment events awaiting review'));
        for (const row of data.reviews) { const review = JSON.parse(row.result); content.append(node('p', `${row.created_at} — PayPal event ${review.provider_event_id || 'reference unavailable'} — ${review.unmapped_count || 0} unlinked transaction(s). Disputed credit remains on hold until a separate reviewed decision.`)); }
      }
      const items = node('ul', null);
      for (const order of data.orders) { const item = node('li', null); item.append(button(`${order.created_at} — ${order.status} — ${order.id}`, async () => receipt(await api('detail', {order_id: order.id}), true))); items.append(item); }
      content.append(items);
    };
    const start = async () => {
      const config = await api('session'); csrf = config.csrf;
      const pending = sessionStorage.getItem('csf-gift-pending');
      if (pending) { receipt(await api('receipt', {order_id: pending})); return; }
      content.replaceChildren();
      const form = node('form', null); const label = node('label', 'Test gift package'); const select = node('select', null, {required: ''});
      for (const product of config.products) select.append(node('option', `${product.label} — ${product.rides} rides of ${product.minutes_per_ride} minutes — ${new Intl.NumberFormat('en-US', {style: 'currency', currency: product.currency}).format(product.amount_minor / 100)}`, {value: product.id}));
      label.append(select); form.append(label, node('h3', `Terms — ${config.terms.version}`), node('pre', config.terms.text, {class: 'csf-gift-terms'}));
      const designLabel = node('label', 'Certificate design'); const design = node('select', null, {required: '', 'aria-label': 'Certificate design'});
      for (const [value, title] of Object.entries(config.designs || {'pasture-v1': 'Pasture green'})) design.append(node('option', title, {value}));
      designLabel.append(design); form.append(designLabel);
      const email = field(form, 'Buyer email for certificate delivery (delivery remains held during testing)', 'email', {required: '', maxlength: '254', autocomplete: 'email'});
      const accept = field(form, ' I have read and accept these exact terms.', 'checkbox', {required: ''});
      form.append(node('button', 'Continue to PayPal sandbox', {type: 'submit'})); const command = uuid();
      form.addEventListener('submit', event => { event.preventDefault(); if (!accept.checked) return; run(async () => {
        const product = config.products.find(p => p.id === select.value);
        const prepared = await api('prepare', {command, design: design.value, buyer_email: email.value.trim(), product_id: product.id, product_revision: product.revision, accepted: true, terms_version: config.terms.version, terms_hash: config.terms.sha256});
        sessionStorage.setItem('csf-gift-pending', prepared.order_id);
        receipt(await api('receipt', {order_id: prepared.order_id}));
        const created = await api('create', {order_id: prepared.order_id, command: uuid()});
        const url = new URL(created.approval_url);
        if (url.origin !== 'https://www.sandbox.paypal.com' || url.pathname !== '/checkoutnow') throw new Error('Unexpected payment destination.');
        location.assign(url.href);
      }); }); content.append(form);
    };
    // Provider return parameters are not evidence of payment and are never sent to our API.
    if (root.dataset.mode === 'public' && (new URLSearchParams(location.search).has('token') || new URLSearchParams(location.search).has('PayerID'))) history.replaceState(null, '', location.pathname);
    run(root.dataset.mode === 'admin' ? list : start);
  }
})();
