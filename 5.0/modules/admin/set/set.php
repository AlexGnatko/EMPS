<?php
if($emps->auth->credentials('admin')):
	$emps->uses_flash();

	$context_id=$emps->website_ctx;
	
	require_once($emps->common_module('props/props.class.php'));
	
	$props = new EMPS_PropertiesEditor;
	$props->skip = "type,subtype,numeric_id,ref_id";
	$props->table_name = "emps_contexts";
	$props->ref_id = $emps->website_ctx;
	
	$props->handle_request();

else:
	$emps->deny_access("AdminNeeded");
endif;

