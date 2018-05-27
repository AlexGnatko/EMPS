<?php

header("Content-Type: text/plain; charset=utf-8");

$emps->no_smarty = true;

echo "Creating MongoDB Indexes:\r\n";

if ($key) {
    $name = "_" . $key . "/mdbindex,module";
    $file_name = $emps->page_file_name($name, 'controller');
//    echo $name;
//    echo $file_name;
    if(file_exists($file_name)){
        require_once $file_name;
    }

} else {
    $name = $emps->common_module('config/mdbindex/emps.php');
    if($name){
        require_once $file_name;
    }
}
