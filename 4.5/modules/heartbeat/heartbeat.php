<?php

$emps->no_smarty = true;

set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', -1);

$data = file_get_contents(EMPS_SCRIPT_WEB."/sendmail/");
$data = file_get_contents(EMPS_SCRIPT_WEB."/sendsms/");
$data = file_get_contents(EMPS_SCRIPT_WEB."/purge_sessions/");
$data = file_get_contents(EMPS_SCRIPT_WEB."/smartyservice/");

$fn = $emps->page_file_name("_heartbeat,project", "controller");
if(file_exists($fn)){
	require_once $fn;
}

$fn = $emps->page_file_name("_heartbeat,local", "controller");
if(file_exists($fn)){
	require_once $fn;
}



?>