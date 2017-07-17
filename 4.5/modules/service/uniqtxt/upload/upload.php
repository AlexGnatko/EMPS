<?php

$emps->no_smarty = true;

header("Content-Type: text/plain; charset=utf-8");

$id = intval($key);

require_once $emps->common_module('uniqtxt/uniqtxt.class.php');
$utxt = new EMPS_UniqueTexts;

$urow = $emps->db->get_row("e_unique_texts", "id = ".$id);
if($urow){

    echo "TESTING ".time();

    $output = ob_get_clean();

    $update = [];
    $update['SET'] = ['upload_log' => $output];
    $emps->db->sql_update_row("e_unique_texts", $update, "id = ".$id);
}else{
    echo "Not found!";
}

