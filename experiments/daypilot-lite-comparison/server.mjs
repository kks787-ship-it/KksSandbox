import http from 'node:http';
import {readFile} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import {escapeHTML as esc} from './projection.mjs';
const root=fileURLToPath(new URL('.',import.meta.url));
export async function startServer(port=8777){
 const model=JSON.parse(await readFile(root+'.artifacts/model.json','utf8'));
 const files={'/':'index.html','/prototype.mjs':'prototype.mjs','/prototype.css':'prototype.css','/projection.mjs':'projection.mjs','/native-bridge.js':'native-bridge.js','/DAYPILOT-LICENSE.txt':'DAYPILOT-LICENSE.txt','/DAYPILOT-NOTICE.txt':'DAYPILOT-NOTICE.txt','/vendor/daypilot.js':'node_modules/@daypilot/daypilot-lite-javascript/daypilot-javascript.min.js','/native-planner.js':'.artifacts/native-assets/planner.js','/native-planner.css':'.artifacts/native-assets/planner.css','/native-staff.css':'.artifacts/native-assets/staff.css','/axe.js':'node_modules/axe-core/axe.min.js'};
 const server=http.createServer(async(req,res)=>{
  res.setHeader('Content-Security-Policy',"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-src 'self'; form-action 'none'; base-uri 'none'; object-src 'none'; frame-ancestors 'self'");
  res.setHeader('Cache-Control','no-store');res.setHeader('X-Content-Type-Options','nosniff');
  if(!['GET','HEAD'].includes(req.method)){res.writeHead(405);return res.end('Read-only experiment');}
  if(!/^(127\.0\.0\.1|localhost):[0-9]+$/.test(req.headers.host??'')){res.writeHead(403);return res.end('Loopback host only');}
  try{const url=new URL(req.url,'http://localhost');let data,type;
   if(url.pathname==='/model.json'){data=JSON.stringify(model);type='application/json';}
   else if(url.pathname==='/native.html'){
    const scope=url.searchParams.get('scope'),date=url.searchParams.get('date'),view=url.searchParams.get('view');
    const html=model.native[scope]?.[date]?.[view];if(typeof html!=='string'){res.writeHead(404);return res.end('Unknown synthetic view');}
    const cards=model.scopes[scope].appointments.map(a=>`<article id="csf-event-${esc(a.uuid)}"><h3>${esc(a.title)}</h3><p>${esc(a.start)} – ${esc(a.end)}</p><p>${esc(a.state)} · ${esc(a.family??'independent visit')} · ${esc(a.horse??'horse not assigned')}</p><p>Fictional fixture; not an approved reservation.</p></article>`).join('');
    data=`<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Native fictional renderer</title><style>@media(min-width:701px){#csf-planner-dialog:modal{position:fixed;inset:0;margin:auto}}</style><link rel="stylesheet" href="/native-staff.css"><link rel="stylesheet" href="/native-planner.css"><script src="/native-planner.js" defer></script><script src="/native-bridge.js" defer></script></head><body style="margin:0;font:14px system-ui,sans-serif"><main class="csf-calendar">${html}<form id="csf-new-appointment"><p id="native-selection" role="status" tabindex="-1">Click a candidate slot: fixture preview only.</p></form><div hidden>${cards}</div></main></body></html>`;type='text/html';
   }else if(files[url.pathname]){data=await readFile(root+files[url.pathname]);type=url.pathname.endsWith('.css')?'text/css':url.pathname.endsWith('.js')||url.pathname.endsWith('.mjs')?'text/javascript':url.pathname==='/'?'text/html':'text/plain';}
   else{res.writeHead(404);return res.end('Not in experiment allowlist');}
   res.writeHead(200,{'Content-Type':type+'; charset=utf-8'});res.end(req.method==='HEAD'?'':data);
  }catch{res.writeHead(500);res.end('Fixture unavailable');}
 });
 await new Promise(resolve=>server.listen(port,'127.0.0.1',resolve));return server;
}
if(process.argv[1]===fileURLToPath(import.meta.url)){const s=await startServer();console.log('Synthetic comparison: http://127.0.0.1:'+s.address().port);}
