<?php

if ($emps->auth->credentials("admin")) {
    $emps->plaintext_response();

    require_once $emps->common_module('photos/photos.class.php');
    $photos = new EMPS_Photos();

    $r = $emps->db->query("select * from ".TP."e_thumbs order by id asc");
    $errors = 0;
    while ($ra = $emps->db->fetch_named($r)) {
        $s_size = $photos->sanitize_size($ra['size']);
        $s_opts = $photos->sanitize_opts($ra['opts']);
        if ($ra['size'] != $s_size) {
            echo "({$ra['id']}) Wrong size: {$ra['size']}\r\n";
            $errors++;
        }
        if ($ra['opts'] != $s_opts) {
            echo "({$ra['id']}) Wrong opts: {$ra['opts']}\r\n";
            $errors++;
        }
    }
    echo "Total erros: {$errors}\r\n";
} else {
    $emps->deny_access("AdminNeeded");
}