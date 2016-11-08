<?php
// CALLED FOR ALL PAGES (program modules if they have .htm templates, database content pages) right before the page is displayed
global $emps;

if(!$emps->enums_loaded){
	$emps->load_enums_from_file();
}

if($emps->virtual_path){
	$emps->shadow_properties_link($emps->virtual_path['uri']);
}else{
	$emps->loadvars();
	$start = "";
	$URI = $emps->elink();
	
	$emps->shadow_properties_link($URI);	
}

$file_name = $emps->common_module('config/project/predisplay.php');
if(file_exists($file_name)){
	require_once $file_name;
}

$css_reset = $emps->get_setting("css_reset");
if($css_reset){
	$emps->page_property("css_reset", $css_reset);
}

$use_bower = $emps->get_setting("use_bower");
if($use_bower){
	$emps->page_property("use_bower", $use_bower);
}
?>