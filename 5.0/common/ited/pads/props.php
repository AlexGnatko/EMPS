<?php

$this->handle_view_row();

require_once $emps->common_module('props/props.class.php');

$props = new EMPS_PropertiesEditor;
$props->table_name = $this->table_name;
$props->ref_id = $this->ref_id;

$props->handle_request();

?>