<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $folders = scandir($photos->up->UPLOAD_PATH);
    foreach ($folders as $folder) {
        if ($folder == "." || $folder == "..") {
            continue;
        }
        $files = scandir($photos->up->UPLOAD_PATH . $folder);
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $x = explode(".dat", $file, 2);
            $main_name = $x[0];
            $x = explode("-", $main_name);
            if ($x[1] == "img") {
                $upload_id = intval($x[0]);
                $upload = $emps->db->get_row("e_uploads", "id = {$upload_id}");
                if ($upload) {
                    echo "{$folder}{$file} - exists\r\n";
                } else {
                    echo "Upload does not exist: {$upload_id} ({$folder})\r\n";
                }
            }
        }
    }

} else {
    $emps->deny_access("AdminNeeded");
}