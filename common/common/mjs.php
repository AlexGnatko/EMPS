<?php

$emps->no_smarty = true;

$last_modified = date("r", time() - 60 * 60 * 24 * 7);
$expires = date("r", time() + 60 * 60 * 24 * 7);

header("Last-Modified: " . $last_modified);
header("Expires: " . $expires);
header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
header_remove("Pragma");

$part = str_replace("-", "/", $key);
$file = str_replace("..", "", $start);

$x = explode(".", $file);
$ext = array_pop($x);

if ($ext == "css") {
    header("Content-Type: text/css");
}

if ($ext == "js") {
    header("Content-Type: application/javascript; charset=utf-8");
}

if ($ext == "vue") {
    header("Content-Type: text/html; charset=utf-8");
}

if ($ext == "php") {
    $emps->not_found();
    exit;
}
if ($ext == "htm") {
    $emps->not_found();
    exit;
}

$page = "_{$part},{$file}";

$file_name = $emps->page_file_name($page, "inc");
if(!$file_name){
    $emps->not_found();
    exit;
}

if ($ext == "vue") {
    $emps->pre_display();
    $page = "_{$part},!{$file}";
    $smarty->display("db:{$page}");
} else {
    $fh = fopen($file_name, "rb");
    if($fh){
        fpassthru($fh);
        fclose($fh);
    }
}

