<?php

/*
 * Common requests handler (before $vted->handle_request())
 */

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;

$ited->add_pad_template("admin/vv/content/pads,%s");

$ited->new_row_fields = ['context_id' => $ited->website_ctx];

if ($_POST['post_import']) {
    $lst = json_decode($_POST['content_json'], true);
    foreach ($lst as $v) {
        $uri = $emps->db->sql_escape($v['uri']);
        $row = $emps->db->get_row("e_content", "uri = '" . $uri . "' and context_id = " . $emps->website_ctx);
        $nr = $v;
        $nr['context_id'] = $emps->website_ctx;
        if ($row) {
            $emps->db->sql_update_row("e_content", ['SET' => $nr], "id = " . $row['id']);
            $id = $row['id'];
        } else {
            $emps->db->sql_insert_row("e_content", ['SET' => $nr]);
            $id = $emps->db->last_insert();
        }
        $context_id = $emps->p->get_context($ited->ref_type, $ited->ref_sub, $id);
        $emps->p->save_properties($v, $context_id, $ited->track_props);

        $ited->v->p->import_photos($context_id, $v['pics']);
    }
    $response = [];
    $response['code'] = "OK";
    $emps->json_response($response); exit;
}