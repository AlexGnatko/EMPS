<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    ob_end_clean();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $nonex = 0;
    $to_delete = [];

    $folders = scandir($photos->up->UPLOAD_PATH);
    foreach ($folders as $folder) {
        if ($folder == "." || $folder == "..") {
            continue;
        }
        $missing_uploads = [];
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
                    $missing_uploads["idx_{$upload_id}"] = $upload_id;
                    $to_delete[] = $photos->up->UPLOAD_PATH . $folder . "/" . $file;
                }
            }
            flush();
        }

        $thumb_path = $photos->up->UPLOAD_PATH . $folder . "/thumb/";
        echo "Custom thumbnails @ {$thumb_path}:\r\n";

        $files = scandir($thumb_path);
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $subfile = $file;
            $x = explode("_", $subfile);
            if (isset($missing_uploads["idx_".intval($x[0])])) {
                $size = filesize($thumb_path . $subfile);
                echo "+ {$subfile} ({$size})\r\n";
                $nonex += $size;
                $to_delete[] = $thumb_path . $subfile;
            }

        }
    }

    echo "Non-existent uploads: {$nonex}\r\n";
    if ($_GET['apply'] ?? false) {
        foreach ($to_delete as $file) {
            unlink($file);
            echo "Deleted {$file}\r\n";
        }
    }

} else {
    $emps->deny_access("AdminNeeded");
}