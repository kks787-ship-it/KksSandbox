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
    const receipt = (data, admin = false) => {
      content.replaceChildren();
      const title = node('h3', 'Purchase record', {tabindex: '-1'}); content.append(title);
      content.append(node('p', `Reference: ${data.order_id}`), node('p', `Status: ${data.status}`), node('p', data.package));
      if (data.certificate_number) {
        content.append(node('p', `Certificate: ${data.certificate_number}`), node('p', `Purchased rides: ${data.purchased}; redeemed: ${data.redeemed}; remaining: ${data.purchased - data.redeemed}.`));
        if (!admin) content.append(button('Print or save this test certificate', async () => window.print()));
      }
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
      const accept = field(form, ' I have read and accept these exact terms.', 'checkbox', {required: ''});
      form.append(node('button', 'Continue to PayPal sandbox', {type: 'submit'})); const command = uuid();
      form.addEventListener('submit', event => { event.preventDefault(); if (!accept.checked) return; run(async () => {
        const product = config.products.find(p => p.id === select.value);
        const prepared = await api('prepare', {command, product_id: product.id, product_revision: product.revision, accepted: true, terms_version: config.terms.version, terms_hash: config.terms.sha256});
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
