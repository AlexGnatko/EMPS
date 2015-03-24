<?php
if($emps->auth->credentials("admin")):
	include($emps->common_module('ted/ted.class.php'));
	

	class EMPS_Redirects extends EMPS_TableEditor {
		public $table_name = 'e_redirect';
		
		public function handle_row($ra){
			global $emps;
			
			$ra['time']=$emps->form_time($ra['dt']);
			
			return $ra;
		}
	}
	
	$ted = new EMPS_Redirects;
	
	$ted->handle_request();
else:
	$emps->deny_access("AdminNeeded");
endif;

?>