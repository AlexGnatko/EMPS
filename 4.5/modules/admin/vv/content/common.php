<?php

/*
 * Common requests handler (before $vted->handle_request())
 */

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;

$ited->add_pad_template("admin/vv/content/pads,%s");

$ited->new_row_fields = ['context_id' => $ited->website_ctx];