<?php
$emps->no_smarty = true;
if ($emps->auth->credentials("users")) {
    $key = $emps->db->sql_escape($key);
    if ($emps->auth->taken_user($key)) {
        echo "yes";
    } else {
        echo "no";
    }
}
?>