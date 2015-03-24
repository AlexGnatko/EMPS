<?php

header("Content-Type: text/css; charset=utf-8");

$emps->no_smarty = true;

echo file_get_contents($emps->plain_file("/fonts/fonts.css"));
echo file_get_contents($emps->plain_file("/css/bootstrap.min.css"));
echo file_get_contents($emps->plain_file("/css/default.css"));
echo file_get_contents($emps->plain_file("/css/editor.css"));

?>