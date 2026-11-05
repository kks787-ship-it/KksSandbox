<?php
// Disposable local form-parser and domain-validation cases; never open a farm config.
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) {
	throw new RuntimeException( 'Disposable fixture required.' );
}
$fields = array(
 'timezone'=>'America/Los_Angeles','hours'=>array_fill(0,7,array(array('start'=>'','end'=>''))),
 'daily_minutes'=>'120','consecutive_minutes'=>'30','break_minutes'=>'15','training_horse_cap'=>'3',
 'preferred_offsite_day'=>'3','travel_buffer_minutes'=>'60','academy'=>array(),
 'services'=>array(array('name'=>'Pilot Lesson','enabled'=>'1','capacity'=>'1','duration_minutes'=>'30','horse_source'=>'school','resources'=>array('instructor','arena','horse'))),
);
$fields['hours'][1]=array(array('start'=>'08:00','end'=>'12:00'),array('start'=>'13:00','end'=>'18:00'));
$parsed=CSF_Scheduling_Settings::parse($fields);
if(is_wp_error(CSF_Scheduling_Policy::validate($parsed))||CSF_Scheduling_Policy::missing($parsed)||count($parsed['service_hours'][1])!==2||$parsed['service_hours'][0]!==array())throw new RuntimeException('Valid native split hours rejected');
$count=1;
$bad=array();
$x=$fields;$x['timezone']=array();$bad[]=$x;
$x=$fields;$x['daily_minutes']='1.5';$bad[]=$x;
$x=$fields;$x['break_minutes']='-1';$bad[]=$x;
$x=$fields;unset($x['hours'][6]);$bad[]=$x;
$x=$fields;$x['hours'][1]=array('unexpected');$bad[]=$x;
$x=$fields;$x['services'][0]['horse_source']=array();$bad[]=$x;
$x=$fields;$x['services'][0]['resources']='instructor';$bad[]=$x;
$x=$fields;$x['services'][0]['enabled']=array('1');$bad[]=$x;
$x=$fields;$x['services'][]=$x['services'][0];$bad[]=$x;
$x=$fields;$x['services'][0]['name']='<script>'; $bad[]=$x;
$x=$fields;$x['academy']=array('enabled'=>'1','weekday'=>'1','capacity'=>'4','start'=>array());$bad[]=$x;
foreach($bad as$x){try{CSF_Scheduling_Settings::parse($x);}catch(RuntimeException$e){++$count;continue;}throw new RuntimeException('Malformed native form accepted');}
$x=$fields;$x['hours'][1][1]['start']='11:00';if(!is_wp_error(CSF_Scheduling_Policy::validate(CSF_Scheduling_Settings::parse($x))))throw new RuntimeException('Overlapping hours accepted');++$count;
$x=$fields;$x['services'][0]['resources']=array('unknown');if(!is_wp_error(CSF_Scheduling_Policy::validate(CSF_Scheduling_Settings::parse($x))))throw new RuntimeException('Unknown resource kind accepted');++$count;
$x=$fields;$x['training_horse_cap']='';if(CSF_Scheduling_Settings::parse($x)['training_horse_cap']!==null)throw new RuntimeException('Blank limit guessed');++$count;
echo 'FACILITY_SETTINGS_PARSER_CHECKS='.$count."\n";
