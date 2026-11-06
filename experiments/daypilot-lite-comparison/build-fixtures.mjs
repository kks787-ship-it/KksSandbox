import {mkdir,writeFile,readFile,copyFile} from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import * as fixtures from './fixtures.mjs';
import {project} from './projection.mjs';
const directory=fileURLToPath(new URL('.',import.meta.url));
const scope={calendarView:true,all:true,appointmentIds:[],resourceIds:fixtures.resources.map(r=>r.id)};
const scopes={staff:project(fixtures,scope),instructor:project(fixtures,{...scope,all:false,appointmentIds:['sibling-a','request'],resourceIds:['instructor-pine','arena-meadow']}),denied:project(fixtures,{calendarView:false})};
await mkdir(directory+'.artifacts',{recursive:true});
await writeFile(directory+'.artifacts/input.json',JSON.stringify({fixture:'invented-daypilot-comparison',week:fixtures.week,dates:fixtures.dates,zone:fixtures.zone,scopes}));
const renderer=process.env.CSF_EXPERIMENT_PHP?execFileSync(process.env.CSF_EXPERIMENT_PHP,[directory+'native-render.php'],{env:{...process.env,CSF_DAYPILOT_FIXTURE:'synthetic-only'},maxBuffer:16*1024*1024}):execFileSync('docker',['--host=unix:///var/run/docker.sock','run','--rm','--network','none','--mount',`type=bind,src=${fileURLToPath(new URL('../..',import.meta.url))},dst=/repo,readonly`,'-e','CSF_DAYPILOT_FIXTURE=synthetic-only','csf-scheduling-php83','php','/repo/experiments/daypilot-lite-comparison/native-render.php'],{maxBuffer:16*1024*1024});
const native=JSON.parse(renderer);
await writeFile(directory+'.artifacts/model.json',JSON.stringify({...native,scopes:Object.fromEntries(Object.entries(scopes).map(([k,v])=>[k,{...v,ticks:native.ticks}])),dates:fixtures.dates,week:fixtures.week,conceptualBuffers:fixtures.conceptualBuffers}));
console.log('Synthetic native PHP projection and DayPilot fixture built; no DB/network access.');

await mkdir(directory+'.artifacts/native-assets',{recursive:true});
const provenance={source:'kks787-ship-it/KksSandbox',implementation:'c2783b5e3f6eba046b97e5dc517a198517532377',files:{}};
for(const name of['assets/planner.js','assets/planner.css','assets/staff.css','includes/class-csf-scheduling-planner.php','includes/class-csf-scheduling-time.php']){
 const source=new URL('../../wp-content/plugins/csf-scheduling/'+name,import.meta.url);provenance.files[name]=createHash('sha256').update(await readFile(source)).digest('hex');
 if(name.startsWith('assets/'))await copyFile(source,directory+'.artifacts/native-assets/'+name.split('/').at(-1));
}
await writeFile(directory+'.artifacts/native-source.json',JSON.stringify(provenance,null,2)+'\n');
