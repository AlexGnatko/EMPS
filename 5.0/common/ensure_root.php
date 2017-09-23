<?php

$emps->no_smarty = true;

$user = $emps->auth->load_user_by_num(1);

if($user){
	echo "User ".$user['username']." already exists!";
}else{
	$user = array();
	$user['user_id'] = 1;
	$user['username'] = 'root';
	$user['password'] = md5("empsrootpwd");
	$user['firstname'] = "Root";
	$user['lastname'] = "Admin";
	$user['email'] = "";
	$user['groups'] = array("admin", "oper", "root");
	$user['status'] = 10;
	
	$params = array();
	$params['doc'] = $user;
	$emps->db->insert("emps_users", $params);

}

//$user = $emps->get_row("emps_users", array('user_id' => 1));
