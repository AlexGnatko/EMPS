<?php

require_once $emps->common_module('photos/photos.class.php');

$photos = new EMPS_Photos();

$lst = $photos->list_pics($key, 10000);

$response = [];
$response['code'] = "OK";
$response['files'] = $lst;

$emps->json_response($response);