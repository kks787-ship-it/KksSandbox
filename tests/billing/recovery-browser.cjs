'use strict';
// Functional restored-site test; only the separate, already restored synthetic database.
const fs=require('fs'),assert=require('assert/strict'),{spawn,execFileSync}=require('child_process'),{chromium}=require('playwright');
const root='/tmp/csf-billing-fixture',php=process.env.CSF_BILLING_TEST_PHP||root+'/php',site=fs.readFileSync(root+'/restored-path.txt','utf8'),base='https://127.0.0.1:18444';
assert([root+'/php','/tmp/csf-gift-matrix/php8.1','/tmp/csf-gift-matrix/php8.3'].includes(php));
assert(site.startsWith(root+'/recovery-')&&site.endsWith('/recovered/site'));
const cli=code=>execFileSync(php,['.wp-local/wp-cli.phar','--path='+site,'eval','if(DB_NAME!=="csf_gift_restore"||CSF_Billing_Config::enabled()){throw new RuntimeException("Wrong recovery target");}'+code],{encoding:'utf8'}).trim();
const router=root+'/restore-router.php',proxy=root+'/restore-tls.cjs';
fs.writeFileSync(router,`<?php\n$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);if(is_file('${site}'.$path)){return false;}require '${site}/index.php';`);
fs.writeFileSync(proxy,fs.readFileSync(root+'/tls.cjs','utf8').replace("req.headers['x-fixture-lane']==='B'?18081:18080",'18082').replace(".listen(18443,",'.listen(18444,'));
const servers=[spawn(php,['-S','127.0.0.1:18082','-t',site,router],{stdio:'ignore'}),spawn(process.execPath,[proxy],{stdio:'ignore'})];
(async()=>{let browser;try{
 browser=await chromium.launch({executablePath:process.env.CSF_TEST_CHROMIUM,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 const context=await browser.newContext({ignoreHTTPSErrors:true});const page=await context.newPage();
 await page.goto(base+'/gift-certificates/');assert.equal(await page.getByText('Online gift certificate purchases are not available yet.',{exact:true}).count(),1);
 const route=await context.request.post(base+'/?rest_route=/csf-billing/v1/session',{headers:{Origin:base},data:{}});assert.equal(route.status(),404);
 await page.goto(base+'/wp-login.php?redirect_to='+encodeURIComponent(base+'/wp-admin/admin.php?page=csf-gift-certificates'));
 await page.evaluate(()=>new Promise(r=>window.jQuery?window.jQuery(()=>setTimeout(r,0)):r()));
 await page.locator('#user_login').fill('gift_operator');await page.locator('#user_pass').fill('synthetic-operator-only');
 await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('#wp-submit').click()]);await page.locator('#authcode').waitFor();
 const code=cli('$p=Two_Factor_Totp::get_instance();echo Two_Factor_Totp::calc_totp($p->get_user_totp_key(2),floor(time()/30));');
 await page.locator('#authcode').evaluate((el,value)=>el.value=value,code);await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('#submit').click()]);
 await page.getByRole('heading',{name:'Private gift certificates',exact:true}).waitFor();
 assert.equal(cli('$r=wp_remote_get("https://example.invalid/");echo is_wp_error($r)?$r->get_error_code():"BAD";'),'isolated_recovery');
 assert.equal(cli('echo apply_filters("pre_wp_mail",null,array())===true?"BLOCKED":"BAD";'),'BLOCKED');
 await page.screenshot({path:root+'/restored-login.png',fullPage:true});
 const evidence=JSON.parse(fs.readFileSync(root+'/recovery-evidence.json'));Object.assign(evidence,{restored_https_page:true,restored_official_password_totp_login:true,restored_checkout_route_absent:true,outbound_blocks_exercised:true});fs.writeFileSync(root+'/recovery-evidence.json',JSON.stringify(evidence,null,2));fs.writeFileSync(evidence.capture_directory+'/evidence.json',JSON.stringify(evidence,null,2));
 console.log('RESTORED_HTTPS_PAGE_OFFICIAL_MFA_LOGIN_AND_OUTBOUND_BLOCKS_PASS');
 }finally{if(browser)await browser.close();for(const server of servers)server.kill('SIGTERM');}})().catch(e=>{console.error(e);process.exitCode=1;});
