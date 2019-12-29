<?php
// CALLED FOR ALL PAGES (program modules if they have .htm templates, database content pages) right before the page is displayed
global $emps;

$file_name = $emps->common_module('config/project/predisplay.php');
if (file_exists($file_name)) {
    require_once $file_name;
}

$emps->page_properties_from_settings("css_reset,use_bower,defer_all,css_fw");
