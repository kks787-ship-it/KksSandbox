<?php
ob_start();require '/repo/tests/scheduling/auto-slot-fixture.php';$ids=json_decode(ob_get_clean(),true);
$p=json_decode(CSF_Scheduling_Schema::settings()['policy_json'],true);$p['services']['pilot_ground_30']=$p['services']['pilot_ground_60'];$p['services']['pilot_ground_30']['duration_minutes']=30;
$r=CSF_Scheduling_Store::configure($p,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic 30-minute family service');
$ids['riders'][]=$save('rider_saved',['label'=>'Synthetic second sibling','adult'=>false,'release_verified'=>false,'guardian_verified'=>false,'emergency_verified'=>false]);echo wp_json_encode($ids);
