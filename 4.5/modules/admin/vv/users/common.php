<?php

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;

$ited->add_pad_template("admin/vv/users/pads,%s");

$ited->new_row_fields = ['status' => 1];

$search_text = $_SESSION[$ited->table_name . "_search"];

$ited->where = " where 1=1 ";

if ($search_text) {
    $ps = "%" . $search_text . "%";

    $ited->where .= " and (
    t.username like '{$ps}' 
    or t.fullname like '{$ps}'
    ) ";
}
