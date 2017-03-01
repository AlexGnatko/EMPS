<?php
if ($emps->auth->credentials('admin')):
    $emps->uses_flash();

    $context_id = $emps->website_ctx;

    require_once $emps->common_module('props/props.class.php');

    $props = new EMPS_PropertiesEditor;
    $props->handle_request($context_id);
else:
    $emps->deny_access("AdminNeeded");
endif;

