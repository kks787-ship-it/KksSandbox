'use strict';
// Supplemental real Chromium UI checks with explicit synthetic API responses; not backend/MFA proof.
const {chromium}=require('playwright');const fs=require('fs');const assert=require('assert/strict');
const source=fs.readFileSync('wp-content/plugins/csf-billing/assets/billing.js','utf8');
const id='11111111-1111-4111-8111-111111111111';const evidence='22222222-2222-4222-8222-222222222222';
const data={order_id:id,certificate_id:id,certificate_number:'SYNTHETIC',package:'Synthetic package',status:'issued',purchased:3,redeemed:1,revision:1,can_manage:true,can_redeem:true,history:[],terms:{version:'synthetic',text:'Not commercial terms'},accepted_at:'synthetic',value:{paid_minor:101,allocation:[33,34,34],used_slots:[0],cash_paid_minor:0,pending:null,policy:{approved:true}}};
let checks=0;const calls=[];function check(ok,label){assert.ok(ok,label);checks++;}
(async()=>{const browser=await chromium.launch({executablePath:process.env.CSF_TEST_CHROMIUM||'/usr/bin/chromium',headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});try{
const page=await browser.newPage({viewport:{width:390,height:844}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
await page.route('**/*',async route=>{const u=new URL(route.request().url());if(u.origin!=='https://fixture.invalid')throw Error('Unexpected network');
 if(u.pathname==='/')return route.fulfill({contentType:'text/html',body:'<meta charset="utf-8"><section class="csf-gift-app" data-mode="admin" data-api="/api/" data-nonce="synthetic-nonce"><div class="csf-gift-content"></div><p class="csf-gift-status"></p></section><script src="/billing.js"></script>'});
 if(u.pathname==='/billing.js')return route.fulfill({contentType:'application/javascript; charset=utf-8',body:source});
 const action=u.pathname.split('/').pop();const b=route.request().postDataJSON();let result;
 if(action==='list')result={orders:[{id,status:'issued',created_at:'Synthetic'}]};else if(action==='detail')result=data;else if(action==='cash'){
  calls.push(b);check(route.request().headers()['x-wp-nonce']==='synthetic-nonce','private nonce included');check(b.attested===true&&b.amount_minor===68&&b.evidence===evidence,'exact amount and attestation/evidence');
  if(b.transition==='reserve')data.value.pending={id:b.request,amount_minor:68,state:'reserved'};
  else if(b.transition==='uncertain')data.value.pending.state='uncertain';
  else if(b.transition==='record_paid'){data.value.pending=null;data.value.cash_paid_minor=68;}
  else throw Error('Unexpected cash action');data.revision++;result={payment_executed:false};
 }else throw Error('Unexpected API action');return route.fulfill({json:result});});
await page.goto('https://fixture.invalid/');await page.getByRole('button',{name:/Synthetic — issued/}).click();
await page.getByText(/Paid value: \$1.01/).waitFor();check(await page.getByText(/remaining value: \$0.68/).count()===1,'cent balances visible');
check(await page.getByText(/below \$5/).count()===1,'small remaining value flagged');
const cashForm=()=>page.locator('form').filter({has:page.getByRole('heading',{name:'Cash redemption record'})});
await cashForm().getByRole('button').click();check(calls.length===0,'browser blocks unattested payout record');
async function submit(action){const f=cashForm();await f.getByRole('combobox',{name:'Cash record action'}).selectOption(action);await f.getByRole('textbox').fill(evidence);await f.getByRole('checkbox').check();await f.getByRole('button').click();await page.waitForFunction(()=>!document.querySelector('.csf-gift-app').hasAttribute('aria-busy'));}
await submit('reserve');await page.getByText(/Value is reserved, not yet/).waitFor();check(calls[0].transition==='reserve','reserve operation explicit');
const request=calls[0].request;await submit('uncertain');await page.getByText(/uncertain. Value is reserved/).waitFor();check(calls[1].request===request,'uncertain outcome retains reservation identity');
await submit('record_paid');await page.getByText(/cash paid \(recorded\): \$0.68/).waitFor();check(calls[2].request===request&&calls[2].revision===3,'recorded payout bound to current reservation/revision');
check(await page.getByRole('heading',{name:'Cash redemption record'}).count()===0,'no second cash workflow after exhausted value');
check(errors.length===0,'no browser runtime errors');console.log('VALUE_BROWSER_ASSERTIONS='+checks+'; mocked API, no backend or payment proof');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
