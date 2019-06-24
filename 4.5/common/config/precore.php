<?php
global $emps;

$file_name = $emps->common_module('config/project/precore.php');
if (file_exists($file_name)) {
    require_once $file_name;
}