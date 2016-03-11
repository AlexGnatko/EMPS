<?php

require_once EMPS_COMMON_PATH_PREFIX."/core/auth.class.php";

class EMPS_Auth extends EMPS_Auth_Common {
	public $USER__ID;
	
	public function ensure_fullname($user){
		global $emps;
		
		$ofullname = $user['fullname'];
		$user = $this->form_fullname($user);
		if($ofullname != $user['fullname']){
			$update = array("fullname" => $user['fullname']);
			$params = array();
			$params['query'] = array('_id' => $user['_id']);
			$params['update'] = array('$set' => $update);
			
			$emps->db->update_one("emps_users", $params);
		}
		return $user;
	}

	
	public function create_session($username, $password, $mode){
		global $SET, $emps;
		
		$user = $this->load_user_by_username($username);
		if(!$user){
			$this->login_error("no_user");
			return false;
		}
		
		$user = $this->ensure_fullname($user);

		if(!$mode){
			if($user['password'] != md5($password)){
				$this->login_error("wrong_password");
				return false;
			}
		}

		if($user['status'] == 0){
			$this->login_error("no_activation");
			return false;
		}
		
		$user__id = $user['_id'];
		
		$user_session = array();
		$user_session['user__id'] = $user__id;
		$user_session['user_id'] = $user['user_id'];
		$user_session['ip'] = $_SERVER['REMOTE_ADDR'];
		$user_session['dt'] = time();
		$params = array();
		$params['doc'] = $user_session;
		$emps->db->insert("emps_user_sessions", $params);
	
		$_SESSION['session_id'] = $emps->db->oid_string($emps->db->last_id);
	
		return true;
	}
	
	function check_session(){
		global $emps;
		$ssid = "";
		if(isset($_SESSION['session_id'])){
			$ssid = $emps->db->oid($_SESSION['session_id']);
		}
		
		if(!$ssid){
			return false;
		}
		
		$session = $emps->db->get_row("emps_user_sessions", array('query' => array('_id' => $ssid)));
		if(!$session){
			unset($this->USER_ID);
			unset($this->USER__ID);
			unset($_SESSION['session_id']);
			return false;
		}else{
			if($session['dt'] < (time() - 10*60)){
				// update every 10 minutes
				$update = array("dt" => time());
				$params = array();
				$params['query'] = array('_id' => $session['_id']);
				$params['update'] = array('$set' => $update);
				
				$emps->db->update_one("emps_user_sessions", $params);
			}
		}
		
		$this->USER_ID = $session['user_id'];
		$this->USER__ID = $session['user__id'];
		
		$this->login = $_SESSION['login'];
	
		$user = $this->load_user($this->USER__ID);
		$this->login['user'] = $user;
	
		$this->AUTH_R = 1;
		return true;
	}
	
	public function close_session(){
		global $emps;
		if(!isset($_SESSION['session_id'])){
			return false;
		}
		$ssid = $emps->db->oid($_SESSION['session_id']);
		if(!$ssid){
			return false;
		}
	
		$emps->db->delete_one("emps_user_sessions", array("query" => array("_id" => $ssid)));
	
		unset($this->USER_ID);
		unset($this->USER__ID);
		unset($_SESSION['session_id']);
	
		$this->AUTH_R = -4;
	}
	
	function user_credentials($user__id, $lst){
		global $emps;
	
		if(!$user__id){
			return false;
		}
		if(!$lst){
			return false;
		}

		$user = $this->load_user($user__id);		
		$p = explode(",", $lst);
		if($lst == "users"){
			if($user['status'] == 10){
				return true;
			}
		}
		
		if($emps->website_ctx == $emps->default_ctx){
			$groups_id = 'groups';
		}else{
			$groups_id = 'groups_'.$emps->db->oid($emps->website_ctx);
		}
		
		foreach($p as $n => $v){
			$v = trim($v);
			if(!is_object($user[$groups_id])){
				return false;
			}
			foreach($user[$groups_id] as $group){
				if($group == $v){
					return true;
				}
			}
		}
		return false;
	}
	
	public function credentials($groups){
		return $this->user_credentials($this->USER__ID, $groups);
	}
	
	public function load_user($user__id){
		global $emps;
		
		$user = $emps->db->get_row("emps_users", array('query' => array('_id' => $user__id)));
		return $user;
	}
	
	public function load_user_by_num($user_id){
		global $emps;
		
		$user = $emps->db->get_row("emps_users", array('query' => array('user_id' => $user_id)));
		return $user;
	}
	
	public function load_user_by_username($username){
		global $emps;
		
		$user = $emps->db->get_row("emps_users", array('query' => array('username' => $username)));
		return $user;
	}
}

?>