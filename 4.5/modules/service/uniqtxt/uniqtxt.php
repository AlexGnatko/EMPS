<?php

require_once $emps->common_module('uniqtxt/uniqtxt.class.php');
$utxt = new EMPS_UniqueTexts;

if(!$utxt->check_available()){
    echo "Unavailable";
    exit;
}

echo $_SESSION['OAUTH_ACCESS_TOKEN'];


$emps->no_time_limit();

$rv = $emps->service_control("uniqtxt", 24 * 60 * 60);
if($rv['wait']){
    echo "Waiting until ".$emps->form_time($rv['nextrun']);
    exit;
}

for($i = 0; $i < 100; $i++){

}