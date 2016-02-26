<?php
// The script that "boots" a PHP-CLI process to use all 
// the available EMPS tools without running into problems
// with the fact that the script is not run by a web server

define('EMPS_COMMON_PATH_PREFIX', 'EMPS/common');

date_default_timezone_set(EMPS_TZ);

$emps_include_path = ini_get('include_path');

$glue = PATH_SEPARATOR;

$emps_paths = array($emps_include_path, EMPS_SCRIPT_PATH);
$emps_extra_paths = explode(':', EMPS_INCLUDE_PATH); // that's why ":" here even on Windows
$emps_paths = array_merge($emps_paths, $emps_extra_paths);

$path = implode($glue, $emps_paths);
ini_set('include_path', $path);			

// Initialize data constants

// Local data constants
$emps_require_file = EMPS_SCRIPT_PATH."/modules/_common/config/data.php";
if(file_exists($emps_require_file)){
	require $emps_require_file;
}
require_once EMPS_PATH_PREFIX."/common/config/data.php";		// Common data constants. Not defined if already defined in the previous script

// The main script
require_once EMPS_PATH_PREFIX."/EMPS.php";						// EMPS Class

$emps_require_file = "modules/_common/config/customizer.php";

$emps = new EMPS();

require_once EMPS_PATH_PREFIX."/core/core.php";					// Core classes (some not included if $emps->fast is set)

// We're not a web-server script!
$emps->cli_mode = true;

$emps->initialize();	// initialization and automatic configuration

$emps->start_time = emps_microtime_float($emps_start_time);

$emps->post_init();	

$emps->pre_controller();

// do not exit!

?>