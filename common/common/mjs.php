<?php

$emps->no_smarty = true;

header("Last-Modified: ", date("r", $ra['dt']));
header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));

$part = str_replace("-", "/", $key);
$file = str_replace("..", "", $start);

$page = "_{$part},{$file}";

$file_name = $emps->page_file_name($page, "inc");
if(!$file_name){
    $emps->not_found();
    exit;
}

$fh = fopen($file_name, "rb");
if($fh){
    fpassthru($fh);
    fclose($fh);
}
