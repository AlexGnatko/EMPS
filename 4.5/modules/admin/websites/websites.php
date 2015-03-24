<?php
if($emps->auth->credentials("root")):
	$emps->page_property("ited",1);
	
	include($emps->common_module('ted/ted.class.php'));
	
	class EMPS_Websites extends EMPS_TableEditor {
		public $table_name = 'e_websites';
	}
	
	$ted = new EMPS_Websites;

	$ted->handle_request();
else:
	$emps->deny_access("AdminNeeded");
endif;

?>