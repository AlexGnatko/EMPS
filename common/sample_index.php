<?php

// THIS IS A SAMPLE index.php FILE FOR AN EMPS WEBSITE
// Rename to index.php and put to the website's document root
// Don't forget the .htaccess file or setup the webserver to rewrite non-file URLs to index.php

$emps_start_time = microtime(true);

// Just a suggestion. Could be turned off on a production server.
error_reporting(E_ERROR);
if ($_GET['debug']) {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Debug mode. Could be turned off in a production environment.
ini_set('display_errors', 1);

require_once "local/local.php";                        // local settings for configuration

require_once "EMPS/4.5/emps_bootstrap.php";            // The main logic of the index.php file

?>