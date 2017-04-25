<?php

$emps->no_smarty = true;

if($emps->should_prevent_session()){
    $retry = intval($_GET['retry']);
    if($retry < 3) {
        $retry++;
        $emps->redirect_page("./?retry=".$retry);exit();
    }
}

$response = array();
$response['code'] = "OK";
$emps->json_response($response);