<?php

require_once $emps->common_module('videos/vue/processor.class.php');

$vp = new EMPS_VideoProcessor();

$context_id = intval($key);
$vp->context_id = $context_id;
$lst = $vp->list_videos();

$response = [];
$response['code'] = "OK";
$response['videos'] = $lst;

$emps->json_response($response);