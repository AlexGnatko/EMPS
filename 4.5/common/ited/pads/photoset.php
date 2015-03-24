<?php
require_once($emps->common_module('photoset/uploader.class.php'));

$photoset = new EMPS_PhotosetUploader;
$photoset->handle_request($this->context_id);
?>
