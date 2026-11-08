'use strict';
// Real local WordPress/MariaDB/MFA; the external PayPal transport remains an explicit fixture.
const {chromium}=require('playwright');
const {execFileSync}=require('child_process');
const fs=require('fs');
const assert=require('assert/strict');
const base='https://127.0.0.1:18443';
const root='/tmp/csf-billing-fixture';
if(!fs.existsSync(root+'/operator.json'))throw Error('Initialized disposable fixture required');
const operator=JSON.parse(fs.readFileSync(root+'/operator.json')).id;
const cli=code=>execFileSync(root+'/php',['.wp-local/wp-cli.phar','--path='+root+'/site','eval',`if(DB_NAME!=="csf_gift_fixture"||ABSPATH!=="${root}/site/"){throw new RuntimeException("Wrong fixture");}${code}`],{encoding:'utf8'}).trim();
let checks=0;function check(value,label){assert.ok(value,label);checks++;console.log('PASS',label);}
const resetRate=()=>cli('global $wpdb;$wpdb->query("DELETE FROM gift_fixture_csf_billing_throttle");');
async function raw(context,action,body={},headers={}){
 const r=await context.request.post(base+'/?rest_route=/csf-billing/v1/'+action,{headers:{Origin:base,'Content-Type':'application/json',...headers},data:body});
 return {status:r.status(),data:await r.json(),headers:r.headers()};
}
async function settled(page){await page.evaluate(()=>new Promise(resolve=>{if(window.jQuery){window.jQuery(()=>setTimeout(resolve,0));}else{setTimeout(resolve,0);}}));}
async function login(page,user,password){
 await page.goto(base+'/wp-login.php?redirect_to='+encodeURIComponent(base+'/wp-admin/admin.php?page=csf-gift-certificates'));
 await settled(page);await page.locator('#user_login').fill(user);await page.locator('#user_pass').fill(password);
 await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('#wp-submit').click()]);
}
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CSF_TEST_CHROMIUM||'/usr/bin/chromium',headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try{
 const customer=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844}});const page=await customer.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.route('https://www.sandbox.paypal.com/**',async route=>{
  const token=new URL(route.request().url()).searchParams.get('token');check(/^[A-Z0-9]+$/.test(token),'sandbox approval order ID');
  await customer.request.get(base+'/?gift_fixture_approve='+token);
  await route.fulfill({status:200,contentType:'text/html',body:'<!doctype html><title>Synthetic PayPal approval</title><h1>Mock provider approved</h1><a href="'+base+'/gift-certificates/">Return to gift certificates</a>'});
 });
 cli(`get_userdata(${operator})->add_cap('csf_billing_redeem');`);resetRate();await page.goto(base+'/gift-certificates/');
 await page.getByRole('button',{name:'Continue to PayPal sandbox',exact:true}).waitFor();
 check(await page.getByText('SYNTHETIC terms for disposable tests only.',{exact:false}).count()===1,'exact fixture terms displayed');
 await page.getByRole('button',{name:'Continue to PayPal sandbox',exact:true}).click();
 check(await page.locator('input[type=checkbox]').evaluate(el=>!el.validity.valid),'consent required before checkout');
 const cookies=await customer.cookies();const session=cookies.find(c=>c.name==='__Host-csf-gift-session');check(session&&session.httpOnly&&session.secure&&session.sameSite==='Lax','secure HttpOnly scoped session cookie');
 const sessionReply=await raw(customer,'session');const csrf=sessionReply.data.csrf;
 check(sessionReply.headers['cache-control'].includes('no-store'),'session response uncached');
 const cross=await raw(customer,'session',{}, {Origin:'https://attacker.invalid'});check(cross.status===403,'cross-origin request denied');
 const noCsrf=await raw(customer,'prepare',{});check(noCsrf.status===403,'missing CSRF denied');
 const product=sessionReply.data.products[0];const terms=sessionReply.data.terms;
 const stale=await raw(customer,'prepare',{command:crypto.randomUUID(),product_id:product.id,product_revision:product.revision,accepted:true,terms_version:terms.version,terms_hash:'0'.repeat(64)},{'X-CSF-CSRF':csrf});check(stale.status===409,'stale terms rejected server-side');
 await page.locator('input[type=checkbox]').check();await page.getByRole('button',{name:'Continue to PayPal sandbox',exact:true}).click();
 await page.getByRole('link',{name:'Return to gift certificates'}).waitFor({timeout:20000});await page.getByRole('link',{name:'Return to gift certificates'}).click();
 await page.getByRole('button',{name:'Complete test payment / check status',exact:true}).click();
 await page.getByRole('button',{name:'Print or save this test certificate'}).waitFor({timeout:20000});
 check(await page.getByText('Purchased rides: 2; redeemed: 0; remaining: 2.',{exact:true}).count()===1,'verified mock capture issued correct rides in real DB');
 const id=await page.evaluate(()=>sessionStorage.getItem('csf-gift-pending'));check(!!id,'purchase reference retained without bearer secret');
 const receipt=await raw(customer,'receipt',{order_id:id},{'X-CSF-CSRF':csrf});check(receipt.status===200&&/^CSF-[A-F0-9]{32}$/.test(receipt.data.certificate_number),'private certificate returned to owning session');
 check(!JSON.stringify(await page.evaluate(()=>Object.entries(sessionStorage))).includes(receipt.data.certificate_number),'certificate number absent from browser storage');
 const stranger=await browser.newContext({ignoreHTTPSErrors:true});const other=await raw(stranger,'session');const stolen=await raw(stranger,'receipt',{order_id:id},{'X-CSF-CSRF':other.data.csrf});check(stolen.status===409&&!JSON.stringify(stolen.data).includes(receipt.data.certificate_number),'different session cannot read certificate');
 const anon=await raw(customer,'list');check(anon.status===403,'anonymous management denied');
 await page.addScriptTag({path:require.resolve('axe-core/axe.min.js')});const publicAxe=await page.evaluate(async()=>await axe.run(document.querySelector('.csf-gift-app'),{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa']}}));check(publicAxe.violations.length===0,'public receipt accessibility checks');await page.screenshot({path:root+'/public-mobile.png',fullPage:true});
 const admin=await browser.newContext({ignoreHTTPSErrors:true});const ap=await admin.newPage();await login(ap,'fixture_admin','synthetic-local-admin-only');check(!(await ap.locator('.csf-gift-app').count()),'technical administrator has no financial access');await admin.close();
 const staff=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1280,height:900}});const sp=await staff.newPage();await login(sp,'gift_operator','synthetic-operator-only');await sp.locator('#authcode').waitFor();await settled(sp);
 check(await sp.locator('#authcode').isVisible(),'real official TOTP challenge required');
 const code=cli(`$p=Two_Factor_Totp::get_instance();echo Two_Factor_Totp::calc_totp($p->get_user_totp_key(${operator}),floor(time()/30));`);
 await sp.locator('#authcode').evaluate((el,value)=>{el.value=value;},code);await Promise.all([sp.waitForNavigation({waitUntil:'domcontentloaded'}),sp.locator('#submit').click()]);
 await sp.locator('.csf-gift-app').waitFor({timeout:15000});await sp.getByRole('button',{name:new RegExp(id)}).click();
 await sp.getByRole('heading',{name:'Audit history'}).waitFor();const nonce=await sp.locator('.csf-gift-app').getAttribute('data-nonce');
 const staffHeaders={'X-WP-Nonce':nonce};const details=await raw(staff,'detail',{order_id:id},staffHeaders);check(details.status===200&&details.data.certificate_id===receipt.data.certificate_id,'real MFA plus explicit grant authorizes private details');check(details.data.history.length>=6,'private audit history keeps UUID links');
 const command=crypto.randomUUID(),ride=crypto.randomUUID();const debit={command,certificate_id:receipt.data.certificate_id,rides:1,revision:1,ride_reference:ride};
 const redeemed=await raw(staff,'redeem',debit,staffHeaders);check(redeemed.status===200&&redeemed.data.remaining===1,'real database audited redemption');
 const replay=await raw(staff,'redeem',debit,staffHeaders);check(replay.status===200&&replay.data.remaining===1,'redemption replay does not spend twice');
 const duplicate=await raw(staff,'redeem',{...debit,command:crypto.randomUUID(),revision:2},staffHeaders);check(duplicate.status===409,'same ride under new command rejected');
 const correction={command:crypto.randomUUID(),certificate_id:receipt.data.certificate_id,redemption_command:command,revision:2,reason:'mistaken_ride'};
 const corrected=await raw(staff,'correct',correction,staffHeaders);check(corrected.status===200&&corrected.data.remaining===2,'audited correction restores original debit');
 const correctedAgain=await raw(staff,'correct',{...correction,command:crypto.randomUUID(),revision:3},staffHeaders);check(correctedAgain.status===409,'same debit cannot be corrected twice');
 await sp.reload();await sp.getByRole('button',{name:new RegExp(id)}).click();await sp.getByRole('heading',{name:'Audit history'}).waitFor();
 await sp.getByLabel('I confirm these rides occurred and have checked the history for an existing entry.',{exact:true}).check();await sp.getByRole('button',{name:'Record completed rides',exact:true}).click();
 await sp.getByText('Purchased rides: 2; redeemed: 1; remaining: 1.',{exact:true}).waitFor();check(true,'staff UI records a completed ride');
 await sp.getByLabel('I confirm this reverses a mistaken ledger debit, not a payment refund.',{exact:true}).check();await sp.getByRole('button',{name:'Correct the debit once',exact:true}).click();
 await sp.getByText('Purchased rides: 2; redeemed: 0; remaining: 2.',{exact:true}).waitFor();check(true,'staff UI corrects a selected readable audit entry');
 await sp.addScriptTag({path:require.resolve('axe-core/axe.min.js')});const axe=await sp.evaluate(async()=>await axe.run(document.querySelector('.csf-gift-app'),{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa']}}));check(axe.violations.length===0,'private billing widget accessibility checks');
 await sp.screenshot({path:root+'/private-desktop.png',fullPage:true});
 // Remove only a synthetic financial grant, never an MFA credential or real farm account.
 cli(`get_userdata(${operator})->remove_cap('csf_billing_redeem');`);
 const revoked=await raw(staff,'redeem',debit,staffHeaders);check(revoked.status===403,'revoked grant blocks replay immediately');
 const providerId=cli(`global $wpdb;echo $wpdb->get_var($wpdb->prepare("SELECT provider_order_id FROM gift_fixture_csf_billing_orders WHERE id=%s","${id}"));`);
 const event={id:'SYNTHETIC-REFUND-BROWSER',event_type:'PAYMENT.CAPTURE.REFUNDED',resource:{supplementary_data:{related_ids:{capture_id:'CAP'+providerId}}}};
 const hookHeaders={'paypal-auth-algo':'SHA256withRSA','paypal-cert-url':'https://api.sandbox.paypal.com/v1/notifications/certs/SYNTHETIC-CERT','paypal-transmission-id':'synthetic-id','paypal-transmission-sig':'synthetic-valid-signature','paypal-transmission-time':new Date().toISOString()};
 const webhook=await raw(customer,'webhook',event,hookHeaders);check(webhook.status===200,'verified fixture refund webhook accepted');
 const frozen=await raw(customer,'receipt',{order_id:id},{'X-CSF-CSRF':csrf});check(frozen.data.status==='frozen','refund freezes real ledger credits');
 check(errors.length===0,'no browser JavaScript errors');
 // Expire this synthetic session proof; do not clear enrollment or weaken verification.
 cli(`$sessions=get_user_meta(${operator},'session_tokens',true);foreach($sessions as &$session){if(isset($session[CSF_MFA_Session::KEY])){$session[CSF_MFA_Session::KEY]['expires']=time()-1;}}update_user_meta(${operator},'session_tokens',$sessions);`);
 const expired=await raw(staff,'detail',{order_id:id},staffHeaders);check(expired.status===403,'expired MFA proof blocks financial reads');
 fs.writeFileSync(root+'/browser-evidence.json',JSON.stringify({checks,order:id,certificate:receipt.data.certificate_id,wordpress:cli('echo get_bloginfo("version");'),php:cli('echo PHP_VERSION;'),database:cli('global $wpdb;echo $wpdb->get_var("SELECT VERSION()");'),provider:'mock transport; official MFA real',browser:await browser.version()},null,2));
 console.log('BILLING_BROWSER_ASSERTIONS='+checks);
 await staff.close();await stranger.close();await customer.close();
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
