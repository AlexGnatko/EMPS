<?php

$emps->no_smarty = true;

$last_modified = date("r", time() - 60 * 60 * 24 * 7);
$expires = date("r", time() + 60 * 60 * 24 * 7);

header("Content-Type: application/json; charset=utf-8");
header("Last-Modified: " . $last_modified);
header("Expires: " . $expires);
header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
header_remove("Pragma");

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
    if ($_GET['string']) {
        $ne = array();
        foreach ($enum as $v) {
            $v['code'] = strval($v['code']);
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

