<?php

// CALLED RIGHT AFTER THE URL IS PARSED - use for URL rewritting, etc.

global $emps;

$file_name = $emps->common_module('config/project/postparse.php');
if (file_exists($file_name)) {
    require_once $file_name;
}