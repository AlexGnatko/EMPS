<?php

/**
 * The version-independent base class for EMPS
 *
 * This class contains common functions that are not supposed to differ from one version of EMPS to another.
 * Such functions do not depend on the database engine used. They will make their way to the new EMPS 5.0
 * MongoDB-based version.
 */
class EMPS_Common
{
    /**
     * @var $p The Properties object
     */
    public $p;

    /**
     * @var $auth The Authentication object
     */
    public $auth;

    public $start_time = 0;

    public $URI = '';
    public $PURI = '';
    public $PLURI = '';
    public $lang = 'nn';
    public $lang_map = array();
    public $virtual_path = 0;
    public $VA = array();

    public $log_file_path = EMPS_SCRIPT_PATH."/local/log.txt";
    public $log_enabled = false;

    public $page_properties = array();

    /**
     * @var $settings_cache Cache array to store website settings
     */
    public $settings_cache = false;

    /**
     * @var $content_cache Cache array to store content pages
     */
    public $content_cache = array();

    /**
     * @var $require_cache Cache array to store the resolved paths of files looked up with page_file_name()
     */
    public $require_cache = array();

    public $no_smarty = false;

    public $enum = array();

    public $spath = array();

    public $menus = array();
    public $mlv = array();

    public $page_var = 'start';
    public $page_clink = '';
    public $no_autopage = false;

    public $website_ctx, $default_ctx;

    public $current_website;

    public $enums_loaded;

    public $fast = false;

    public $last_modified = 0;

    public $cli_mode = false;

    public $tl_array = array();

    public $json_options = 0;

    public $prand_seed = 17131;

    public function __construct()
    {
        $this->lang = $GLOBALS['emps_lang'];
        $this->lang_map = $GLOBALS['emps_lang_map'];
    }

    public function __destruct()
    {
        ob_end_flush();
    }

    /**
     * Unslash a string prepared by magic_quotes
     *
     * @param $a string
     *
     * @return string
     */
    public function unslash_prepare($a)
    {
        reset($a);
        foreach ($a as $n => $v) {
            if (is_array($v)) {
                $a[$n] = $this->unslash_prepare($v);
            } else {
                $a[$n] = stripslashes($v);
            }
        }
        reset($a);
        return $a;
    }

    /**
     * Early initialization procedure
     *
     * Overloaded by EMPS version classes
     */
    public function early_init()
    {
    }

    /**
     * Main initialization procedure
     *
     * This will detect the current website, parse the URL for variables, initialize Smarty plugins.
     */
    public function initialize()
    {

        $this->early_init();
        $this->select_website();

        if (!$this->cli_mode) {
            $this->parse_path();
            $this->import_vars();
            $this->savevars();

            if ($_GET['plain']) {
                $this->page_property('plain', true);
            }
        }

        $plugins = $this->common_module('smarty.plugins.php');

        if (file_exists($plugins)) {
            require_once $plugins;
        }
    }

    /**
     * Set the HTML Content-Types
     *
     * This function will ensure that the Content-Type header is set to text/html.
     */
    public function text_headers()
    {
        header("Content-Type: text/html; charset=utf-8");
    }

    /**
     * Truncate a string
     *
     * If the $s string is longer that $t characters, it will be truncated at the nearest space character and ended with a ' ...'
     *
     * @param $s string The input string
     * @param $t int The maximum length
     *
     * @return string
     */
    public function cut_text($s, $t)
    {
        $i = $t;
        if (mb_strlen($s) <= $t) {
            return $s;
        }
        for ($i = $t; $i > 0; $i--) {
            $c = mb_substr($s, $i, 1);
            if ($c == ' ') {
                return mb_substr($s, 0, $i) . " ...";
            }
        }
        return "";
    }

    /**
     * Ensure the script can use flash
     *
     * Flash is a feature that lets a script know what happened the previous time the script was called.
     * To use the feature, it has to call $emps->uses_flash() early. If a previous flash session is set,
     * it will be sent to a Smarty variable. A new flash session will be initialized in any case.
     */
    public function uses_flash()
    {
        global $smarty;
        if ($_SESSION['flash']) {
            $smarty->assign("flash", $_SESSION['flash']);
            unset($_SESSION['flash']);
        }
    }

    /**
     * Reset the flash session variable
     */
    public function noflash()
    {
        unset($_SESSION['flash']);
    }

    /**
     * Add a flash variable
     *
     * @param $code string Named array index (variable name)
     * @param $value mixed Any value to store in the flash variable (some short value!)
     */
    public function flash($code, $value)
    {
        $_SESSION['flash'][$code] = $value;
    }

    /**
     * Check if the current module URL should be regarded as 'fast'
     *
     * 'fast' modules are not using authentication and some other modules to boost performance. The list of module names
     * is defined in the EMPS_FAST constant.
     */
    public function check_fast()
    {
        global $pp;

        if(!defined("EMPS_FAST")){
            return;
        }
        $x = explode(',', EMPS_FAST);
        $skip = false;
        foreach($x as $v){
            if ($v == $pp) {
                $skip = true;
            }
        }
        if ($skip) {
            $this->fast = true;
        }
    }

    /**
     * Called after parsing the URL
     *
     * This function, among other things, starts the PHP session.
     */

    public function post_parse()
    {
        global $pp;

        $fn = $this->common_module('config/postparse.php');
        if ($fn) {
            require_once $fn;
        }

        // this website's default content-type is utf-8 HTML
        $this->text_headers();

        $skip = false;

        if (defined("EMPS_NO_SESSION")){
            // these pages should not set the session cookie, they don't need it
            $x = explode(',', EMPS_NO_SESSION);
            foreach($x as $v){
                if ($v == $pp) {
                    $skip = true;
                }
            }
        }

        if (!$skip) {
            $skip = $this->should_prevent_session();
        }
        if (!$skip) {
            if (!$this->is_localhost_request() || $GLOBALS['emps_localhost_mode']) {
                session_start();
                if ($_SESSION['lsu'] < (EMPS_SESSION_COOKIE_LIFETIME / 30)) {
                    $_sess_name = session_name();
                    $_sess_id = session_id();
                    setcookie($_sess_name, $_sess_id, time() + EMPS_SESSION_COOKIE_LIFETIME, "/");
                    $_SESSION['lsu'] = time();
                }
            }
        }
    }

    /**
     * Replace the value of a stored URL variable
     *
     * The next loadvars() will set the variable to this new stored value.
     */
    public function changevar($n, $v)
    {
        $this->VA[$n] = $v;
        $GLOBALS[$n] = $v;
    }

    /**
     * Reset stored URL variables
     *
     * Clears the global variables whose names are defined in the EMPS_VARS constant. Omits the $lang variable.
     */
    public function clearvars()
    {
        $x = explode(",", EMPS_VARS);
        foreach ($x as $value) {
            if ($value == 'lang') {
                continue;
            }
            $GLOBALS[$value] = "";
        }
    }

    /**
     * Load stored URL variables
     *
     * Loads the values of the variables whose names are defined in the EMPS_VARS constant from the $this->VA array property to their respective
     * global variables.
     */
    public function loadvars()
    {
        $pp = explode(",", EMPS_VARS);
        foreach ($pp as $value) {
            $GLOBALS[$value] = $this->VA[$value];
        }
    }

    /**
     * Save URL variables to storage
     *
     * Puts the values of URL variables whose names are defined in the EMPS_VARS constant into the $this->VA array property.
     */
    public function savevars()
    {
        $x = explode(",", EMPS_VARS);
        foreach ($x as $value) {
            if (isset($GLOBALS[$value])) {
                $this->VA[$value] = $GLOBALS[$value];
            }
        }
    }

    /**
     * Sets a page property
     *
     * @param $name string Page property name (code)
     * @param $value mixed Page property value
     */
    public function page_property($name, $value)
    {
        $this->page_properties[$name] = $value;
    }

    /**
     * Copy properties from a content item (page)
     *
     * @param $code string Page URI
     */
    public function copy_properties($code)
    {
        // Load properties from get_content_data for the content item $code and save them as $page_properies
        global $smarty;

        $item = $this->get_db_content_item($code);
        $props = $this->get_content_data($item);
        unset($props['_full']);

        $this->page_properties = array_merge($this->page_properties, $props);
    }

