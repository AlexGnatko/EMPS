<?php
$this->handle_view_row();

require_once($emps->common_module('videos/uploader.class.php'));

$videos = new EMPS_VideoUploader;

if(!$this->can_save()){
	$videos->can_save = false;
}

$videos->handle_request($this->context_id);
?>
