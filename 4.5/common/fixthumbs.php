<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $r = $emps->db->query("select * from ".TP."e_thumbs order by id asc");
    $errors = 0;
    $size = 0;
    while ($ra = $emps->db->fetch_named($r)) {
        $s_size = $photos->sanitize_size($ra['size']);
        $s_opts = $photos->sanitize_opts($ra['opts']);
        $has_error = false;
        if ($ra['size'] != $s_size) {
            echo "({$ra['id']}) Wrong size: {$ra['size']}\r\n";
            $errors++;
            $has_error = true;
        }
        if ($ra['opts'] != $s_opts) {
            echo "({$ra['id']}) Wrong opts: {$ra['opts']}\r\n";
            $errors++;
            $has_error = true;
        }
        if ($has_error) {
            $folder = $this->up->pick_folder($ra['upload_id'], DT_IMAGE);
            if (!$folder) continue;
            $tname = $this->up->UPLOAD_PATH . $folder . "/thumb/" . $ra['upload_id'] . "_" . $ra['size'] . "_" . $ra['opts'] . ".dat";
            if (file_exists($tname)) {
                //unlink($tname);
                $size = filesize($tname);
            } else {
                echo "File {$tname} does not exist!\r\n";
            }
        }
    }
    echo "Total errors: {$errors}\r\n";
    echo "Total file size: {$size}\r\n";
} else {
    $emps->deny_access("AdminNeeded");
}