    /**
     * Set properties from a text file (can be obtained from a Smarty template with $lang and {{syn...}} applied)
     *
     * @param $code string Property codes followed by "equals" signs and property values, one property per line
     */
    public function parse_properties($code)
    {
        $x = explode("\n", $code);
        foreach($x as $v){
            $v = trim($v);
            $xx = explode("=", $v);
            $code = trim($xx[0]);
            $value = trim($xx[1]);
            $this->page_properties[$code] = $value;
        }
    }

    public function page_properties_from_settings($list){
        $x = explode(",", $list);
        foreach($x as $v){
            $v = trim($v);
            $value = $this->get_setting($v);
            if(!$value) {
                continue;
            }
            $this->page_property($v, $value);
        }
    }

    /**
     * Adds a new menu item to $this->spath
     *
     * This can be a real menu item or an array prepared by a module script.
     *
     * @param $v array Menu item array
     */
    public function add_to_spath($v)
    {
        if (substr($v['uri'], 0, 1) == '#') {
            return false;
        }
        reset($this->spath);
        foreach ($this->spath as $cv) {
            if (($cv['id'] == $v['id'])) {
                reset($this->spath);
                return false;
            }
        }
        reset($this->spath);
        $this->spath[] = $v;
        return true;
    }

    /**
     * Scan a menu for selected items
     *
     * Iterate through a menu, including sub-menus, and mark menu items that match the current URLs.
     *
     * @param $menu array Menu array
     */
    public function scan_selected(&$menu)
    {
        reset($menu);
        $mr = 0;

        foreach ($menu as $n => $v) {
            $obtained_spath = array();
            if ($v['sub']) {
                $reserve_spath = $this->spath;
                $this->spath = array();
                $res = $this->scan_selected($v['sub']);
                $obtained_spath = $this->spath;
                $this->spath = $reserve_spath;
                $menu[$n]['sub'] = $v['sub'];
                if ($res > 0) {
                    $menu[$n]['ssel'] = $res;
                    $menu[$n]['sel'] = $v['sel'] = 1;
                }
                if ($res > 0) $mr = 1;
            }
            if ($v['sel'] > 0) {
                if(!$this->no_spath[$v['grp']]){
                    $this->add_to_spath($v);
                    foreach ($obtained_spath as $spv) {
                        $this->add_to_spath($spv);
                    }

                }

                $mr = 1;
            }
        }

        return $mr;
    }

    /**
     * Sorting function for menu items
     *
     * @param $a array One menu item
     * @param $b array Other menu item
     */
    function sort_menu($a, $b)
    {
        if ($a['ord'] == $b['ord']) {
            return 0;
        }
        if ($a['ord'] < $b['ord']) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Load a menu or submenu from the database
     *
     * @param $code Menu code
     * @param $parent Parent ID
     */
    public function section_menu($code, $parent)
    {
        return $this->section_menu_ex($code, $parent, 0);
    }

    /**
     * Create menu levels
     *
     * Top menu (0), then selected submenu (1), then selected sub-submenu (2), etc. Used to make the popup-menu for the current page.
     *
     * @param $menu Menu array
     * @param $mlv Menu levels array
     */
    public function menu_levels($menu, $mlv)
    {
        reset($menu);
        $mlv[] = $menu;
        foreach ($menu as $v) {
            if ($v['sel'] > 0 && $v['sub']) {
                $mlv = $this->menu_levels($v['sub'], $mlv);
                break;
            }
        }
        return $mlv;
    }

    /**
     * Prepare all website menus
     *
     * Read the 'handle_menus' setting and load the appropriate menus, including the 'admin' menu.
     */
    public function prepare_menus()
    {
        global $smarty;

        if ($this->auth->credentials("admin,author,editor,oper,seo,copywriter,buh,manager,staff,owner")) {
            $menu = $this->section_menu("admin", 0);
            $this->scan_selected($menu);
            $this->menus['admin'] = $menu;
        }

        $r = $this->get_setting('handle_menus');
        if (!$r) {
            return false;
        }
        $nsr = $this->get_setting('no_spath_menus');
        $x = explode(',', $nsr);
        $no_spath = [];
        foreach($x as $ns_code){
            $no_spath[$ns_code] = true;
        }
        $this->no_spath = $no_spath;

        $x = explode(',', $r);
        foreach($x as $v){
            unset($menu);
            $xx = explode('/', $v);
            $code = $xx[0];
            $t = $xx[1];
            $menu = $this->section_menu($code, 0);
            $this->scan_selected($menu);
            if ($t == 'mlv') {
                $mlv = array();
                $mlv = $this->menu_levels($menu, $mlv);
                $this->mlv[$code] = $mlv;
            }
            $this->menus[$code] = $menu;
        }

        $smarty->assign("menus", $this->menus);
        $smarty->assign("mlv", $this->mlv);
        return true;
    }

    /**
     * Post-init handler
     *
     * Called after the initialization of the EMPS object.
     */
    public function post_init()
    {
        if(strstr($_SERVER["CONTENT_TYPE"], "application/json")){
            $raw = file_get_contents("php://input");
            $request = json_decode($raw, true);
            $_REQUEST = array_merge($_REQUEST, $request);
            $_POST = array_merge($_POST, $request);
        }
        $this->prepare_menus();
    }

    /**
     * Pre-init handler
     *
     * Called before the initialization of the EMPS object.
     */
    public function pre_init()
    {
        if(strstr(@$_SERVER["CONTENT_TYPE"], "application/json")){
            $raw = file_get_contents("php://input");
            $request = json_decode($raw, true);
            $_REQUEST = array_merge($_REQUEST, $request);
            $_POST = array_merge($_POST, $request);
        }
    }

    /**
     * Pre-controller handler
     *
     * Called immediately before a module controller PHP script is called.
     */
    public function pre_controller()
    {
        global $pp, $smarty;
        $x = explode('-', $pp);
        if ($x[0] == "admin" || $x[0] == "manage") {
            $this->page_property("adminpage", 1);
        }

        $smarty->assign("enum", $this->enum);
    }

    /**
     * Pre-display handler
     *
     * Called immediately before a module view Smarty template is displayed.
     */
    public function pre_display()
    {
        global $smarty;

        if (!$this->page_properties['title']) {
            $this->page_properties['title'] = "";
            foreach ($this->spath as $v) {
                if ($this->page_properties['title'] != "") {
                    $this->page_properties['title'] = strip_tags($v['dname']) . " - " . $this->page_properties['title'];
                } else {
                    $this->page_properties['title'] = strip_tags($v['dname']);
                }
            }
        }

        $this->page_property("year", date("Y", time()));

        $smarty->assign("enum", $this->enum);

        $fn = $this->common_module('config/predisplay.php');
        if ($fn) {
            require_once $fn;
        }

        header("Referrer-Policy: unsafe-url");

        $smarty->assign("spath", $this->spath);

        $smarty->assign('page', $this->page_properties);
        $smarty->assign('lang', $this->lang);

        $html_lang = $this->lang;

        if ($html_lang == 'nn') {
            $html_lang = 'ru';
        }
        $smarty->assign("html_lang", $html_lang);

        $smarty->assign("df_format", EMPS_DT_FORMAT);

        $smarty->assign("current_host", $_SERVER['HTTP_HOST']);
        $smarty->assign("current_uri", $_SERVER['REQUEST_URI']);

/*        $clst = get_defined_constants(true);
        foreach ($clst['user'] as $n => $v) {
            if (mb_substr($n, 0, 3) == 'DT_') {
                foreach ($clst['user'] as $m => $w) {
                    if (mb_substr($m, 0, 3) == 'DT_') {
                        if ($m != $n && $v == $w) {
                            echo "ERROR! DUPLICATE DATA TYPE {$n} / {$m} = {$v} / {$w}\r\n";
                        }
                    }
                }
            }
        }*/

    }

    /**
     * Parse an enum descriptor string into an enum array
     *
     * @param $name String Enum name (code)
     * @param $list String Values list string (e.g. '10=Yes,20=No')
     */
    public function make_enum($name, $list)
    {
        $lst = array();
        $x = explode(";", $list);

        foreach ($x as $v) {
            $xx = explode("=", $v, 3);
            $e = array();
            $e['code'] = trim($xx[0]);
            if(strval(intval($e['code'])) == $e['code']){
                $e['code'] = intval($e['code']);
            }
            $e['value'] = $xx[1];
            $dx = explode(",", $xx[2]);
            foreach($dx as $vv){
                if ($vv) {
                    $e[$vv] = 1;
                }
            }
            if ($e['str']) {
                $e['code'] = strval($e['code']);
            }
            $lst[] = $e;
        }
        $this->enum[$name] = $lst;
    }

    /**
     * Redirect handler for parse_path()
     *
     * Called from within parse_path() to check if the current URL has to be redirected (e.g. /admin-shadows/)
     */
    public function handle_redirect($uri)
    {
    }

    /**
     * Main URL parser
     *
     * Parses the current URL to determine if it should be routed to a module or a virtual page.
     */
    private function parse_path()
    {

        $uri = $_SERVER["REQUEST_URI"];

        $first = substr($uri, 1);

        $this->handle_redirect($uri);

        if (function_exists("emps_uri_filter")) {
            $uri = emps_uri_filter($uri);
        }

        $s = explode("?", $uri, 2);

        $uri = $s[0];
        $uri = str_replace(EMPS_SCRIPT_URL_FOLDER, '', $uri);    // remove initial path from the URI

        $this->PLURI = $uri;
        $this->menu_URI = $uri;

        if (substr($uri, 0, 1) == '/') $uri = substr($uri, 1);
        $ouri = $uri;
        $this->PURI = $ouri;

        $this->savevars();
        $uri = $this->PURI;
        if (substr($uri, strlen($uri) - 1, 1) == '/') $uri = substr($uri, 0, strlen($uri) - 1);

        $this->URI = $uri;

        $sp = $this->get_setting("startpage");

        if (!$this->URI) {
            if (!$_SERVER['QUERY_STRING']) {
                $this->URI = $sp;
            }
            $GLOBALS['pp'] = $sp;
            $this->page_property('front', 1);
        }
        if ($vp = $this->page_exists_external($this->PLURI)) {
            // virtual object (CMS database item)
            $this->virtual_path = $vp;
        } elseif ($vp = $this->page_exists($this->PLURI . '/')) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->PLURI . '/');
            exit();
        } else {
            // parse parts of the $ouri as variables from the $RVLIST, make them global
            $xx = explode(",", EMPS_URL_VARS);
            $x = explode("/", $ouri);
            foreach ($x as $n => $v) {
                if ($v == "") continue;
                if ($v != '-') {
                    $GLOBALS[$xx[$n]] = urldecode($v);
                }
            }
        }

