<?php
$this->handle_view_row();

require_once $emps->common_module('files/blueimp/uploader.class.php');

$biup = new EMPS_BlueimpUploader;

if(!$this->can_save()){
    $biup->can_save = false;
}

if($this->context_id) {
    $biup->handle_request($this->context_id);
}

