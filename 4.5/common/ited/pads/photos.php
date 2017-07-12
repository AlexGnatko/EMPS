<?php

$this->handle_view_row();

require_once $emps->common_module('photos/blueimp/uploader.class.php');

$biup = new EMPS_BlueimpUploader;



if($_GET['export']){
	$smarty->assign("ExportMode", 1);
	
	$txt = "";
	$lst = $biup->p->list_pics($this->context_id, 100000);
	foreach($lst as $pic){
		if($txt != ""){
			$txt .= "\r\n";
		}
		$txt .= EMPS_SCRIPT_WEB."/pic/".$pic['md5'].".".$pic['ext'];
	}
	
	$smarty->assign("txt", $txt);
	
}else{

	$emps->page_property("blueimp_uploader",1);
	
	require_once($emps->common_module('photos/blueimp/uploader.class.php'));
	
	$biup = new EMPS_BlueimpUploader;
	
	if(!$this->can_save()){
		$biup->can_save = false;
	}
	
	$biup->handle_request($this->context_id);
}

