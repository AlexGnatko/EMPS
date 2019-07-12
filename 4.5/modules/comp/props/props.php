<?php

$emps->page_property("vuejs", 1);

$emps->p->no_idx = false;
$emps->p->no_full = false;

if($_GET['load_settings']){
    $response = [];
    $response['code'] = "OK";
    $lst = [];
    $emps->p->no_idx = true;

    $settings = $emps->p->read_properties([], $context_id);
    foreach($settings['_full'] as $n => $v){
        $a = [];
        $a['id'] = $v['id'];
        $a['code'] = $v['code'];
        $a['type'] = $v['type'];
        if (!$a['type']) {
            $a['type'] = 't';
        }
        if($a['type'] == 'i'){
            $a['value'] = $v['v_int'];
        }
        if($a['type'] == 't'){
            $a['value'] = $v['v_text'];
        }
        if($a['type'] == 'c'){
            $a['value'] = $v['v_char'];
        }
        if($a['type'] == 'd'){
            $a['value'] = $v['v_data'];
        }
        if($a['type'] == 'f'){
            $a['value'] = $v['v_float'];
        }
        if($a['type'] == 'b'){
            $a['value'] = ($v['v_int'] != 0)?true:false;
        }
        $a['checked'] = false;
        $lst[] = $a;
    }
    $response['lst'] = $lst;

    $emps->json_response($response);
}

if($_POST['post_save_changes_settings']){
    $row = $_POST['row'];
    $id = intval($_POST['id']);
    if ($row['code']) {
        $emps->p->save_property($context_id, $row['code'], $row['type'], $row['value'], false, 0);
    }
    $response = [];
    $response['code'] = "OK";
    if (!$row['code']) {
        $response['message'] = "No code!";
    }
    $emps->json_response($response);
}

if($_POST['delete_settings_rows']){
    $id_list = $_POST['id_list'];
    $settings = $emps->p->read_properties([], $context_id);
    foreach($id_list as $id){
        foreach($settings['_full'] as $n => $v){
            if($v['id'] == $id){
                $emps->p->clear_property($context_id, $v['code']);
            }
        }
    }

    $response = [];
    $response['code'] = "OK";
    $emps->json_response($response);
}

if ($_POST['post_import']) {
    $data = json_decode($_POST['import_json'], true);
    if(!$data){
        // try to import text
        $x = explode("\n", $_POST['import_json']);
        foreach($x as $v){
            $xx = explode("=", $v, 2);
            $code = trim($xx[0]);
            $value = trim($xx[1]);
            if(!$code || !$value){
                continue;
            }
            $emps->p->save_property($context_id, $code, "t", $value, false, 0);
        }
    }else {
        foreach ($data as $ra) {
            $value = 'v_text';
            if ($ra['type'] == 'i') {
                $value = 'v_int';
            }
            if ($ra['type'] == 'c') {
                $value = 'v_char';
            }
            if (isset($ra['value'])) {
                $value = "value";
            }
            $emps->p->save_property($context_id, $ra['code'], $ra['type'], $ra[$value], false, 0);
        }
    }

    $response = [];
    $response['code'] = "OK";
    $emps->json_response($response);

}