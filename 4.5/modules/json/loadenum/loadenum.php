<?php

$emps->no_smarty = true;

header("Content-Type: application/json; charset=utf-8");

$code = $key;

$response = array();
if(isset($emps->enum[$code])){
	$response['code'] = 'OK';
	$response['enum'] = $emps->enum[$code];
}else{
	$response['code'] = 'Error';
	$response['message'] = "No such enum!";
}

echo json_encode($response);


?>