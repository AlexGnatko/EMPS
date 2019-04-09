<?php

require_once $emps->common_module('files/vue/uploader.class.php');

$uploader = new EMPS_VuePhotosUploader;
$uploader->context_id = intval($key);

$context = $emps->p->load_context($uploader->context_id);

if ($emps->auth->credentials("users")) {
    if (in_array($context['ref_type'], [DT_WEBSITE, DT_CONTENT, DT_MENU, DT_VIDEO], false)) {
        if ($emps->auth->credentials("admin")) {
            $uploader->can_save = false;
        }
    }
} else {
    $response = [];
    $response['code'] = "Error";
    $response['message'] = "No access!";
    $emps->json_response($response); exit;
}

require_once $emps->page_file_name('_json/upload,project','controller');

$uploader->handle_request();
