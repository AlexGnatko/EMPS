<?php

$this->handle_view_row();

require_once $emps->common_module('props/props.class.php');

$props = new EMPS_PropertiesEditor;
$props->handle_request($this->context_id);

