// Local collection only; this never commits or publishes artifacts.
import {readFile,writeFile,copyFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
const root=new URL('.',import.meta.url),json=async path=>JSON.parse(await readFile(new URL(path,root),'utf8'));
const receipt=await json('.artifacts/browser-results.json');
if(receipt.status!=='completed'||receipt.contexts.some(c=>c.errors.length||c.external.length||c.writes.length))throw Error('Completed isolated browser run required');
for(const name of receipt.screenshots){if(!/^screenshots\/[a-z0-9-]+\.png$/.test(name))throw Error('Unexpected artifact path');await mkdir(new URL('evidence/screenshots/',root),{recursive:true});await copyFile(new URL('.artifacts/'+name,root),new URL('evidence/'+name,root));}
await copyFile(new URL('.artifacts/browser-results.json',root),new URL('evidence/browser-results.json',root));
const validation=await json('evidence/validation.json');validation.generated_model_sha256=createHash('sha256').update(await readFile(new URL('.artifacts/model.json',root))).digest('hex');
await writeFile(new URL('evidence/validation.json',root),JSON.stringify(validation,null,2)+'\n');
const reviewed=await json('evidence/files.sha256.json');
for(const name of Object.keys(reviewed))reviewed[name]=createHash('sha256').update(await readFile(new URL(name,root))).digest('hex');
await writeFile(new URL('evidence/files.sha256.json',root),JSON.stringify(reviewed,null,2)+'\n');
console.log('Collected local fictional screenshots/receipts; review before publication. Historical decision/log hashes remain dated baseline evidence.');
