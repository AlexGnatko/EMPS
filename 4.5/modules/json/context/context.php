<?php

$emps->no_smarty = true;

if ($emps->auth->credentials("users")) {

    $x = explode("-", $key);

    $ref_type = intval($x[0]);
    $ref_sub = intval($x[1]);
    $ref_id = intval($x[2]);

    $response = [];
    $response['code'] = "Error";

    if ($ref_type <= 0) {
        $response['message'] = "ref_type is not positive!";
    } else {
        if ($ref_sub <= 0) {
            $response['message'] = "ref_sub is not positive!";
        } else {
            if ($ref_id <= 0) {
                $response['message'] = "ref_id is not positive!";
            } else {
                $context_id = $emps->p->get_context($ref_type, $ref_sub, $ref_id);

                $days = 365;

                $last_modified = date("r", time() - 60 * 60 * 24 * $days);
                $expires = date("r", time() + 60 * 60 * 24 * $days);

                header("Content-Type: application/json; charset=utf-8");
                header("Last-Modified: " . $last_modified);
                header("Expires: " . $expires);
                header("Cache-Control: max-age=" . (60 * 60 * 24 * $days));
                header_remove("Pragma");

                $response['code'] = "OK";
                $response['context_id'] = $context_id;
            }
        }
    }

} else {
    $response = [];
    $response['code'] = "Error";
    $response['message'] = "You have to be logged in!";
}


echo json_encode($response);
