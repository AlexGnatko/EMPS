<?php
/**
 * The autoloader function
 * 
 * The function looks for classes on the include_path, optionally with $emps_autoload_prefixes, such as "PayPal-PHP-SDK/lib/".
 */

if(!isset($emps_autoload_prefixes)){
	$emps_autoload_prefixes = array();
}

spl_autoload_register(function ($name) {
	global $emps_autoload_prefixes;
	$name = str_replace("\\", "/", $name);
	$fn = $name.".php";
	$f = stream_resolve_include_path($fn);
	if($f === false){
		reset($emps_autoload_prefixes);
		foreach($emps_autoload_prefixes as $prefix){
			$x = explode("|", $prefix);
			$xfn = $fn;
			if(isset($x[1])){
				$xfn = str_replace($x[1], '', $xfn);
			}
			$pfn = $x[0].$xfn;
			$f = stream_resolve_include_path($pfn);
			if($f !== false){
				require_once $f;
				return true;
			}
		}
		return false;
	}
	require_once $f;
	return true;
});

?>