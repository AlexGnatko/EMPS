<?php
$emps->no_smarty = true;
if ($key) {
    $key += 0;
    $row = $emps->db->get_row("comments", "id=$key");
    if (!$row) {
        echo "ERROR";
    } else {
        echo $key . "|" . $row['msg'];
    }
} else {
    echo "ERROR";
}
?>