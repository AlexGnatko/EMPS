<?php

$emps->no_smarty = true;

set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', -1);

require_once $emps->core_module("heartbeat.class");

$hb = new EMPS_Heartbeat;

$hb->add_url("/sendmail/");
$hb->add_url("/sendsms/");
$hb->add_url("/purge_sessions/");
$hb->add_url("/smartyservice/");

$hb->execute();

$fn = $emps->page_file_name("_heartbeat,project", "controller");
if (file_exists($fn)) {
    require_once $fn;
}

$fn = $emps->page_file_name("_heartbeat,local", "controller");
if (file_exists($fn)) {
    require_once $fn;
}

