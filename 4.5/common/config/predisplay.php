<?php
// CALLED FOR ALL PAGES (program modules if they have .htm templates, database content pages) right before the page is displayed
global $emps;

if (!$emps->enums_loaded) {
    $emps->load_enums_from_file();
}

if ($emps->virtual_path) {
    $emps->shadow_properties_link($emps->virtual_path['uri']);
    $page_data = $emps->get_content_data($emps->virtual_path);
    $emps->page_property("context_id", $page_data['context_id']);
} else {
    $emps->loadvars();
    $start = "";
    $URI = $emps->elink();

    $emps->shadow_properties_link($URI);
}

$file_name = $emps->common_module('config/project/predisplay.php');
if (file_exists($file_name)) {
    require_once $file_name;
}

$emps->page_properties_from_settings("css_reset,use_bower,defer_all,css_fw");