        $this->post_parse();
    }

    /**
     * Import URL variables from $GET/$POST
     *
     * Checks if any of the variables whose names are defined in EMPS_VARS exist in $GET or $POST arrays and loads them
     * to the appropriate global variables, if found. Effecively this is a filtered track-vars.
     */
    private function import_vars()
    {
        $x = explode(",", EMPS_VARS);
        foreach ($x as $v) {
            if (!isset($GLOBALS[$v])) $GLOBALS[$v] = '';
            if (isset($_GET[$v])) $GLOBALS[$v] = $_GET[$v];
            if (isset($_POST[$v])) $GLOBALS[$v] = $_POST[$v];
        }
    }

    /**
     * Check if a virtual page exists in the database
     *
     * @param $uri string The full relative URI of the page sought
     */
    public function page_exists($uri)
    {
        $ra = $this->get_db_content_item($uri);
        if ($ra) return $ra;
        return false;
    }

    public function page_exists_external($uri)
    {
        return $this->page_exists($uri);
    }

    public function try_page_file_name($page_name, $first_name, $include_name, $type, $path, $lang)
    {
        $fn = $path . '/modules/' . $page_name;
        $exact = false;
        switch ($type) {
            case 'view':
                if (mb_substr($first_name, 0, 1) == '!') {
                    $first_name = mb_substr($first_name, 1);
                    $fn .= '/' . $first_name;
                    $exact = true;
                } else {
                    $fn .= '/' . $first_name . '.' . $lang . '.htm';
                }
                break;
            case 'controller':
                $fn .= '/' . $first_name . '.php';
                break;
            case 'inc':
                $fn .= '/' . $include_name;
                break;
        }
        if (isset($this->require_cache['try_page_file_name'][$fn])) {
            return $this->require_cache['try_page_file_name'][$fn];
        }
        $fn = stream_resolve_include_path($fn);

        $this->require_cache['try_page_file_name'][$fn] = $fn;
        return $fn;
    }

    public function try_template_name($path, $page_name, $lang)
    {
        $fn = $path . '/templates/' . $lang . '/' . $page_name . '.htm';
        if (isset($this->require_cache['try_template_name'][$fn])) {
            return $this->require_cache['try_template_name'][$fn];
        }
        $fn = stream_resolve_include_path($fn);

        $this->require_cache['try_template_name'][$fn] = $fn;
        return $fn;
    }

    public function module_exists($name) {
        $fn = $this->page_file_name("_{$name}", "controller");
        if (file_exists($fn)) {
            return true;
        }
        return false;
    }

    public function page_file_name($page_name, $type)
    {
        // This function controls the naming of files used by the application
        if (isset($this->require_cache['page_file'][$type][$page_name])) {
            return $this->require_cache['page_file'][$type][$page_name];
        }
        $opage_name = $page_name;

        if (substr($page_name, 0, 1) == '_') {
            $page_name = substr($page_name, 1);
            $page_name = str_replace('-', '/', $page_name);
            if ($type == 'inc') {
                $x = explode(',', $page_name, 2);
                $page_name = $x[0];
                $include_name = $x[1];
            } else {
                $x = explode(',', $page_name);
                if (isset($x[1])) {
                    $page_name = $x[0];
                    $first_name = $x[1];
                } else {
                    $x = explode('/', $page_name);
                    $first_name = array_pop($x);
                }
            }

            if (!isset($include_name)) {
                $include_name = $page_name;
            }

            $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, $this->lang);
            if (!$fn) {
                $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, 'nn');
                if (!$fn) {
                    $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, $this->lang);
                    if (!$fn) {
                        $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, 'nn');
                        if (!$fn) {
                            $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, $this->lang);
                            if (!$fn) {
                                $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, 'nn');
                                if (!$fn) {
                                    $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, $this->lang);
                                    if (!$fn) {
                                        $fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, 'nn');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, $this->lang);
            if (!$fn) {
                $fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, 'nn');
                if (!$fn) {
                    $fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, $this->lang);
                    if (!$fn) {
                        $fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, 'nn');
                        if (!$fn) {
                            $fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, $this->lang);
                            if (!$fn) {
                                $fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, 'nn');
                                if (!$fn) {
                                    $fn = $this->try_template_name(EMPS_COMMON_PATH_PREFIX, $page_name, $this->lang);
                                    if (!$fn) {
                                        $fn = $this->try_template_name(EMPS_COMMON_PATH_PREFIX, $page_name, 'nn');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->require_cache['page_file'][$type][$page_name] = $fn;
        return $fn;
    }

    public function try_common_module_html($path, $file_name, $lang)
    {

        $x = explode(".", $file_name);
        $len = mb_strlen($x[count($x) - 1], "utf-8");
        if ($len <= 3) {
            $fn = $path . '/modules/_common/' . $file_name;
        } else {
            $fn = $path . '/modules/_common/' . $file_name . '.' . $lang . '.htm';
        }

        if (isset($this->require_cache['common_module_html_try'][$fn])) {
            return $this->require_cache['common_module_html_try'][$fn];
        }

        if (!file_exists($fn)) {
            $fn = false;
        }

        $this->require_cache['common_module_html_try'][$fn] = $fn;
        return $fn;
    }

    public function common_module_html($file_name)
    {
        // This function controls the naming of files used by common modules
        if (isset($this->require_cache['common_module_html'][$file_name])) {
            return $this->require_cache['common_module_html'][$file_name];
        }
        $fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH, $file_name, $this->lang);
        if (!$fn) {
            $fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH, $file_name, 'nn');
            if (!$fn) {
                $fn = $this->try_common_module_html(EMPS_SCRIPT_PATH, $file_name, $this->lang);
                if (!$fn) {
                    $fn = $this->try_common_module_html(EMPS_SCRIPT_PATH, $file_name, 'nn');
                    if (!$fn) {
                        $x = explode(".", $file_name);
                        $len = mb_strlen($x[count($x) - 1], "utf-8");
                        if ($len <= 3) {
                            $fn = EMPS_PATH_PREFIX . '/common/' . $file_name;
                            $fn = stream_resolve_include_path($fn);
                            if (!$fn) {
                                $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name;
                                $fn = stream_resolve_include_path($fn);
                            }
                        } else {
                            $fn = EMPS_PATH_PREFIX . '/common/' . $file_name . '.' . $this->lang . '.htm';
                            $fn = stream_resolve_include_path($fn);
                            if (!$fn) {
                                $fn = EMPS_PATH_PREFIX . '/common/' . $file_name . '.nn.htm';
                                $fn = stream_resolve_include_path($fn);
                                if (!$fn) {
                                    $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name . '.' . $this->lang . '.htm';
                                    $fn = stream_resolve_include_path($fn);
                                    if (!$fn) {
                                        $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name . '.nn.htm';
                                        $fn = stream_resolve_include_path($fn);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->require_cache['common_module_html'][$file_name] = $fn;
        return $fn;
    }

    public function try_common_module($path, $file_name)
    {
        if (isset($this->require_cache['common_module_try'][$path][$file_name])) {
            return $this->require_cache['common_module_try'][$path][$file_name];
        }
        $fn = $path . '/modules/_common/' . $file_name;
        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['common_module_try'][$path][$file_name] = $fn;
        return $fn;
    }

    public function common_module_ex($file_name, $level)
    {
        // This function controls the naming of files used by common modules
        if (isset($this->require_cache['common_module'][$level][$file_name])) {
            return $this->require_cache['common_module'][$level][$file_name];
        }

        $fn = $this->try_common_module(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
        if (!$fn || ($level > 0)) {
            $fn = $this->try_common_module(EMPS_SCRIPT_PATH, $file_name);
            if (!$fn || ($level > 1)) {
                $fn = EMPS_PATH_PREFIX . '/common/' . $file_name;
                $fn = stream_resolve_include_path($fn);
                if (!$fn) {
                    $fn = EMPS_COMMON_PATH_PREFIX . '/common/' . $file_name;
                    $fn = stream_resolve_include_path($fn);
                }
            }
        }

        if ($fn != false) {
            $this->require_cache['common_module'][$level][$file_name] = $fn;
        }

        return $fn;
    }

    public function common_module($file_name)
    {
        return $this->common_module_ex($file_name, 0);
    }


    public function try_core_script($path, $file_name)
    {
        if (isset($this->require_cache['common_script_try'][$path][$file_name])) {
            return $this->require_cache['common_script_try'][$path][$file_name];
        }
        $fn = stream_resolve_include_path($path . "/core/" . $file_name . ".php");

        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['common_script_try'][$path][$file_name] = $fn;
        return $fn;
    }


    public function core_module($file_name)
    {

        $fn = $this->try_core_script(EMPS_PATH_PREFIX, $file_name);
        if (!$fn) {
            $fn = $this->try_core_script(EMPS_COMMON_PATH_PREFIX, $file_name);
        }
        return $fn;
    }

    public function try_plain_file($path, $file_name)
    {
        if (isset($this->require_cache['plain_file_try'][$path][$file_name])) {
            return $this->require_cache['plain_file_try'][$path][$file_name];
        }
        $fn = $path . $file_name;
        if (!file_exists($fn)) {
            $fn = false;
        }
        $this->require_cache['plain_file_try'][$path][$file_name] = $fn;
        return $fn;
    }

    public function plain_file($file_name)
    {
        // This function finds a file in the websites' folders
        // (first the primary website, then the base website) and then in the main EMPS folder
        if (isset($this->require_cache['plain_file'][$file_name])) {
            return $this->require_cache['plain_file'][$file_name];
        }
        $fn = $this->try_plain_file(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
        if (!$fn) {
            $fn = $this->try_plain_file(EMPS_SCRIPT_PATH, $file_name);
            if (!$fn) {
                $fn = EMPS_PATH_PREFIX . $file_name;
                $fn = stream_resolve_include_path($fn);
                if (!$fn) {
                    $fn = EMPS_COMMON_PATH_PREFIX . $file_name;
                    $fn = stream_resolve_include_path($fn);
                }
            }
        }

        if ($fn != false) {
            $this->require_cache['plain_file'][$file_name] = $fn;
        }

        return $fn;
    }

    public function pad_menu($template){
        global $smarty;
        $smarty->assign("lang", $this->lang);
        $json = $smarty->fetch($template);
        $menu = json_decode($json, true);
        return $menu;
    }

    public function not_found()
    {
        global $smarty;
        header("HTTP/1.1 404 Not Found");
        $smarty->assign("main_body", "db:page/notfound");
        $this->pre_display();
        $this->page_property("plain", $this->get_setting("plain_404"));
        $smarty->assign('page', $this->page_properties);
        $smarty->display("db:main");
    }

    public function database_down()
    {
        global $smarty;
        header("HTTP/1.1 500 Internal Server Error");
        $this->pre_display();
        $smarty->display("db:page/databasedown");
    }

    public function deny_access($reason)
    {
        global $smarty;

        $this->retry_for_session();
        $smarty->assign($reason, 1);
    }

    public function retry_for_session()
    {
        if ($this->should_prevent_session()) {
            $retry = intval($_GET['retry']);
            if ($retry < 3) {
                $retry++;
                $this->redirect_page("./?retry=" . $retry);
                exit();
            }
        }
    }

    public function add_to_menu(&$menu, $variable, $code, $name)
    {
        // Add a code/name pair to a $menu, the selection of a menu item is tracked by $variable
        $current_value = $GLOBALS[$variable];
        $e = array();
        $e['code'] = $code;
        $GLOBALS[$variable] = $code;
        $e['link'] = $this->elink();
        $this->loadvars();
        if ($current_value == $code) {
            $e['sel'] = 1;
        }
        $e['name'] = $name;
        $menu[] = $e;
    }

    public function prepare_pad_menu($pads, $variable)
    {
        $menu = array();
        if (!is_array($pads)) {
            return false;
        }

        foreach ($pads as $n => $v) {
            $this->add_to_menu($menu, $variable, $n, $v);
        }
        return $menu;
    }

    public function count_pages($total)
    {
        // New pagination function
        global $perpage;

        if ($total < $GLOBALS[$this->page_var] && !$this->no_autopage && $total > 0) {
            $GLOBALS[$this->page_var] = 0;
            $this->redirect_page($this->elink() . "?" . $_SERVER['QUERY_STRING']);
        }

        if (!$perpage) {
            $perpage = 10;
        }
        $a = array();

        if (is_numeric($GLOBALS[$this->page_var])) {
            if ($GLOBALS[$this->page_var] >= $total) {
                $GLOBALS[$this->page_var] = $total - $perpage;
                if ($GLOBALS[$this->page_var] < 0) {
                    $GLOBALS[$this->page_var] = 0;
                }
                $this->savevars();
            }
        }

        $cs = $GLOBALS[$this->page_var];
        $f = ceil($total / $perpage);

        $cf = $f;
        $scl = floor($GLOBALS[$this->page_var] / $perpage) - 3;
        if ($scl < 0) $scl = 0;

        if ($f > 7) $f = 7;

        if ($f + $scl > $cf) $scl = $cf - $f;

        if ($scl < 0) $scl = 0;

        $GLOBALS[$this->page_var] = 0;
        $a['first']['start'] = $GLOBALS[$this->page_var];
        $a['first']['page'] = 1;
        $a['first']['link'] = $this->clink($this->page_clink);
        $pl = array();

        $selitem = -1;

        for ($i = 0; $i < $f; $i++) {
            $GLOBALS[$this->page_var] = ($i + $scl) * $perpage;

            $pl[$i] = array();

            $sel = false;
            if ($GLOBALS[$this->page_var] == $cs) {
                $pl[$i]['sel'] = true;
                $sel = true;
                $selitem = $i;
            }

            $pl[$i]['start'] = $GLOBALS[$this->page_var];
            $pl[$i]['link'] = $this->clink($this->page_clink);
            $pl[$i]['page'] = ($i + $scl + 1);

            $GLOBALS[$this->page_var]++;

            $pl[$i]['fi'] = $GLOBALS[$this->page_var] + 0;

            $res = ($GLOBALS[$this->page_var] + $perpage - 1);

            if ($res > $total) $res = $total;

            $pl[$i]['li'] = $res + 0;
            $pl[$i]['count'] = $res - $GLOBALS[$this->page_var] + 1;

            if ($pl[$i]['sel']) {
                $a['cur'] = $pl[$i];
            }

        }

        $GLOBALS[$this->page_var] = ($cf - 1) * $perpage;

        if ($pl[$i - 1]['start'] == $GLOBALS[$this->page_var]) {
            $a['last'] = $pl[$i - 1];
        } else {
            $a['last'] = array();
            $a['last']['start'] = $GLOBALS[$this->page_var];
            $a['last']['link'] = $this->clink($this->page_clink);
            $a['last']['page'] = $cf;
        }

        $npl = array();
        for ($i = 0; $i < $f; $i++) {
            $npl[$i] = array_slice($pl[$i], 0);
            if ($i > 0) {
                $npl[$i]['prev'] = array_slice($pl[$i - 1], 0);
            } else {
                $npl[$i]['prev'] = $a['last'];
            }
            if ($i < $f - 1) {
                $npl[$i]['next'] = array_slice($pl[$i + 1], 0);
            } else {
                $npl[$i]['next'] = $a['first'];
            }
        }

        if ($selitem != -1) {
            $a['prev'] = $npl[$selitem]['prev'];
            $a['next'] = $npl[$selitem]['next'];
        }

        $GLOBALS[$this->page_var] = "all";
        $a['all'] = ['start' => 'all', 'link' => $this->clink($this->page_clink)];

        $GLOBALS[$this->page_var] = $cs;

        $a['pl'] = $npl;
        $a['count'] = count($npl);
        $a['total'] = $total;

        return $a;
    }

    public function clink($a)
    {
        // Make up a link with the current variables plus another query part component (e.g. "x=1")
        $l = $this->elink();
        if ($a) {
            if (strstr($l, "?")) {
                $l .= "&" . $a;
            } else {
                $l .= "?" . $a;
            }
        }
        return $l;
    }

    public function xrawurlencode($vle)
    {
        // rawurlencode that doesn't encode hyphens
        $v = rawurlencode($vle);
        $v = str_replace("%2F", "-", $v);
        $v = str_replace("%2C", ",", $v);
        $v = str_replace("%3D", "=", $v);
        $v = str_replace("%3B", ";", $v);

        return $v;
    }

    public function elink()
    {
        // Make up an internal link with the variables
        $x = explode(",", EMPS_URL_VARS);
        $rlist = array();
        foreach ($x as $v) {
            $rlist[$v] = $GLOBALS[$v];
        }

        $t = "";
        $tc = "";

        foreach ($x as $v) {
            $v = $this->xrawurlencode($GLOBALS[$v]);
            if (!$v) {
                $tc .= "/-";
            } else {
                $t .= $tc;
                $t .= "/$v";
                $tc = "";
            }
        }
        $t .= "/";

        $s = false;
        $xx = explode(",", EMPS_VARS);
        foreach ($xx as $value) {
            if ($GLOBALS[$value] == "") continue;
            if ($rlist[$value] != "") continue;
            if ($s) $t .= "&"; else $t .= "?";
            $s = true;
            $t .= $value . "=" . rawurlencode($GLOBALS[$value]);
        }
        return $t;
    }

    public function slink($value, $var) {
        $GLOBALS[$var] = $value;
        return $this->elink();
    }

    public function print_pages($found)
    {
        global $smarty;

        $pages = $this->count_pages($found);
        $smarty->assign("pages", $pages);
        return $smarty->fetch("db:page/paginator");
    }

    public function form_time($dt)
    {
        return date("d.m.Y H:i", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function form_time_full($dt)
    {
        return date("d.m.Y H:i:s", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function get_log_time()
    {
        $mt = microtime();
        $x = explode(' ', $mt, 2);
        return date("d.m.Y H:i:s", $x[1] + EMPS_TZ_CORRECT * 60 * 60) . sprintf(':%d', $x[0] * 1000);
    }

    public function form_date($dt)
    {
        return date("d.m.Y", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function form_date_full($dt)
    {
        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября',
        'ноября', 'декабря'];

        $month = intval(date("m", $dt)) - 1;
        $month_name = $months[$month];
        return sprintf("%s %s %s", date("d", $dt), $month_name, date("Y", $dt));
    }

    public function form_date_us($dt)
    {
        return date("m/d/Y", $dt + EMPS_TZ_CORRECT * 60 * 60);
    }

    public function parse_time($v)
    {
        $p = explode(" ", $v);
        $d = explode(".", $p[0]);
        $mon = intval($d[1]);
        $day = intval($d[0]);
        $year = intval($d[2]);
        if (!$p[1]) {
            $p[1] = '12:00:00';
        }

        $t = explode(":", $p[1]);
        $hour = intval($t[0]);
        $min = intval($t[1]);
        $sec = intval($t[2]);
        $dt = mktime($hour, $min, $sec, $mon, $day, $year) - EMPS_TZ_CORRECT * 60 * 60;

        return $dt;
    }

    public function redirect_page($page)
    {
        header("Location: " . $page);
    }

    public function redirect_elink()
    {
        $this->redirect_page($this->elink());
    }

    public function kill_flood($txt, $max)
    {
        $l = strlen($txt);
        $res = "";
        $intag = false;
        $cnt = 0;
        for ($i = 0; $i < $l; $i++) {
            $c = substr($txt, $i, 1);

            if ($c == '<') $intag = true;
            if ($c == '>') {
                $intag = false;
                $res .= $c;
                continue;
            }

            if (($c == ' ' || $c == '\n' || $c == '\t') && (!$intag)) {
                $cnt = 0;
            } else {
                if (!$intag) {
                    $cnt++;
                }
            }

            if (($cnt > $max) && (!$intag)) {
                $cnt = 0;
                $res .= " ";
            }
            $res .= $c;
        }
        return $res;
    }

    public function check_required($arr, $list)
    {
        // Check if $arr contains values named with comma-separated values in the $list. If an item from $list
        // does not exist in the $arr, it is added to the $err array so that Smarty could know which fields
        // are missing: style="field {{if $err.some_value}}error{{/if}}"
        $x = explode(",", $list);
        $err = array();
        foreach($x as $v){
            if (!$arr[$v]) {
                $err[] = $v;
            } else {
                if (is_array($arr[$v])) {
                    if (!$arr[$v][0] && count($arr[$v]) == 1) {
                        $err[] = $v;
                    }
                }
            }
        }
        return $err;
    }

    public function partial_array($arr, $list)
    {
        $x = explode(",", $list);
        $parr = array();
        foreach($x as $v){
            if ($arr[$v]) {
                $parr[$v] = $arr[$v];
            }
        }
        return $parr;
    }

    public function utf8_urldecode($str)
    {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($str));
        return html_entity_decode($str, null, 'UTF-8');;
    }

    public function ensure_protocol($protocol)
    {
        $addr = EMPS_HOST_NAME . $_SERVER['REQUEST_URI'];
        if ($protocol == 'https') {
            if ($_SERVER['HTTPS'] != 'on') {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: https://" . $addr);
                exit;
            }
        } elseif ($protocol == 'http') {
            if ($_SERVER['HTTPS'] == 'on') {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: http://" . $addr);
                exit;
            }
        }
    }

    public function is_https()
    {
        if ($_SERVER['HTTPS'] == 'on') {
            return true;
        }
        return false;
    }

    public function load_enums_from_file_ex($file)
    {
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $x = explode("\n", $data);
            foreach ($x as $v) {
                $v = trim($v);
                $m = explode(':', $v, 2);
                $name = trim($m[0]);
                $value = trim($m[1]);
                if ($name && $value) {
                    $this->make_enum($name, $value);
                }
            }
            $this->enums_loaded = true;
        }
    }

    public function load_enums_from_file()
    {
        $file_list = array();
        for ($i = 2; $i >= 0; $i--) {
            $file = $this->common_module_ex("config/enum.nn.txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
            $file = $this->common_module_ex("config/enum." . $this->lang . ".txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }

            $file = $this->common_module_ex("config/project/enum.nn.txt", $i);
            if (!isset($file_list[$file])) {
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
            $file = $this->common_module_ex("config/project/enum." . $this->lang . ".txt", $i);
            if (!isset($file_list[$file])) {
//				echo $file."<br/>";
                $this->load_enums_from_file_ex($file);
                $file_list[$file] = true;
            }
        }
    }

    public function enum_val($enum, $code)
    {
        $lst = $this->enum[$enum];
        foreach ($lst as $n => $v) {
            if ($v['code'] == $code) {
                return $v['value'];
            }
        }
        return false;
    }

    public function enumval($code, $enum){
        return $this->enum_val($enum, $code);
    }

    public function inflection($value)
    {
        return $this->infliction($value);
    }

    public function infliction($value)
    {
        $h = floor(($value % 100) / 10);
        $d = $value % 10;

        if ($d == 1) {
            if ($h == 1) {
                return 5;
            } else {
                return 1;
            }
        }
        if ($d >= 2 && $d <= 4) {
            if ($h == 1) {
                return 5;
            } else {
                return 2;
            }
        }

        return 5;
    }

    public function traceback(Exception $e)
    {
        $o = "";

        $trace = $e->getTrace();

        $i = count($trace);
        foreach ($trace as $v) {
            $o .= "#" . $i . ": at line " . $v['line'] . " of " . $v['file'] . ", " . $v['class'] . $v['type'] . $v['function'] . "\r\n";
            $i--;
        }

        return $o;
    }

    public function expire_guess()
    {
        $dt = time();
        if ($this->last_modified > 0) {
            $past = time() - $this->last_modified;
            $mins = floor($past / 60);
            $hours = floor($mins / (60));
            $days = floor($hours / 24);
            if ($days > 7) {
                return time() + 7 * 24 * 60 * 60;
            }
            if ($days > 1) {
                return time() + 2 * 24 * 60 * 60;
            }
            if ($hours > 12) {
                return time() + 12 * 60 * 60;
            }
            if ($hours > 6) {
                return time() + 6 * 60 * 60;
            }
            if ($hours > 2) {
                return time() + 2 * 60 * 60;
            }
            if ($hours > 1) {
                return time() + 60 * 60;
            }
            if ($mins > 30) {
                return time() + 30 * 60;
            }
            if ($mins > 15) {
                return time() + 15 * 60;
            }
            return time() + 60;
        }
        return $dt;
    }

    public function handle_modified()
    {
        if ($this->last_modified > 0) {
            header("Expires: " . date("r", $this->expire_guess()));
            header("Last-Modified: " . date("r", $this->last_modified));

            $if_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            if ($if_modified) {
                $if_dt = strtotime($if_modified);
                if ($this->last_modified <= $if_dt) {
                    header("HTTP/1.1 304 Not Modified");
                    exit();
                }
            }
        }
    }

    public function should_prevent_session()
    {
        global $emps_bots, $emps_just_set_cookie;

        if ($this->is_localhost_request()) {
            return false;
        }

        if (!$_SERVER['HTTP_USER_AGENT']) {
            return true;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'];
        foreach ($emps_bots as $bot) {
            if (strpos($ua, $bot) != false) {
                return true;
            }
        }

        if (!$emps_just_set_cookie) {
            if (!isset($_COOKIE['EMPS'])) {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    public function normalize_url()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $x = explode("?", $uri, 2);
        $uri = $x[0];
        $elink = $this->elink();
        if ($uri != $elink) {
            $this->redirect_elink();
            exit();
        }
    }

    public function in_list($val, $list)
    {
        $x = explode(",", $list);
        foreach ($x as $v) {
            if ($v == $val) {
                return true;
            }
        }
        return false;
    }

    public function values_match($row, $copy, $list) {
        $x = explode(",", $list);
        foreach ($x as $v) {
            if ($row[$v] != $copy[$v]) {
                return false;
            }
        }
        return true;
    }

    public function copy_values(&$target, $source, $list)
    {
        $x = explode(",", $list);
        foreach ($x as $v) {
            $v = trim($v);
            $xx = explode(":", $v);
            $v = trim($xx[0]);
            if (isset($source[$v])) {
                $target[$v] = $source[$v];
            }
        }
    }

    /*
     * Source: https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/formatting.php
     */
    public function remove_accents($string) {
        if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
            return $string;
        }

        $chars = array(
            // Decompositions for Latin-1 Supplement
            'ª' => 'a',
            'º' => 'o',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Ø' => 'O',
            // Decompositions for Latin Extended-A
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
            'ſ' => 's',
            // Decompositions for Latin Extended-B
            'Ș' => 'S',
            'ș' => 's',
            'Ț' => 'T',
            'ț' => 't',
            // Euro Sign
            '€' => 'E',
            // GBP (Pound) Sign
            '£' => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            'Ơ' => 'O',
            'ơ' => 'o',
            'Ư' => 'U',
            'ư' => 'u',
            // grave accent
            'Ầ' => 'A',
            'ầ' => 'a',
            'Ằ' => 'A',
            'ằ' => 'a',
            'Ề' => 'E',
            'ề' => 'e',
            'Ồ' => 'O',
            'ồ' => 'o',
            'Ờ' => 'O',
            'ờ' => 'o',
            'Ừ' => 'U',
            'ừ' => 'u',
            'Ỳ' => 'Y',
            'ỳ' => 'y',
            // hook
            'Ả' => 'A',
            'ả' => 'a',
            'Ẩ' => 'A',
            'ẩ' => 'a',
            'Ẳ' => 'A',
            'ẳ' => 'a',
            'Ẻ' => 'E',
            'ẻ' => 'e',
            'Ể' => 'E',
            'ể' => 'e',
            'Ỉ' => 'I',
            'ỉ' => 'i',
            'Ỏ' => 'O',
            'ỏ' => 'o',
            'Ổ' => 'O',
            'ổ' => 'o',
            'Ở' => 'O',
            'ở' => 'o',
            'Ủ' => 'U',
            'ủ' => 'u',
            'Ử' => 'U',
            'ử' => 'u',
            'Ỷ' => 'Y',
            'ỷ' => 'y',
            // tilde
            'Ẫ' => 'A',
            'ẫ' => 'a',
            'Ẵ' => 'A',
            'ẵ' => 'a',
            'Ẽ' => 'E',
            'ẽ' => 'e',
            'Ễ' => 'E',
            'ễ' => 'e',
            'Ỗ' => 'O',
            'ỗ' => 'o',
            'Ỡ' => 'O',
            'ỡ' => 'o',
            'Ữ' => 'U',
            'ữ' => 'u',
            'Ỹ' => 'Y',
            'ỹ' => 'y',
            // acute accent
            'Ấ' => 'A',
            'ấ' => 'a',
            'Ắ' => 'A',
            'ắ' => 'a',
            'Ế' => 'E',
            'ế' => 'e',
            'Ố' => 'O',
            'ố' => 'o',
            'Ớ' => 'O',
            'ớ' => 'o',
            'Ứ' => 'U',
            'ứ' => 'u',
            // dot below
            'Ạ' => 'A',
            'ạ' => 'a',
            'Ậ' => 'A',
            'ậ' => 'a',
            'Ặ' => 'A',
            'ặ' => 'a',
            'Ẹ' => 'E',
            'ẹ' => 'e',
            'Ệ' => 'E',
            'ệ' => 'e',
            'Ị' => 'I',
            'ị' => 'i',
            'Ọ' => 'O',
            'ọ' => 'o',
            'Ộ' => 'O',
            'ộ' => 'o',
            'Ợ' => 'O',
            'ợ' => 'o',
            'Ụ' => 'U',
            'ụ' => 'u',
            'Ự' => 'U',
            'ự' => 'u',
            'Ỵ' => 'Y',
            'ỵ' => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            'ɑ' => 'a',
            // macron
            'Ǖ' => 'U',
            'ǖ' => 'u',
            // acute accent
            'Ǘ' => 'U',
            'ǘ' => 'u',
            // caron
            'Ǎ' => 'A',
            'ǎ' => 'a',
            'Ǐ' => 'I',
            'ǐ' => 'i',
            'Ǒ' => 'O',
            'ǒ' => 'o',
            'Ǔ' => 'U',
            'ǔ' => 'u',
            'Ǚ' => 'U',
            'ǚ' => 'u',
            // grave accent
            'Ǜ' => 'U',
            'ǜ' => 'u',
        );

        $string = strtr( $string, $chars );

        return $string;
    }

    public function transliterate($c)
    {
        if ($c == '0') {
            return $c;
        }
        $src = "A.B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z." .
            "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "1.2.3.4.5.6.7.8.9.0.А.Б.В.Г.Д.Е.Ё.Ж.З.И.Й.К.Л.М.Н.О.П.Р.С.Т.У.Ф.Х.Ц.Ч.Ш.Щ.Ъ.Ы.Ь.Э.Ю.Я." .
            "а.б.в.г.д.е.ё.ж.з.и.й.к.л.м.н.о.п.р.с.т.у.ф.х.ц.ч.ш.щ.ъ.ы.ь.э.ю.я.é";
        $dest = "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z." .
            "1.2.3.4.5.6.7.8.9.0.a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya." .
            "a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya.e";
        if (!$this->tl_array) {
            $x = explode(".", $src);
            $y = explode(".", $dest);
            $l = count($x);
            $this->tl_array = array();
            for ($i = 0; $i < $l; $i++) {
                $this->tl_array['_' . $x[$i]] = $y[$i];
            }
        }
        if ($this->tl_array['_' . $c]) {
            return $this->tl_array['_' . $c];
        }

        if ($c == ' ' || $c == '-' || $c == '_' || $c == ':' || $c == '*') {
            return '-';
        }

        if ($c == '\'' || $c == '"') {
            return "";
        }

        if ($c == ',' || $c == ';') {
            return ',';
        }

        return '.';
    }

    public function transliterate_url($source)
    {
        $s = $this->remove_accents($source);
        $t = "";
        $l = mb_strlen($s);
        $c = '';
        $pc = '';
        for ($i = 0; $i < $l; $i++) {
            $c = mb_substr($s, $i, 1, "UTF-8");
            $tc = $this->transliterate($c);
            if (($pc == '-' || $pc == '.' || $pc == ',') && ($tc == '-' || $tc == '.' || $tc == ',')) {
                continue;
            }
            $pc = $tc;
            $t .= $tc;
        }
        $l = mb_strlen($t);
        $lc = mb_substr($t, $l - 1, 1);
        if ($lc == '.' || $lc == ',' || $lc == '-') {
            $t = mb_substr($t, 0, $l - 1);
        }
        return $t;
    }

    public function is_localhost_request()
    {
        if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
            return true;
        }
        return false;
    }

    public function json_response($response)
    {
        global $emps;

        $emps->no_smarty = true;
        header("Content-Type: application/json; charset=utf-8");

        echo json_encode($response, $this->json_options);
    }

    public function json_error($message) {
        $response = [];
        $response['code'] = "Error";
        $response['message'] = $message;
        $this->json_response($response);
    }

    public function json_ok($data) {
        $response = [];
        $response['code'] = "OK";
        $response = array_merge($response, $data);
        $this->json_response($response);
    }

    public function plaintext_response()
    {
        global $emps;

        $emps->no_smarty = true;
        header("Content-Type: text/plain; charset=utf-8");
    }

    public function array_info($array){
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    function indexes_list($ar)
    {
        reset($ar);
        $lst = "";
        foreach ($ar as $n => $v) {
            if ($lst != "") {
                $lst .= ", ";
            }
            $lst .= $n;
        }
        return $lst;
    }

    public function recaptcha_check()
    {
        $response = $_POST['g-recaptcha-response'];
        if (!$response) {
            return false;
        }

        $postdata = http_build_query(
            array(
                'secret' => GOOGLE_KEY_RECAPTCHA,
                'response' => $response
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        $data = json_decode($result, true);
        $this->last_recaptcha_result = $data;
        if ($data['success']) {
            return true;
        }

        return false;
    }

    public function referer_vars()
    {
        $referer = $_SERVER['HTTP_REFERER'];

        $x = explode(EMPS_SCRIPT_WEB, $referer);
        if ($x[0] == "" && isset($x[1])) {
            $xx = explode(",", EMPS_URL_VARS);
            $uri = mb_substr($x[1], 1);
            $x = explode("/", $uri);
            foreach ($x as $n => $v) {
                if ($v == "") continue;
                if ($v != '-') {
                    $GLOBALS[$xx[$n]] = urldecode($v);
                }
            }
        }
    }

    /**
     * Pseudo-random number generator
     *
     * Used in {{syn v=""}} to select variants pseudo-randomly based on the page URL seed
     */
    public function prand($min, $max){
        $pv = $this->prand_seed;
        $cv = $this->prand_seed * $this->prand_seed + 7;
        //echo $cv." => ";
        $val = $cv / 10;
        $val = $val % 100000;

        if($val == 0){
            $val = 15891;
        }
        if($val == 1){
            $val = 21131;
        }

        if($pv == $val){
            $val += 7;
        }

        $this->prand_seed = $val;
        //echo $val;

        $diff = abs($max - $min);
        $rv = $val / (100000 / $diff);
        $rv += $min;
        return $rv;
    }

    /**
     * Create a 5-digital-digit prand_seed from an md5 string
     *
     * A few digits of the md5 string will be cut out of the middle of the string, converted to integer, and limited at 11111-27999
     *
     * @param $md string The input md5 string
     *
     * @return int
     */

    public function prand_md5_seed($md5){
        $s = substr($md5, 8, 6);
        $int = intval($s, 16);
        $v = $int % 16889;
        $this->prand_seed = $v + 11111;
        return $this->prand_seed;
    }

    /**
     * Pseudo-random shuffling of an array
     *
     * Unlike the original shuffle(), this one will generate the same pseudo-random results depending on the initial prand_seed
     *
     * @param $array &array The array to shuffle
     *
     * @return bool
     */
    public function prand_shuffle(&$array)
    {
        $l = count($array);
        for($i = 0; $i < $l; $i++){
            $v = $array[$i];
            $idx = $this->prand(0, $l - 1);
            $array[$i] = $array[$idx];
            $array[$idx] = $v;
        }
        return true;
    }

    /**
     * Set the maximum execution time of the script to unlimited / 12 hours.
     */
    public function no_time_limit(){
        ini_set("max_execution_time",60*60*12);
        set_time_limit(0);
        ignore_user_abort(true);
    }

    /**
     * Control service execution: check if it's too early to run this service again (period since the last run hasn't
     * yet elapsed).
     *
     * @param $code Service code
     * @param $period Period in seconds between service runs
     */
    public function service_control($code, $period){
        $setting_name = "_service_control_".$code;
        $last_run = $this->get_setting($setting_name);
        $next_run = $last_run + $period;

        $rv = [];
        $rv['wait'] = false;

        if(time() < $next_run){
            $rv['wait'] = true;
            $rv['nextrun'] = $next_run;
        }else{
            $this->save_setting($setting_name, time());
        }

        return $rv;
    }

    public function json_dump($data){
        echo "<pre>";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }

    public function amount($number){
        $str = number_format($number, 10, ",", "");

        $str = preg_replace('~\,0+$~','', $str);

        return $str;
    }

    function format_size($bytes, $lang)
    {
        $bytes = intval($bytes);
        if ($bytes <= 0) return $bytes;
        if($lang == 'en'){
            $formats = array("%d bytes", "%.1f KB", "%.1f MB", "%.1f GB", "%.1f TB");
        }else{
            $formats = array("%d байт", "%.1f Кб", "%.1f Мб", "%.1f Гб", "%.1f Тб");
        }

        $logsize = min((int)(log($bytes) / log(1024)), count($formats) - 1);
        return sprintf($formats[$logsize], $bytes / pow(1024, $logsize));
    }

    public function parse_array($text) {
        $x = explode("\n", $text);
        $rv = [];
        foreach($x as $v){
            $v = trim($v);
            $xx = explode("=", $v);
            if (!$xx[0]) {
                continue;
            }
            $rv[$xx[0]] = $xx[1];
        }
        return $rv;
    }

    public function log($v) {
        if (!$this->log_enabled) {
            return;
        }
        if (is_array($v) || is_object($v)) {
            $v = "\r\n".json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $time = microtime(true);
        $dt = floor($time);
        $micro = sprintf("%03d", round(($time - $dt)*1000));
        $output = $this->form_time_full($dt).".".$micro.": ".$v."\r\n";
        error_log($output, 3, $this->log_file_path);
    }

    public function usergroup($group) {
        return $this->auth->credentials($group);
    }

    public function static_svg($url) {
        if (mb_substr($url, 0, 9) == "/modules/") {
            return "";
        }
        if (mb_substr($url, 0, 7) == "/local/") {
            return "";
        }
        if (strstr($url, "..")) {
            return "";
        }

        $file_name = EMPS_WEBSITE_SCRIPT_PATH.$url;

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_SCRIPT_PATH.$url;

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_PATH_PREFIX.$url;
        $file_name = stream_resolve_include_path($file_name);

        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        $file_name = EMPS_COMMON_PATH_PREFIX.$url;
        $file_name = stream_resolve_include_path($file_name);
        if (file_exists($file_name)) {
            $data = file_get_contents($file_name);
            return $data;
        }

        return "";
    }

    private $md5_shortener = "yenaEFGHIJKLMNOPQRSTUVWXYZDbcdBfghijklmCopqrstuvwxAz0123456789-$";

    public function short_md5($md5) {
        $bin = hex2bin($md5);
        $rv = "";
        $byte_index = 0;
        $src_bit = 0;
        $dst_bit = 0;
        $value = 0;
        $length = 16;

        while (true) {
            $byte = ord($bin[$byte_index]);
            $bit = ($byte >> $src_bit) & 0x1;
            $add = $bit << $dst_bit;

//            echo "Byte #{$byte_index}: {$byte} Bit: {$bit} - Add: {$add}\r\n";
            $value += $add;
            $src_bit++;
            $dst_bit++;
            if ($dst_bit >= 6) {
                $rv .= $this->md5_shortener[$value];
                $value = 0;
                $dst_bit = 0;
            }
            if ($src_bit >= 8) {
                $src_bit = 0;
                $byte_index++;
                if ($byte_index >= $length) {
                    $rv .= $this->md5_shortener[$value];
                    break;
                }
            }
        }
        return $rv;
    }

    public function long_md5($short_md5) {
        $rv = "";
        $byte_index = 0;
        $src_bit = 0;
        $dst_bit = 0;
        $value = 0;
        $length = strlen($short_md5);

        while (true) {
            $char = $short_md5[$byte_index];
            $byte = strpos($this->md5_shortener, $char);
            $bit = ($byte >> $src_bit) & 0x1;
            $add = $bit << $dst_bit;

//            echo "Byte #{$byte_index}: {$byte} Bit: {$bit} - Add: {$add}\r\n";
            $value += $add;
            $src_bit++;
            $dst_bit++;
            if ($dst_bit >= 8) {
                $rv .= chr($value);
                $value = 0;
                $dst_bit = 0;
            }
            if ($src_bit >= 6) {
                $src_bit = 0;
                $byte_index++;
                if ($byte_index >= $length) {
                    break;
                }
            }
        }
        return bin2hex($rv);
    }

    public function join_text($glue, $list) {
        $pts = [];
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            $pts[] = $item;
        }
        return implode($glue, $pts);
    }

    public function first_match($list) {
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            return $item;
        }
        return "";
    }

    function remove_emoji($text) {
        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|'.
                '\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|'.
                '[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|'.
                '[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|'.
                '[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{1F000}-\x{1FEFF}]?|'.
                '[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|'.
                '[\x{1F000}-\x{1F9FF}][\x{FE00}-\x{FEFF}]?|'.
                '[\x{1F000}-\x{1F9FF}][\x{1F000}-\x{1FEFF}]?/u', '', $text);
    }

    public function make_keywords($list) {
        if (!isset($this->hf_words)) {
            $hf_words = "что
тот
быть
весь
это
как
она
они
так
сказать
этот
который
один
еще
такой
только
себя
свое
какой
когда
уже
для
вот
кто
год
мой
или
если
нет
даже
другой
наш
свой
под
где
есть
сам
раз
чтобы
два
там
чем
ничто
потом
очень
при
мог
могли
могут
может
надо
без
теперь
тоже
сейчас
можно
после
место
что
над
три
ваш
несколько
пока
хорошо
более
хотя
всегда
куда
сразу
совсем
об
почти
много
между
про
лишь
однако
чуть
зачем
любой
назад
оно
поэтому
совершенно
точно
среди
иногда
ко
затем
четыре
также
откуда
чтоб
мало
немного
впрочем
разве
против
иной
лучший
вполне
иметь
имеет
имеют
нужно
начать
включает
понятие
нем
нём
нужно
начать
каждое
каждый
каждая
";
            $x = explode("\n", $hf_words);
            $hf = [];
            foreach ($x as $v) {
                $v = trim($v);
                if (!$v) {
                    continue;
                }
                $hf[$v] = true;
            }
            $this->hf_words = $hf;
        }
        $words = [];
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }
            $item = trim(strip_tags($item));
            if (!$item) {
                continue;
            }
            $item = $this->remove_emoji($item);
            $item = preg_replace("#[[:punct:]](?<!-)#", "", $item);
            $item = preg_replace("#[[:space:]]#", " ", $item);
            $x = explode(" ", $item);
            foreach ($x as $v) {
                $v = trim($v);
                if (!$v) {
                    continue;
                }
                if (mb_strlen($v) < 3) {
                    continue;
                }
                $v = mb_strtolower($v);
                if ($this->has_similar_index($this->hf_words, $v, 80)) {
                    continue;
                }
                $key = $this->has_similar_index($words, $v, 80);
                if (!$key) {
                    $words[$v] = 1;
                } else {
                    $words[$key]++;
                }
            }
        }

        arsort($words);
        $i = 0;
        $pts = [];
        foreach ($words as $k => $v) {
            $pts[] = $k;
            $i++;
            if ($i > 10) {
                break;
            }
        }
        $this->last_keywords = $words;
        return implode(", ", $pts);
    }

    public function has_similar_index($words, $word, $target_percent) {
        foreach ($words as $key => $value) {
            $percent = 0;
            similar_text($key,$word,$percent);
            if ($percent >= $target_percent) {
                return $key;
            }
        }
        return false;
    }

    public function conditional_content_length($resp, $size) {
        $range = $_SERVER['HTTP_RANGE'];
        $x = explode("=", $range);
        if ($x[0] == "bytes") {
            $xx = explode("-", $x[1]);
            $start = intval($xx[0]);
            if ($start == 0) {
                $resp->setHeader("Content-Length", $size);
            }
        }
    }

    public function cached_response($seconds) {
        header("Last-Modified: ", time());
        header("Expires: ", date("r", time() + $seconds));
        header("Cache-Control: max-age=" . $seconds);
    }
}
