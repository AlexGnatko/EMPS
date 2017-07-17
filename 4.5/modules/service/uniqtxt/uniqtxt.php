<?php
$emps->no_smarty = true;

require_once $emps->common_module('uniqtxt/uniqtxt.class.php');
$utxt = new EMPS_UniqueTexts;

if(!$utxt->check_available()){
    echo "Unavailable";
    exit;
}

$emps->no_time_limit();

$rv = $emps->service_control("uniqtxt", 24 * 60 * 60);
if($rv['wait']){
    echo "Waiting until ".$emps->form_time($rv['nextrun']);
    exit;
}

$hb = new EMPS_Heartbeat;

for($i = 0; $i < 100; $i++){
    $ra = $utxt->get_next_to_upload("status_yandex");
    if(!$ra){
        break;
    }
    $hb->add_url("/service-uniqtxt-upload/".$ra['id']."/");
}

$hb->execute();