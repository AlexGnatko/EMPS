<?php
/**
 * Initialization Script
 *
 * The script that "boots" the website. Checks what the user wants and calls the appropriate entry points in the EMPS Class.
 */

define('EMPS_PATH_PREFIX', 'EMPS/5.0');

// Initialize data constants

// Local data constants
$emps_require_file = EMPS_SCRIPT_PATH."/modules/_common/config/data.php";
if(file_exists($emps_require_file)){
	require $emps_require_file;
}
require_once EMPS_PATH_PREFIX."/common/config/data.php";		// Common data constants. Not defined if already defined in the previous script

require_once "EMPS/common/emps_bootstrap.php";
