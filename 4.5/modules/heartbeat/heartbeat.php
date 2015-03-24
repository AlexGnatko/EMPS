<?php

$emps->no_smarty = true;

$data = file_get_contents(EMPS_SCRIPT_WEB."/sendmail/");
$data = file_get_contents(EMPS_SCRIPT_WEB."/purge_sessions/");

$fn = $emps->page_file_name("_heartbeat,project", "controller");
if(file_exists($fn)){
	require_once $fn;
}

$fn = $emps->page_file_name("_heartbeat,local", "controller");
if(file_exists($fn)){
	require_once $fn;
}

?>