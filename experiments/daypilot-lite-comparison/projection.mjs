// Test adapter: accepts an ALREADY AUTHORIZED read model, never authenticates a user.
// This synthetic fixture is not a production authorization adapter or booking engine.
export function project(input,scope){
 if(!scope||scope.calendarView!==true)return {resources:[],appointments:[],closures:[],zone:input.zone};
 const allowed=new Set(Array.isArray(scope.resourceIds)?scope.resourceIds:[]),ids=new Set(Array.isArray(scope.appointmentIds)?scope.appointmentIds:[]);
 const resources=input.resources.filter(r=>allowed.has(r.id)).map(({id,kind,label})=>({id,kind,label}));
 const appointments=input.appointments.filter(a=>scope.all===true||ids.has(a.uuid)).map(a=>({
  uuid:a.uuid,title:a.title,state:a.state,start:a.start,end:a.end,
  resources:a.resources.filter(id=>allowed.has(id)),rider_cleared:a.rider_cleared,
  welfare_review:a.welfare_review,hold_expired:a.hold_expired,
  family:a.family??null,horse:a.horse??null,override:a.override===true
 }));
 return {zone:input.zone,resources,appointments,closures:input.closures.filter(c=>allowed.has(c.resource)).map(({id,resource,start,end,label})=>({id,resource,start,end,label}))};
}
export function choices(projection,local){return (projection.ticks[local.slice(0,10)]??[]).filter(t=>t.local===local);}
export function closureAt(projection,local,resource){
 return projection.closures.find(c=>c.resource===resource&&local>=c.start.slice(0,16)&&local<c.end.slice(0,16));
}
export const escapeHTML=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
