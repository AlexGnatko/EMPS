<?php

class EMPS_SMS {
	public $account_sid;
	public $auth_token;
	
	public function __construct(){
		$this->account_sid = TWILIO_SID; 
		$this->auth_token = TWILIO_TOKEN; 
	}
	
	public function enqueue_message($to, $msg){
		global $emps, $SET;
		
		if(!trim($to)){
			return false;
		}
		
		$params = array();
		$params['account_sid'] = $this->account_sid;
		$params['auth_token'] = $this->auth_token;
		
		$SET = array();
		$SET['to'] = $to;
		$SET['message'] = $msg;
		$SET['params'] = json_encode($params);
		$emps->db->sql_insert("e_smscache");
		return true;
	}
	
	public function send_message($to, $msg){
		$rv = true;
		try {
			$client = new Services_Twilio($this->account_sid, $this->auth_token); 
			$client->account->messages->create(array( 
				'To' => $to, 
				'From' => TWILIO_FROM, 
				'Body' => $msg,  
			));		
		}catch(Exception $e){
			$error_message = $e->getMessage();
			echo $error_message."<br/>";
			$rv = false;
		};
		
		unset($client);
		
		return $rv;
	}
}