<?php
$this->handle_view_row();

$emps->page_property("blueimp_uploader",1);

require_once($emps->common_module('files/blueimp/uploader.class.php'));

$biup = new EMPS_BlueimpUploader;
$biup->handle_request($this->context_id);

?>
