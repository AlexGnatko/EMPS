<?php

require_once $emps->common_module('files/vue/uploader.class.php');

$uploader = new EMPS_VuePhotosUploader;
$uploader->context_id = $this->context_id;

$uploader->handle_request();
