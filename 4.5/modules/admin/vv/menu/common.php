<?php

/*
 * Common requests handler (before $vted->handle_request())
 */

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;

if ($_GET['load_filter']) {
    $r = $emps->db->query("select grp from ".TP."e_menu where context_id = {$ited->website_ctx} group by grp order by grp asc");
    $lst = [];
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[] = $ra;
    }
    $response = [];
    $response['code'] = "OK";
    $response['lst'] = $lst;
    $emps->json_response($response); exit;
}

if ($sk) {
    $sk = $emps->db->sql_escape($sk);
    if (!$ited->where) {
        $ited->where = " where 1=1 ";
    }
    $ited->where .= " and t.grp = '{$sk}' ";
}

$emps->loadvars();
if ($_GET['export_menu']) {
    $parent = intval($sd);
    if ($sk == '00') {
        $sk = '';
    }
    $code = $sk;
    if ($code) {
        $menu = $emps->section_menu($code, $parent);
        $data = json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response = [];
        $response['code'] = "OK";
        $response['menu_json'] = $data;
        $emps->json_response($response); exit;
    }
}

function import_menu(&$lst, $code, $parent)
{
    global $emps, $ited;

    foreach ($lst as $v) {
        $v['name'] = $v['dname'];
        $v['uri'] = $v['link'];
        $v['enabled'] = 1;
        unset($v['parent']);
        unset($v['id']);

        $v['parent'] = $parent;

        $uri = $emps->db->sql_escape($v['link']);
        $row = $emps->db->get_row("e_menu", "uri = '{$uri}' and context_id = {$emps->website_ctx}");
        $update = array();
        $update['SET'] = $v;
        $update['SET']['context_id'] = $emps->website_ctx;
        if ($row) {
            $emps->db->sql_update_row("e_menu", $update, "id = " . $row['id']);
            $id = $row['id'];
        } else {
            $emps->db->sql_insert_row("e_menu", $update);
            $id = $emps->db->last_insert();
//            error_log("Inserted");
        }
//        usleep(10000);
        $context_id = $emps->p->get_context($ited->ref_type, 1, $id);
//        error_log("Name/uri: {$v['name']}/{$v['uri']}, id: {$id}, context_id: {$context_id}");
        $emps->p->save_properties($v, $context_id, $ited->track_props);
        if (count($v['sub']) > 0) {
            $sls = $v['sub'];
            import_menu($sls, $code, $id);
        }
    }
}


if ($_POST['post_import']) {
    $lst = json_decode($_POST['menu_json'], true);
    import_menu($lst, false, 0);
    $response = [];
    $response['code'] = "OK";
    $emps->json_response($response); exit;
}

$ited->add_pad_template("admin/vv/menu/pads,%s");

$ited->new_row_fields = ['context_id' => $ited->website_ctx];