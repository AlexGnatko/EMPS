<?php
header("Content-Type: text/plain; charset=utf-8");
$emps->no_smarty = true;

if ($emps->auth->credentials("admin")) {

    require_once $emps->common_module('config/cassandra/project.php');

    foreach ($tables as $table) {
        $emps->cas->ensure_structure($table);
    }

} else {
    echo "No access!";
}
