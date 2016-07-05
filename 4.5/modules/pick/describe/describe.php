<?php
$emps->no_smarty=true;

$id = $_GET['id']+0;
$type = $_GET['selector_type'];

$x = explode('|',$type,2);
$type = $x[0];
$extra = $x[1];

$object = $emps->db->get_row($type,"id=".$id);

require_once $emps->common_module('objsel/objsel.class.php');
require_once $emps->common_module('tables/tables.class.php');

$objsel = new EMPS_ObjectSelector();

$tables = new EMPS_Tables();

if($object){
	$object['extra'] = "";
	if($type == 'e_users'){
		$object['name'] = $object['username'].' - '.$object['fullname'];
		$object['link'] = "/admin-siteusers/".$object['id']."/-/info/";
	}
	if($object['parent']){
		$object['name'] .= $tables->text_parents($type, $object['parent']);
	}
	$fn = $emps->page_file_name('_pick/describe,descr_modifier', 'controller');
	if(file_exists($fn)){
		require_once $fn;
	}
	echo $objsel->serialize_nv($object);
}else{
	echo "ERROR";
}
?>