'use strict';
const {chromium}=require('playwright'),fs=require('fs'),assert=require('assert'),path=require('path');
const output=process.argv[2]||'/tmp';
(async()=>{const browser=await chromium.launch({executablePath:process.env.CHROMIUM_EXECUTABLE||undefined,args:['--no-sandbox']});let checks=0;function check(v,message){assert(v,message||`Assertion ${checks+1}`);checks++;}
async function audit(page){await page.addScriptTag({path:require.resolve('axe-core/axe.min.js')});const result=await page.evaluate(()=>axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa']}}));check(result.violations.length===0,JSON.stringify(result.violations));check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Page overflow');const modal=page.getByRole('dialog');if(await modal.isVisible())check(await modal.evaluate(el=>el.scrollWidth<=el.clientWidth),'Dialog overflow');}
for(const width of [390,768,1280]){
 const page=await browser.newPage({viewport:{width,height:900}});const errors=[];page.on('pageerror',e=>errors.push(e.message));await page.setContent(fs.readFileSync(path.join(output,'directory-preview.html'),'utf8'));
 check(await page.locator('.csf-directory-group:visible').count()===1);
 check(await page.locator('.csf-directory-record:visible').count()===4);
 check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
 await page.getByRole('searchbox').fill('clo');check(await page.locator('.csf-directory-record:visible').count()===1);
 await page.getByRole('searchbox').fill('zzzz');check(await page.locator('.csf-directory-empty').isVisible());
 await page.getByRole('searchbox').fill('Clover');const summary=page.locator('.csf-directory-record:visible > summary');await summary.focus();await page.keyboard.press('Enter');
 const dialog=page.getByRole('dialog');check(await dialog.isVisible());check(await dialog.getByRole('heading',{name:'Clover'}).count()===1);
 await audit(page);await page.screenshot({path:path.join(output,`horse-dialog-${width}.png`),fullPage:true});
 const form=dialog.locator('form[data-kind=horse_saved]');await form.locator('[data-field=label]').fill('Clover revised');
 const seed=await form.locator('[name=body]').inputValue();await page.keyboard.press('Escape');check(!await dialog.isVisible());check(await summary.evaluate(el=>document.activeElement===el));
 await summary.click();check(await form.locator('[data-field=label]').inputValue()==='Clover revised');check(await form.locator('[name=body]').inputValue()===seed);
 await form.locator('.csf-save').click();check(await page.evaluate(()=>lastSubmission.body.expected===5&&lastSubmission.body.data.label==='Clover revised'));
 check(!await dialog.getByText('Permanently delete this unused entry',{exact:true}).isVisible());
 await dialog.locator('.csf-record-lifecycle > summary').click();await dialog.getByText('Delete mistaken unused entry: Clover',{exact:true}).click();
 const del=dialog.locator('form[data-kind=horse_deleted]');check(await del.locator('[data-field=irreversible]').count()===1);check(await del.locator('[data-field=confirm_label]').getAttribute('required')!==null);
 await audit(page);await dialog.getByRole('button',{name:'Close record'}).click();await page.getByRole('button',{name:'Resources',exact:true}).click();check(await page.locator('.csf-directory-record:visible').count()===3);
 await page.locator('.csf-directory-record:visible > summary').first().click();check(await dialog.locator('[data-kind=resource_saved]').count()===1);check(await dialog.locator('[data-kind=availability_saved]').count()===1);await audit(page);await page.keyboard.press('Escape');
 await page.getByRole('button',{name:'Riders',exact:true}).click();check(await page.locator('.csf-directory-record:visible').count()===2);await page.locator('.csf-directory-record:visible > summary').first().click();await audit(page);await page.keyboard.press('Escape');
 await page.getByRole('button',{name:'Protective blocks',exact:true}).click();check(!await page.getByRole('searchbox').isVisible());check(await page.locator('[data-kind=restriction_added]').count()===1);
 await page.getByRole('button',{name:'Horses',exact:true}).click();await audit(page);await page.locator('.csf-directory-record:visible > summary').first().click();await dialog.getByRole('heading').evaluate(el=>el.textContent='L'.repeat(200));await audit(page);await page.screenshot({path:path.join(output,`long-name-dialog-${width}.png`),fullPage:true});await page.keyboard.press('Escape');
 await page.screenshot({path:path.join(output,`directory-${width}.png`),fullPage:true});check(errors.length===0);await page.close();
}
const nojs=await browser.newPage({javaScriptEnabled:false});await nojs.setContent(fs.readFileSync(path.join(output,'directory-preview.html'),'utf8'));check(await nojs.locator('.csf-directory-group:visible').count()===4);check(await nojs.locator('form[data-kind=horse_deleted]').count()===3);
const limited=await browser.newPage();await limited.setContent(fs.readFileSync(path.join(output,'directory-readonly.html'),'utf8'));check(await limited.locator('form').count()===0);
console.log(JSON.stringify({checks,passed:true,scope:'Actual PHP-generated native forms with synthetic context; submission intercepted, no database/server write qualification.'},null,2));await browser.close();})();
