<?php
global $context_id;

require_once $emps->common_module('photos/vue/uploader.class.php');

$uploader = new EMPS_VuePhotosUploader;
$uploader->context_id = $context_id;

$smarty->assign("context_id", $uploader->context_id);

$uploader->handle_request();
