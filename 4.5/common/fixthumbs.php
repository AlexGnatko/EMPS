<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $r = $emps->db->query("select * from ".TP."e_thumbs order by id asc");
    $errors = 0;
    $size = 0;
    $folders = [];
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
            $folder = $photos->up->pick_folder($ra['upload_id'], DT_IMAGE);
            if (!$folder) continue;
            if (!isset($folders[$folder])) {
                $folders[$folder] = true;
            }
            $tname = $photos->up->UPLOAD_PATH . $folder . "/thumb/" . $ra['upload_id'] . "_" . $ra['size'] . "_" . $ra['opts'] . ".dat";

            if (file_exists($tname)) {
                //unlink($tname);
                $size = filesize($tname);
            } else {
                echo "File {$tname} does not exist!\r\n";
                $emps->db->query("delete from ".TP."e_thumbs where id = {$ra['id']}");
            }
        }
    }
    foreach ($folders as $folder => $bool) {
        echo "Check folder: {$folder}\r\n";
        $path = $photos->up->UPLOAD_PATH . $folder . "/thumb/";
        $lst = scandir($path);
        foreach ($lst as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $filepath = $path.$file;
            if (is_dir($filepath)) {
                echo "Directory should not exist: {$filepath}\r\n";
                shell_exec('rm -rf ' . escapeshellarg($filepath));
            } else {
                $x = explode(".dat", $file, 2);
                $main_name = $x[0];
                $x = explode("_", $main_name);
                $has_error = false;
                if (strval(intval($x[0])) != $x[0]) {
                    echo "{$main_name} - X0 should be a string!\r\n";
                    $has_error = true;
                }
                if ($x[1] != $photos->sanitize_size($x[1])) {
                    echo "{$main_name} - X1 should be a size!\r\n";
                    $has_error = true;
                }
                if (count($x) > 2) {
                    if ($x[2] != $photos->sanitize_opts($x[2])) {
                        echo "{$main_name} - X2 should be an opts!\r\n";
                        $has_error = true;
                    }
                }
                if ($has_error) {
                    unlink($filepath);
                }
            }
        }
    }
    echo "Total errors: {$errors}\r\n";
    echo "Total file size: {$size}\r\n";
} else {
    $emps->deny_access("AdminNeeded");
}