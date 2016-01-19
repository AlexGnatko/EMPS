<?php

$emps->no_smarty = true;

$hours = $emps->get_setting("smarty_clear_hours");

if(!$hours){
	$hours = 12;
}

$smarty->clearCompiledTemplate(null, null, $hours * 60 * 60);

echo "Cleared!";
	
?>