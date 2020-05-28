<?php
global $context_id;

require_once $emps->common_module('videos/vue/processor.class.php');

$uploader = new EMPS_VideoProcessor();
$uploader->context_id = $context_id;

$uploader->handle_request();

