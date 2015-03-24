<?php

$emps->no_smarty = true;

$last_purge = intval($emps->get_setting("_last_session_purge"));

if($last_purge < (time() - 30*60)){
	$emps->save_setting("_last_session_purge", time());
	
	$dt = time() - 30*24*60*60;
	$emps->db->query("delete from ".TP."e_sessions where dt < $dt");
	$emps->db->query("delete from ".TP."e_php_sessions where dt < $dt");	
	
	$dt = time() - 60*15;	
	$emps->db->query("delete from ".TP."e_php_sessions where (dt = cdt and dt < $dt) or sess_id = ''");		
}

?>