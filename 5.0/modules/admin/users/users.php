<?php
if($emps->auth->credentials("root")):
	include($emps->common_module('ted/ted.class.php'));
	
	class EMPS_Users extends EMPS_TableEditor {
		public $table_name = 'emps_users';
		public $id_field = "user_id";
		
		public function handle_row($ra){
			global $emps;
			
			$ra = parent::handle_row($ra);
			
			$ra['id'] = $ra['user_id'];
			
			if($emps->website_ctx == $emps->default_ctx){
				$groups = $emps->db->get_array($ra['groups']);
			}else{
				$groups = $emps->db->get_array($ra['groups_'.$emps->db->oid($emps->website_ctx)]);
			}

			$glst = implode(", ", $groups);
			$ra['grp'] = $glst;
			
			return $ra;
		}
		
		public function post_save($id){
			global $emps;
			parent::post_save($id);
			
			$user = $emps->auth->load_user($id);
			$emps->auth->ensure_fullname($user);
		}
		
		public function pre_save($id){
		    global $emps;

			$x = explode(",", $_POST['grp']);
			$a = array();
			foreach($x as $v){
				$v = trim($v);
				if(!$v){
					continue;
				}
				$a[] = $v;
			}

			if($emps->website_ctx == $emps->default_ctx){
				$fn = "groups";
			}else{
				$fn = "groups_".$emps->db->oid_string($emps->website_ctx);
			}
			
			$_POST[$fn] = $a;
			unset($_POST['grp']);
		}
		
		public function handle_kill($id){
			global $emps;
		}
		
		public function handle_input($ra){
			global $emps;
			return $this->handle_row($ra);
		}
		
		public function handle_post(){
			$_POST['status'] = 10;		
			
			if($_POST['password']){
				$_POST['password'] = md5($_POST['password']);
			}
			parent::handle_post();			
		}
	}
	
	if(!$_POST['password']){
		unset($_POST['password']);
	}
	
	$ted = new EMPS_Users;

	$ted->handle_request();
else:
	$emps->deny_access("AdminNeeded");
endif;

