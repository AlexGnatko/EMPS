<?php
if($emps->auth->credentials("admin")):
	include($emps->common_module('ted/ted.class.php'));
	
	class EMPS_Messages extends EMPS_TableEditor {
		public $table_name = 'e_messages';
		public $tord = " order by id desc ";
		
		public function handle_row($ra){
			global $emps;
			$ra['time']=$emps->form_time($ra['dt']);
			return $ra;
		}
	}
	
	$ted = new EMPS_Messages;
	
	$ted->handle_request();
else:
	$emps->deny_access("AdminNeeded");
endif;
?>