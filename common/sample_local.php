<?php
// Please edit this file and put to ./local/local.php
// Don't forget to create the ./local/temp_c and ./local/upload directories

// hostname / URL configuration
if (!defined('EMPS_HOST_NAME')) {
    define('EMPS_HOST_NAME', 'emps.here'); // Enter your website's hostname here
}

$emps_force_hostname = true;

define('EMPS_SCRIPT_WEB', 'http://' . EMPS_HOST_NAME);
define('EMPS_SCRIPT_URL_FOLDER', '');

// file paths configuration
define('EMPS_SCRIPT_PATH', 'd:/w/git/emps.here/htdocs'); // Full page to your website's htdocs folder
define('EMPS_INCLUDE_PATH', ''); // always have the include paths separated by : (even on Windows)

if (!defined('EMPS_WEBSITE_SCRIPT_PATH')) {
    define('EMPS_WEBSITE_SCRIPT_PATH', EMPS_SCRIPT_PATH);
}

// timezone correction configuration
define('EMPS_TZ_CORRECT', 0);
define('EMPS_TZ', 'Asia/Irkutsk');

define('EMPS_DT_FORMAT', '%d.%m.%Y %H:%M');

define('EMPS_UPLOAD_SUBFOLDER', '/local/upload/');

define('EMPS_MIN_WATERMARKED', 600);

// script timing configuration
define('EMPS_TIMING', false);
define('EMPS_SHOW_TIMING', false);
define('EMPS_SHOW_SQL_ERRORS', false);

// session cookie parameters
define('EMPS_SESSION_COOKIE_LIFETIME', 3600 * 24 * 7);

define('EMPS_DISPLAY_ERRORS', 1);

define('CURRENT_LANG', 1);
define('PHOTOSET_WATERMARK', false);

define('EMPS_PHOTO_SIZE', '1920x1920|100x100|inner');

define("EMPS_FONTS", "d:/w/_fonts"); // Fonts used by GD2

// database configuration. This object will be destroyed upon connection to the database for security reasons.
$emps_db_config = array(
    'host' => 'localhost',
    'database' => 'emps',            // CHANGE
    'user' => 'root',            // CHANGE
    'password' => 'password',        // CHANGE
    'charset' => 'utf8');

define('TP', 'c_');    // table name prefix

// URL variable tracking configuration
// Variables watch list
define('EMPS_VARS', 'aact,pp,act,key,t,ss,start,start2,start3,start4,sk,dlist,sd,sm,cmd,sx,sy,sz');

// Variable/Path mapping string. Variables listed in the order that is used
// to retrieve them from URLs.
define('EMPS_URL_VARS', 'pp,key,start,ss,sd,sk,sm,sx,sy');

// language configuration
$emps_lang = 'ru';                                // default language setting
$emps_lang_map = array('' => 'nn', 'en' => 'en');        // subdomain mapping for language settings

// configuration of SMTP email box for sending messages from the website
$emps_default_smtp_params = array(
    'From' => 'info@mail.ru',
    'Who' => 'My Website',
    'Reply-To' => 'info@mail.ru',
    'Content-Type' => 'text/html; charset=utf-8',
);

$emps_default_smtp_data = array(
    'host' => 'ssl://smtp.mail.ru',
    'port' => '465',
    'auth' => true,
    'username' => 'info@mail.ru',
    'password' => 'password',
);


// initialize the global SMTP vars with the default values
$emps_smtp_params = $emps_default_smtp_params;
$emps_smtp_data = $emps_default_smtp_data;


// ADD YOUR SPECIFIC CONFIGURATION OPTIONS HERE (OAuth keys, Twilio, etc.)
// THAT YOU DON'T WANT TO BE STORED IN GIT (local.php is excluded from the repository)

?>