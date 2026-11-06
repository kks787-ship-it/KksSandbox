import {choices,closureAt,escapeHTML as esc} from './projection.mjs';
const data=await fetch('/model.json').then(r=>r.json());
const $=id=>document.getElementById(id),dialog=$('detail');let dp,opener;
window.experiment={renders:[],previews:[],eventClicks:[],writes:0};
for(const date of data.dates)$('date').add(new Option(date,date));$('date').value=data.week;
const model=()=>data.scopes[$('scope').value];
const naive=d=>d.toString('yyyy-MM-ddTHH:mm');
function open(title,text,buttons=[]){opener=document.activeElement;$('detail-title').textContent=title;$('detail-text').textContent=text;$('occurrences').replaceChildren(...buttons);if(!dialog.open)dialog.showModal();}
$('close-detail').addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>opener?.focus());
function details(id){const a=model().appointments.find(e=>e.uuid===id);if(!a)return;experiment.eventClicks.push(id);open('Fictional lesson details',`${a.title}\n${a.start} → ${a.end}\n${a.state}${a.state==='requested'?' · NOT RESERVED':''}\n${a.family??'Independent visit'}\n${a.horse??'Horse not assigned'}\n${a.override?'BUFFER OVERRIDE · illustrative only':''}\n${a.welfare_review?'Welfare review required':''}\n${!a.rider_cleared?'Rider not cleared':''}\nNo engine preview or save is available.`);}
function preview(local,resource=''){
 if(!model().resources.length)return open('Access denied','No authorized projection.');
 const valid=choices(model(),local);if(!valid.length)return open('Time unavailable','This local time does not exist on the authoritative PHP quarter-hour ruler.');
 const closure=closureAt(model(),local,resource);if(closure)return open('Closed resource',closure.label+' · no booking preview.');
 const select=iso=>{experiment.previews.push({iso,resource});open('Read-only candidate preview',`${iso}\n${resource||'Instructor and arena not assigned'}\nNOT RESERVED. The actual PHP engine must recheck conflicts, horse welfare, clearance and facility policy before any real booking.`);};
 if(valid.length===1)return select(valid[0].iso);
 const buttons=valid.map(t=>{const b=document.createElement('button');b.textContent=t.label+' · '+t.iso.slice(-6);b.addEventListener('click',()=>select(t.iso));return b;});open('Choose clock-change occurrence','This wall time occurs twice; do not guess an offset.',buttons);
}
function render(){const started=performance.now(),view=$('view').value,date=$('date').value,scope=$('scope').value,p=model();
 const actualView=view==='week'&&date!==data.week?'day':view;
 const arena=p.resources.find(r=>r.kind==='arena');const nativeView=actualView==='resources'&&$('group').value==='arena'&&arena?'filter-'+arena.id:actualView;
 $('native').src='/native.html?'+new URLSearchParams({view:nativeView,date,scope});
 $('families').replaceChildren(...(scope==='staff'?data.conceptualBuffers:[]).map(f=>{const li=document.createElement('li');li.textContent=f.family+': illustrative outer interval '+f.before+'–'+f.after+'; '+p.appointments.filter(a=>a.family===f.family).map(a=>a.title).join(' → ');return li;}));
 $('native').onload=()=>experiment.renders.push({pane:'native-dom-navigation',ms:performance.now()-started});
 if(dp)dp.dispose();dp=null;window.daypilot=null;$('lite').replaceChildren();$('lite').removeAttribute('class');$('lite').removeAttribute('style');$('lesson').replaceChildren(...p.appointments.map(a=>new Option(a.title,a.uuid)));
 if(!p.resources.length){$('lite').textContent='Access denied: no authorized calendar data.';$('status').textContent='Denied synthetic projection; no lessons or resources.';return;}
 const isResource=actualView==='resources',columns=p.resources.filter(r=>r.kind===$('group').value),events=[];
 for(const a of p.appointments){const selected=isResource?columns.filter(c=>a.resources.includes(c.id)):[null];for(const col of selected)events.push({id:a.uuid+(col?'@'+col.id:''),start:a.start.slice(0,16)+':00',end:a.end.slice(0,16)+':00',text:a.title,resource:col?.id,sourceId:a.uuid,kind:'lesson',state:a.state,override:a.override});}
 for(const c of p.closures){if(!isResource||columns.some(r=>r.id===c.resource))events.push({id:c.id,start:c.start.slice(0,16)+':00',end:c.end.slice(0,16)+':00',resource:c.resource,text:c.label,kind:'closure'});}
 const t=performance.now();dp=new DayPilot.Calendar('lite',{startDate:date,viewType:isResource?'Resources':actualView==='week'?'Week':'Day',weekStarts:1,columns:columns.map(r=>({id:r.id,name:r.label})),cellDuration:15,cellHeight:22,businessBeginsHour:['2026-11-01','2027-03-14'].includes(date)?0:8,businessEndsHour:['2026-11-01','2027-03-14'].includes(date)?4:18,heightSpec:'BusinessHours',eventMoveHandling:'Disabled',eventResizeHandling:'Disabled',eventDeleteHandling:'Disabled',eventClickHandling:'Enabled',timeRangeSelectedHandling:'Enabled',xssProtection:'Enabled',events,
 onBeforeEventRender:args=>{const e=args.data;e.html=esc(e.text)+'<br>'+esc(e.kind==='closure'?'Closed':e.state+(e.state==='requested'?' · NOT RESERVED':''));e.backColor=e.kind==='closure'?'#d5d9d7':e.override?'#a71925':e.state==='canceled'?'#e4e4e4':'#d5ece0';e.fontColor=e.override?'#fff':'#162f24';e.barColor=e.override?'#a71925':'#21554a';},
 onBeforeCellRender:args=>{const c=closureAt(p,naive(args.cell.start),args.cell.resource);if(c)args.cell.properties.backColor='#c4c9c5';},
 onEventClick:args=>args.e.data.kind==='closure'?open('Closed resource',args.e.text()+' · illustrative closure only.'):details(args.e.data.sourceId),
 onTimeRangeSelected:args=>{dp.clearSelection();preview(naive(args.start),args.resource??'');}
 });dp.init();dp.scrollToHour(['2026-11-01','2027-03-14'].includes(date)?0:8);window.daypilot=dp;
 experiment.renders.push({pane:'daypilot-js-init',ms:performance.now()-t});$('status').textContent=`Synthetic ${scope} · ${actualView} · ${date} · PHP ruler: ${p.ticks[date].length} quarter-hours. No writes.`;
}
for(const id of['view','date','group','scope'])$(id).addEventListener('change',render);$('refresh').addEventListener('click',render);
$('show-lesson').addEventListener('click',()=>details($('lesson').value));$('probe').addEventListener('click',()=>preview($('local').value));
window.addEventListener('message',e=>{if(e.origin!==location.origin||e.source!==$('native').contentWindow)return;if(e.data.type==='native-preview')experiment.previews.push({iso:e.data.iso,source:'native'});if(e.data.type==='native-navigation'&&data.dates.includes(e.data.date)){ $('date').value=e.data.date;$('view').value=e.data.view;render();}});
window.fixturePreview=preview;window.fixtureModel=model;render();

$('display').addEventListener('change',()=>{document.querySelector('.comparison').dataset.display=$('display').value;requestAnimationFrame(()=>dp?.update());});
