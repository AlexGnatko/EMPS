<?php
if ($emps->auth->credentials('admin')):
    if ($emps->get_setting("admin_tools")){
        require_once $emps->page_file_name("_admin/" . $emps->get_setting("admin_tools") . "/set", "controller");
    }else{
        $emps->uses_flash();

        $context_id = $emps->website_ctx;

        require_once $emps->common_module('props/props.class.php');

        $props = new EMPS_PropertiesEditor;
        $props->handle_request($context_id);
    }
else:
    $emps->deny_access("AdminNeeded");
endif;

