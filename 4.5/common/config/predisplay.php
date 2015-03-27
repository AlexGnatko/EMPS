<?php
// CALLED FOR ALL PAGES (program modules if they have .htm templates, database content pages) right before the page is displayed
global $emps;

if(!$emps->enums_loaded){
	$emps->load_enums_from_file();
}

$emps->loadvars();
$start = "";
$URI = $emps->elink();

$emps->shadow_properties_link($URI);	

$file_name = $emps->common_module('config/project/predisplay.php');
if(file_exists($file_name)){
	require_once $file_name;
}
?>