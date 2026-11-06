// All people, horses and resources are invented. No farm input is accepted.
export const zone = 'America/Los_Angeles';
export const week = '2026-11-09';
export const dates = ['2026-11-09','2026-11-10','2026-11-11','2026-11-12','2026-11-13','2026-11-14','2026-11-15','2026-11-01','2027-03-14'];
export const resources = [
 {id:'instructor-pine',kind:'instructor',label:'Fictional Pine Instructor'},
 {id:'instructor-cedar',kind:'instructor',label:'Fictional Cedar Instructor'},
 {id:'arena-meadow',kind:'arena',label:'Fictional Meadow Arena'},
 {id:'arena-covered',kind:'arena',label:'Fictional Covered Arena'}
];
const event=(id,title,day,start,minutes,instructor='instructor-pine',arena='arena-meadow',extra={})=>{
 const begin=`${day}T${start}-08:00`,end=new Date(Date.parse(begin)+minutes*60000-8*3600000).toISOString().slice(0,16)+'-08:00';
 return {uuid:id,title,state:'confirmed',start:begin,end,resources:[instructor,arena],rider_cleared:true,welfare_review:false,hold_expired:false,privateNote:'SYNTHETIC_PRIVATE_SENTINEL',...extra};
};
export const appointments = [
 event('sibling-a','Fictional sibling A · 30m',week,'10:00',30,undefined,undefined,{family:'fictional-family-a',horse:'Fictional Maple'}),
 event('sibling-b','Fictional sibling B · 30m',week,'10:30',30,undefined,undefined,{family:'fictional-family-a',horse:'Fictional Cedar'}),
 event('override','BUFFER OVERRIDE · fictional private','2026-11-10','10:00',60,'instructor-cedar','arena-covered',{override:true}),
 event('family-120a','Family block A · 120m','2026-11-11','08:00',120,undefined,undefined,{family:'fictional-family-b',horse:'Illustrative rotation; not welfare-approved'}),
 event('family-120b','Family block B · 120m','2026-11-11','10:00',120,undefined,undefined,{family:'fictional-family-b',horse:'Illustrative rotation; not welfare-approved'}),
 event('family-60','Family block C · 60m','2026-11-11','12:00',60,undefined,undefined,{family:'fictional-family-b',welfare_review:true}),
 event('overlap','Parallel fictional lesson','2026-11-11','09:00',60,'instructor-cedar','arena-covered'),
 event('request','Requested · NOT RESERVED','2026-11-12','11:30',30,undefined,undefined,{state:'requested',rider_cleared:false}),
 event('canceled','Canceled · history only','2026-11-13','14:00',30,'instructor-cedar','arena-covered',{state:'canceled'}),
 event('held','Held fictional lesson','2026-11-13','10:00',30),
 { ...event('fold-first','First 01:30 · PDT','2026-11-01','01:30',15),start:'2026-11-01T01:30-07:00',end:'2026-11-01T01:45-07:00'},
 event('fold-second','Second 01:30 · PST','2026-11-01','01:30',15)
];
appointments.find(e=>e.uuid==='held').state='held';
export const closures = [
 {id:'closure-tuesday',resource:'arena-meadow',start:'2026-11-10T09:00-08:00',end:'2026-11-10T11:00-08:00',label:'CLOSED · Fictional Meadow Arena'},
 {id:'closure-friday',resource:'arena-covered',start:'2026-11-13T12:00-08:00',end:'2026-11-13T15:00-08:00',label:'CLOSED · Fictional Covered Arena'}
];
export const conceptualBuffers = [{family:'fictional-family-a',before:'09:45',after:'11:15'},{family:'fictional-family-b',before:'07:45',after:'13:15'}];
