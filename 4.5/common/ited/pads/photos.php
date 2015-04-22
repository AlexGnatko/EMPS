<?php

$this->handle_view_row();

$emps->page_property("blueimp_uploader",1);

require_once($emps->common_module('photos/blueimp/uploader.class.php'));

$biup = new EMPS_BlueimpUploader;

if(!$this->can_save()){
	$biup->can_save = false;
}

$biup->handle_request($this->context_id);

?>
