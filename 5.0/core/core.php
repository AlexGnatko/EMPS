<?php

// core includes
require_once EMPS_COMMON_PATH_PREFIX."/core/proc.php";
require_once EMPS_PATH_PREFIX."/core/mdb.class.php";
require_once EMPS_PATH_PREFIX."/core/properties.class.php";

if($emps_custom_session_handler){
	require_once EMPS_PATH_PREFIX."/core/session_handler_mongodb.class.php";		
	
	$emps_session_handler = new EMPS_SessionHandler();
	session_set_save_handler($emps_session_handler, true);
}

if(!$emps->fast){
	require_once EMPS_PATH_PREFIX."/core/auth.class.php";
	require_once EMPS_COMMON_PATH_PREFIX."/core/smarty.php";
}
