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


?>