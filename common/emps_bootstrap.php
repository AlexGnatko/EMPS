<?php
/**
 * Initialization Script
 *
 * This procedure is common for all EMPS versions.
 */

define('EMPS_COMMON_PATH_PREFIX', 'EMPS/common');
// EMPS_PATH_PREFIX is set in the current version's emps_bootstrap.php file

date_default_timezone_set(EMPS_TZ);

// EMPS Autoloader
require_once EMPS_COMMON_PATH_PREFIX . "/emps_autoloader.php";
// Composer Autoloader
require_once "EMPS/vendor/autoload.php";

if ($emps_force_hostname) {
    if ($_SERVER['HTTP_HOST'] != EMPS_HOST_NAME) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: http://" . EMPS_HOST_NAME . $_SERVER['REQUEST_URI']);
        //exit();
    }
}

$emps_include_path = ini_get('include_path');

$glue = PATH_SEPARATOR;

$emps_paths = array($emps_include_path, EMPS_SCRIPT_PATH);
$emps_extra_paths = explode(':', EMPS_INCLUDE_PATH); // that's why ":" here even on Windows
$emps_paths = array_merge($emps_paths, $emps_extra_paths);

$path = implode($glue, $emps_paths);
ini_set('include_path', $path);

// Send the file if the user wants a file
require_once EMPS_COMMON_PATH_PREFIX . "/emps_sendfile.php";        // No further execution of the main script will be needed if this script does the job

// A cookie test - this will let us know if the browser supports cookies
$emps_just_set_cookie = false;
if (!isset($_COOKIE['EMPS'])) {
    $emps_just_set_cookie = true;
    setcookie("EMPS", time(), time() + 60 * 60 * 24 * 30, '/');
}

// The main script
require_once EMPS_PATH_PREFIX . "/EMPS.php";                        // EMPS Class

$emps = new EMPS();
$emps->check_fast();

if ($emps_force_protocol && !$_GET['nohttps']) {
    $emps->ensure_protocol($emps_force_protocol);
}

$fn = $emps->common_module('config/precore.php');
if ($fn) {
    require_once $fn;
}

require_once EMPS_PATH_PREFIX . "/core/core.php";                    // Core classes (some not included if $emps->fast is set)

mb_internal_encoding('utf-8');
date_default_timezone_set(EMPS_TZ);

ini_set("session.cookie_lifetime", EMPS_SESSION_COOKIE_LIFETIME);
ini_set("session.cookie_path", "/");
ini_set("session.use_cookies", "1");
ini_set("session.use_only_cookies", "1");
ini_set("magic_quotes_runtime", "0");

$emps_bots = array(
    'YandexBot',
    'SputnikBot',
    'YandexMetrika',
    'Yahoo! Slurp',
    'bingbot',
    'StackRambler',
    'Googlebot',
);

$emps->pre_init();

$emps->initialize();    // initialization and automatic configuration

if (!$emps->db->operational) {
    // could not connect to a DB
    $emps->database_down();
    exit;
}

$emps->start_time = emps_microtime_float($emps_start_time);

ob_start();

if (!$emps->fast) {
    $emps->auth->handle_logon();

    $fn = $emps->page_file_name('_' . $pp . ',_postinit', 'controller');
    if (file_exists($fn)) {
        require_once $fn;
    }

    $emps->post_init();
}


$sua = $emps->get_setting("service_unavailable");
if ($sua == 'yes') {
    $go = true;
    if (
        substr($_SERVER['REQUEST_URI'], 0, 6) == "/admin" ||
        substr($_SERVER['REQUEST_URI'], 0, 4) == "/mjs"
    ) {
        $go = false;
        if ($emps->auth->USER_ID > 0) {
            if ($emps->auth->USER_ID != 1) {
                $go = true;
            }
        }
    }

    if ($go) {
        $page = $emps->get_setting("unavailable_page");
        if ($page) {
            $smarty->assign("show_page", $page);
        }
        header("HTTP/1.1 503 Service Unavailable");
        header("Retry-After: 3600");
        $smarty->display("db:site/unavailable");
        exit();
    }
}

if ($emps->virtual_path && !$emps->fast) {
// if the item exists in the CMS database
    $data = $emps->get_content_data($emps->virtual_path);

    $emps->last_modified = $emps->virtual_path['dt'];
    $emps->handle_modified();

    $emps->page_property("canprint", 1);
    $emps->copy_properties($emps->virtual_path['uri']);
    $emps->pre_display();

    $out = ob_get_clean();
    $smarty->assign("ob_out", $out);
    $smarty->assign("virtual_path", $emps->virtual_path);

    if (!$data['html']) {
        $emps->not_found();
    } else {
        $smarty->assign("main_body", "page:" . $emps->virtual_path['uri']);
        $smarty->display("db:main");
    }
} else {
// if the item is a controller or a static page
    $fn = $emps->common_module('config/webinit.php');
    if ($fn) {
        require_once $fn;
    }

    $emps->pre_controller();

    $tn = $emps->page_file_name('_' . $pp, 'view');
    $fn = $emps->page_file_name('_' . $pp, 'controller');

    // PHP module
    if (file_exists($fn)) {
        require_once $fn;
    } else {
        if (!file_exists($tn)) {
            $fn = $emps->common_module($pp . '.php');
            if ($fn) {
                $fn = stream_resolve_include_path($fn);
                if ($fn !== false) {
                    require_once $fn;
                }
            }
        }
    }


    // HTML view
    if (!$emps->no_smarty) {
        $emps->pre_display();
        $out = ob_get_clean();
        $smarty->assign("ob_out", $out);

        if (file_exists($tn)) {
            $x = explode("-", $pp);
            if ($x[0] == "comp") {
                // Prevent component HTML from being displayed
                $emps->not_found();
            } else {
                $smarty->assign("main_body", $tn);

                $smarty->display("db:main");
            }
        } else {
            $emps->not_found();
        }
    } else {

    }
}

exit();            // Invoke EMPS class destructor for clean shutdown
