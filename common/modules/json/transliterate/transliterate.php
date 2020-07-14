<?php

$emps->no_smarty = true;

$response = array();
$response['code'] = "OK";
$response['value'] = $emps->transliterate_url($_GET['text']);

echo json_encode($response);

