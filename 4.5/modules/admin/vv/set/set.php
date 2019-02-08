<?php

if ($emps->auth->credentials('admin')):

    $context_id = $emps->website_ctx;

    require_once $emps->page_file_name("_comp/props", "controller");

else:

    $emps->deny_access("UserNeeded");

endif;
