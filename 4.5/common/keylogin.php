<?php
$emps->no_smarty=true;

$row=$emps->db->get_row("e_actkeys","pin='$key'");
if($row){
	$emps->db->query("delete from ".TP."e_actkeys where pin='$key'");
	$ra=$emps->db->get_row("e_users","id=".$row['user_id']);
	if($ra['status']!=1){
		$emps->redirect_page("/badkey/");
	}else{
		$r=$emps->auth->create_session($ra['username'],"",1);
		if(!$r){
			$emps->redirect_page("/badkey/");
		}else{
			$emps->auth->clear_activations($row['user_id']);
			$emps->redirect_page("/profile/");
		}
	}
}else{
	$emps->redirect_page("/badkey/");
}


?>