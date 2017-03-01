<?php

$emps->no_smarty = true;

header("Content-Type: application/json; charset=utf-8");

$code = $key;

$response = array();
if (isset($emps->enum[$code])) {
    $response['code'] = 'OK';
    $enum = $emps->enum[$code];
    if ($_GET['numeric']) {
        $ne = array();
        foreach ($enum as $v) {
            $v['code'] = intval($v['code']);
            $ne[] = $v;
        }
        $enum = $ne;
    }

    $response['enum'] = $enum;
} else {
    $response['code'] = 'Error';
    $response['message'] = "No such enum!";
}

echo json_encode($response);

