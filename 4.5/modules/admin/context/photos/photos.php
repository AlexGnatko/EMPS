<?php

if ($emps->auth->credentials("admin")) {
    $emps->page_property("vuejs", 1);
    $emps->page_property("vue_debug", 1);
    $emps->page_property("sortable_vue", 1);
    $emps->page_property("toastr", 1);

    $context_id = intval($key);
    $smarty->assign("context_id", $context_id);

    require_once $emps->page_file_name("_comp/photos", "controller");

} else {
    $emps->deny_access("AdminNeeded");
}