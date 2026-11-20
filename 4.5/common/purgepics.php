<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    ob_end_clean();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $nonex = 0;

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
                $xx = explode("_", $x[0]);
                if ($xx[0] == "thumb") {
                    $upload_id = intval($xx[1]);
                } else {
                    $upload_id = intval($x[0]);
                }
                $upload = $emps->db->get_row("e_uploads", "id = {$upload_id}");
                if ($upload) {
                    echo "{$folder}/{$file} - exists\r\n";
                } else {
                    echo "Upload does not exist: {$upload_id} ({$folder}/{$file})\r\n";
                    $nonex += filesize($photos->up->UPLOAD_PATH . $folder . "/" . $file);
                    $thumb_path = $photos->up->UPLOAD_PATH . $folder . "/thumb/";
                    echo "Custom thumbnails @ {$thumb_path}:\r\n";
                    $cmd = sprintf(
                        'find %s -maxdepth 1 -name %s',
                        escapeshellarg($thumb_path),
                        escapeshellarg($upload_id . '_*')
                    );
                    $rv = shell_exec($cmd);
                    echo "RV:\r\n{$rv}\r\n";
                }
            }
            flush();
        }
    }

    echo "Non-existent uploads: {$nonex}\r\n";

} else {
    $emps->deny_access("AdminNeeded");
}