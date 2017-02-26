<?php

/** 
 * The version-independent base class for EMPS
 *
 * This class contains common functions that are not supposed to differ from one version of EMPS to another.
 * Such functions do not depend on the database engine used. They will make their way to the new EMPS 5.0
 * MongoDB-based version.
 */

class EMPS_Common {
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
	
	public $page_properties = array();
	
	/**
	 * @var $settings_cache Cache array to store website settings
	 */
	private $settings_cache = false;
	
	/**
	 * @var $content_cache Cache array to store content pages
	 */
	private $content_cache = array();
	
	/**
	 * @var $require_cache Cache array to store the resolved paths of files looked up with page_file_name()
	 */
	private $require_cache = array();
	
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
			
	public function __construct(){
		$this->lang = $GLOBALS['emps_lang'];
		$this->lang_map = $GLOBALS['emps_lang_map'];		
	}
	
	public function __destruct(){
		ob_end_flush();
	}	

	/**
	 * Unslash a string prepared by magic_quotes
	 *
	 * @param $a string
	 *
	 * @return string
	 */	
	public function unslash_prepare($a){
		reset($a);
		while(list($n,$v) = each($a)){
			if(is_array($v)){
				$a[$n] = $this->unslash_prepare($v);
			}else{
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
	public function early_init(){
	}
	
	/**
	 * Main initialization procedure
	 *
	 * This will detect the current website, parse the URL for variables, initialize Smarty plugins.
	 */
	public function initialize(){
		if(!$this->cli_mode){
			if(get_magic_quotes_gpc()){
				$_REQUEST = $this->unslash_prepare($_REQUEST);
				$_POST = $this->unslash_prepare($_POST);
				$_GET = $this->unslash_prepare($_GET);
			}	
		}

	
		$this->early_init();
		$this->select_website();
		
		if(!$this->cli_mode){
			$this->parse_path();
			$this->import_vars();
			$this->savevars();
			
			if($_GET['plain']){
				$this->page_property('plain', true);
			}
		}
		
		$plugins = $this->common_module('smarty.plugins.php');
				   
		if(file_exists($plugins)){
			require_once($plugins);
		}	
	}
	
	/**
	 * Set the HTML Content-Types
	 *
	 * This function will ensure that the Content-Type header is set to text/html.
	 */
	public function text_headers(){
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
	public function cut_text($s, $t){
		$i = $t;
		if(mb_strlen($s) <= $t){
			return $s;
		}
		for($i = $t; $i > 0; $i--){
			$c = mb_substr($s, $i, 1);
			if($c==' '){
				return mb_substr($s, 0, $i)." ...";
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
	public function uses_flash(){
		global $smarty;
		if($_SESSION['flash']){
			$smarty->assign("flash", $_SESSION['flash']);
			unset($_SESSION['flash']);
		}
	}
	
	/**
	 * Reset the flash session variable
	 */
	public function noflash(){
		unset($_SESSION['flash']);
	}
	
	/**
	 * Add a flash variable
	 *
	 * @param $code string Named array index (variable name)
	 * @param $value mixed Any value to store in the flash variable (some short value!)
	 */
	public function flash($code, $value){
		$_SESSION['flash'][$code] = $value;
	}
	
	/**
	 * Check if the current module URL should be regarded as 'fast'
	 * 
	 * 'fast' modules are not using authentication and some other modules to boost performance. The list of module names
	 * is defined in the EMPS_FAST constant.
	 */
	public function check_fast(){
		global $pp;
		
		$x = explode(',', EMPS_FAST);
		$skip = false;
		while(list($n,$v) = each($x)){
			if($v == $pp){
				$skip = true;
			}
		}
		if($skip){
			$this->fast = true;
		}		
	}
	
	/**
	 * Called after parsing the URL
	 *
	 * This function, among other things, starts the PHP session.
	 */
	
	public function post_parse(){
		global $pp;
		
		$fn = $this->common_module('config/postparse.php');
		if($fn){
			require_once $fn;
		}
		
		// this website's default content-type is utf-8 HTML
		$this->text_headers();
		
		// these pages should not set the session cookie, they don't need it
		$x = explode(',', EMPS_NO_SESSION);
		$skip = false;
		while(list($n,$v)=each($x)){
			if($v == $pp){
				$skip = true;
			}
		}
		$skip = $this->should_prevent_session();
		if(!$skip){
			if(!$this->is_localhost_request() || $GLOBALS['emps_localhost_mode']){
				session_start();
				if($_SESSION['lsu'] < (EMPS_SESSION_COOKIE_LIFETIME / 30)){
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
	public function changevar($n, $v){
		$this->VA[$n] = $v;
		$GLOBALS[$n] = $v;
	}
	
	/**
	 * Reset stored URL variables
	 *
	 * Clears the global variables whose names are defined in the EMPS_VARS constant. Omits the $lang variable.
	 */
	public function clearvars(){
		$x = explode(",", EMPS_VARS);
		while(list($name, $value) = each($x)){
			if($value == 'lang'){
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
	public function loadvars(){
		$pp = explode(",", EMPS_VARS);
		while(list($name, $value) = each($pp)){
			$GLOBALS[$value] = $this->VA[$value];
		}	
	}
	
	/**
	 * Save URL variables to storage
	 *
	 * Puts the values of URL variables whose names are defined in the EMPS_VARS constant into the $this->VA array property.
	 */
	public function savevars(){
		$x = explode(",", EMPS_VARS);
		while(list($name, $value) = each($x)){
			if(isset($GLOBALS[$value])){
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
	public function page_property($name, $value){
		$this->page_properties[$name] = $value;
	}	
	
	public function copy_properties($code){
	// Load properties from get_content_data for the content item $code and save them as $page_properies
		global $smarty;
		
		$item = $this->get_db_content_item($code);
		$props = $this->get_content_data($item);
		unset($props['_full']);
		
		$this->page_properties = array_merge($this->page_properties, $props);
	}

	/**
	 * Adds a new menu item to $this->spath
	 *
	 * This can be a real menu item or an array prepared by a module script.
	 *
	 * @param $v array Menu item array
	 */
	public function add_to_spath($v){
		if($v['uri']{0} == '#'){
			return false;
		}
		reset($this->spath);
		while(list($n,$cv) = each($this->spath)){
			if(($cv['id'] == $v['id'])){
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
	public function scan_selected(&$menu){
		reset($menu);
		$mr = 0;
		
		$found_one = false;
		
		while(list($n,$v) = each($menu)){
			$obtained_spath = array();
			if($v['sub']){
				$reserve_spath = $this->spath;
				$this->spath = array();
				$res = $this->scan_selected($v['sub']);
				$obtained_spath = $this->spath;
				$this->spath = $reserve_spath;
				$menu[$n]['sub'] = $v['sub'];
				if($res > 0){
					$menu[$n]['ssel'] = $res;
					$menu[$n]['sel'] = $v['sel']= 1;
				}
				if($res > 0) $mr=1;
			}		
			if($v['sel'] > 0) {
				$this->add_to_spath($v);
				foreach($obtained_spath as $spv){
					$this->add_to_spath($spv);
				}
			
				$found_one = true;
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
	function sort_menu($a, $b){
		if($a['ord'] == $b['ord']){
			return 0;
		}
		if($a['ord'] < $b['ord']){
			return -1;
		}else{
			return 1;
		}
	}
	
	/**
	 * Load a menu or submenu from the database
	 *
	 * @param $code Menu code
	 * @param $parent Parent ID
	 */
	public function section_menu($code, $parent){
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
	public function menu_levels($menu, $mlv){
		reset($menu);
		$mlv[] = $menu;
		while(list($n,$v) = each($menu)){
			if($v['sel']>0 && $v['sub']){
				$mlv = $this->menu_levels($v['sub'],$mlv);
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
	public function prepare_menus(){
		global $smarty;
		
		if($this->auth->credentials("admin,author,editor,oper")){
			$menu=$this->section_menu("admin", 0);
			$this->scan_selected($menu);
			$this->menus['admin'] = $menu;
		}
		
		$r = $this->get_setting('handle_menus');
		if(!$r){
			return false;
		}		
		
		$x = explode(',', $r);
		while(list($n,$v) = each($x)){
			unset($menu);
			$xx = explode('/',$v);
			$code = $xx[0];
			$t = $xx[1];
			$menu = $this->section_menu($code, 0);
			$this->scan_selected($menu);
			if($t == 'mlv'){
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
	public function post_init(){
		$this->prepare_menus();		
	}
	
	/**
	 * Pre-controller handler
	 *
	 * Called immediately before a module controller PHP script is called.
	 */
	public function pre_controller(){
		global $pp, $smarty;
		$x = explode('-', $pp);
		if($x[0] == "admin" || $x[0] == "manage"){
			$this->page_property("adminpage", 1);
		}

		$smarty->assign("enum", $this->enum);		
	}
	
	/**
	 * Pre-display handler
	 *
	 * Called immediately before a module view Smarty template is displayed.
	 */
	public function pre_display(){
		global $smarty;
		
		if(!$this->page_properties['title']){
			$this->page_properties['title'] = "";
			while(list($n,$v) = each($this->spath)){
				if($this->page_properties['title'] != ""){
					$this->page_properties['title'] = strip_tags($v['dname']) . " - " . $this->page_properties['title'];
				}else{
					$this->page_properties['title'] = strip_tags($v['dname']);
				}
			}
		}
		
		$this->page_property("year", date("Y", time()));
		
		$smarty->assign("enum", $this->enum);				
		
		$fn = $this->common_module('config/predisplay.php');
		if($fn){
			require_once $fn;
		}
		
		$smarty->assign("spath", $this->spath);
	
		$smarty->assign('page', $this->page_properties);
		$smarty->assign('lang', $this->lang);
		
		$html_lang = $this->lang;
		
		if($html_lang == 'nn'){
			$html_lang = 'ru';
		}
		$smarty->assign("html_lang", $html_lang);
		
		$smarty->assign("df_format", EMPS_DT_FORMAT);
		
		$smarty->assign("current_host", $_SERVER['HTTP_HOST']);
		$smarty->assign("current_uri", $_SERVER['REQUEST_URI']);
		
	}
	
	/**
	 * Parse an enum descriptor string into an enum array
	 *
	 * @param $name Enum name (code)
	 * @param $list Values list string (e.g. '10=Yes,20=No')
	 */
	public function make_enum($name, $list){
		$lst = array();
		$x = explode(";", $list);
		while(list($n,$v) = each($x)){
			$xx = explode("=", $v, 3);
			$e = array();
			$e['code'] = trim($xx[0]);
			$e['value'] = $xx[1];
			$dx = explode(",", $xx[2]);
			while(list($nn,$vv) = each($dx)){
				$e[$vv] = 1;
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
	public function handle_redirect($uri){
	}
	
	/**
	 * Main URL parser
	 *
	 * Parses the current URL to determine if it should be routed to a module or a virtual page.
	 */
	private function parse_path(){

		$uri = $_SERVER["REQUEST_URI"];
		
		$first = substr($uri, 1);
		
		$this->handle_redirect($uri);
	
		if(function_exists("emps_uri_filter")){
			$uri = emps_uri_filter($uri);
		}
	
		$s = explode("?",$uri,2);
	
		$uri = $s[0];
		$uri = str_replace(EMPS_SCRIPT_URL_FOLDER, '', $uri);	// remove initial path from the URI
		
		$this->PLURI = $uri;	
		$this->menu_URI = $uri;			
		
		if($uri{0} == '/') $uri = substr($uri,1);		
		$ouri = $uri;		
		$this->PURI = $ouri;

		$this->savevars();
		$uri=$this->PURI;
		if($uri{strlen($uri)-1}=='/') $uri = substr($uri,0,strlen($uri)-1);
	
		$this->URI = $uri;
		
		$sp = $this->get_setting("startpage");
	
		if(!$this->URI){
			if(!$_SERVER['QUERY_STRING']){
				$this->URI = $sp;
			}
			$GLOBALS['pp'] = $sp;
			$this->page_property('front',1);
		}
		if($vp = $this->page_exists($this->PLURI)){
			// virtual object (CMS database item)
			$this->virtual_path = $vp;
		}elseif($vp = $this->page_exists($this->PLURI.'/')){
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".$this->PLURI.'/');
			exit();
		}else{
			// parse parts of the $ouri as variables from the $RVLIST, make them global
			$xx = explode(",", EMPS_URL_VARS);
			$x = explode("/", $ouri);
			while(list($n, $v) = each($x)){
				if($v == "") continue;
				if($v != '-'){
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
	private function import_vars(){
		$x = explode(",", EMPS_VARS);
		while(list($n,$v) = each($x)){
			if(!isset($GLOBALS[$v])) $GLOBALS[$v] = '';
			if(isset($_GET[$v])) $GLOBALS[$v] = $_GET[$v];
			if(isset($_POST[$v])) $GLOBALS[$v] = $_POST[$v];
		}	
	}	
	
	/**
	 * Check if a virtual page exists in the database
	 *
	 * @param $uri string The full relative URI of the page sought
	 */
	public function page_exists($uri){
		$ra = $this->get_db_content_item($uri);
		if($ra) return $ra;
		return false;
	}

    /**
     * Add the current remote IP address to the black list (or update the timestamps if it already exists)
     *
     *
     */
	public function add_to_blacklist(){
	    $term = 180*24*60*60;
        $ip = $_SERVER['REMOTE_ADDR'];
	    $row = $this->db->get_row("e_blacklist", "ip = '".$ip."'");

        $ur = array();
        $ur['edt'] = time() + $term;
        $ur['adt'] = time();

	    if($row){
	        $update = ['SET' => $ur];
	        $this->db->sql_update_row("e_blacklist", $update, "id = ".$row['id']);
        }else{
	        $ur['ip'] = $ip;
            $update = ['SET' => $ur];
            $this->db->sql_insert_row("e_blacklist", $update);
        }

        $this->service_blacklist();
    }

    /**
     * Check if the current remote IP address is blacklisted
     *
     *
     */
    public function is_blacklisted(){
        $ip = $_SERVER['REMOTE_ADDR'];

        $row = $this->db->get_row("e_blacklist", "ip = '".$ip."'");
        if($row){
            return true;
        }

        return false;
    }

    /**
     * Delete expired items from the black list
     *
     *
     */
    public function service_blacklist(){
	    $this->db->query("delete from ".TP."e_blacklist where edt < ".time());
    }
	
	public function try_page_file_name($page_name, $first_name, $include_name, $type, $path, $lang){
		$fn = $path.'/modules/'.$page_name;
		$exact = false;
		switch($type){
		case 'view':
			if(mb_substr($first_name, 0, 1) == '!'){
				$first_name = mb_substr($first_name, 1);
				$fn .= '/'.$first_name;
				$exact = true;
			}else{
				$fn .= '/'.$first_name.'.'.$lang.'.htm';
			}
			break;
		case 'controller':
			$fn .= '/'.$first_name.'.php';		
			break;
		case 'inc':
			$fn .= '/'.$include_name;
			break;
		}
		if(isset($this->require_cache['try_page_file_name'][$fn])){
			return $this->require_cache['try_page_file_name'][$fn];
		}		
		$fn = stream_resolve_include_path($fn);

		$this->require_cache['try_page_file_name'][$fn] = $fn;
		return $fn;
	}
	
	public function try_template_name($path, $page_name, $lang){
		$fn = $path.'/templates/'.$lang.'/'.$page_name.'.htm';
		if(isset($this->require_cache['try_template_name'][$fn])){
			return $this->require_cache['try_template_name'][$fn];
		}
		$fn = stream_resolve_include_path($fn);

		$this->require_cache['try_template_name'][$fn] = $fn;
		return $fn;
	}
	
	public function page_file_name($page_name, $type){
	// This function controls the naming of files used by the application
		if(isset($this->require_cache['page_file'][$type][$page_name])){
			return $this->require_cache['page_file'][$type][$page_name];
		}
		$opage_name = $page_name;
		if($page_name{0}=='_'){
			$page_name = substr($page_name, 1);
			$page_name = str_replace('-', '/', $page_name);
			if($type == 'inc'){
				$x = explode(',', $page_name, 2);
				$page_name = $x[0];
				$include_name = $x[1];
			}else{
				$x = explode(',', $page_name);
				if(isset($x[1])){
					$page_name = $x[0];
					$first_name = $x[1];
				}else{
					$x = explode('/', $page_name);
					$first_name = array_pop($x);
				}
			}

			$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, $this->lang);
			if(!$fn){
				$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_WEBSITE_SCRIPT_PATH, 'nn');
				if(!$fn){
					$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, $this->lang);
					if(!$fn){
						$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_SCRIPT_PATH, 'nn');
						if(!$fn){
							$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, $this->lang);
							if(!$fn){
								$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_PATH_PREFIX, 'nn');
								if(!$fn){
									$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, $this->lang);
									if(!$fn){
										$fn = $this->try_page_file_name($page_name, $first_name, $include_name, $type, EMPS_COMMON_PATH_PREFIX, 'nn');
									}
								}
							}
						}
					}
				}
			}
		}else{
			$fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, $this->lang);
			if(!$fn){
				$fn = $this->try_template_name(EMPS_WEBSITE_SCRIPT_PATH, $page_name, 'nn');
				if(!$fn){
					$fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, $this->lang);
					if(!$fn){
						$fn = $this->try_template_name(EMPS_SCRIPT_PATH, $page_name, 'nn');
						if(!$fn){
							$fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, $this->lang);
							if(!$fn){
								$fn = $this->try_template_name(EMPS_PATH_PREFIX, $page_name, 'nn');
								if(!$fn){
									$fn = $this->try_template_name(EMPS_COMMON_PATH_PREFIX, $page_name, $this->lang);
									if(!$fn){
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
	
	public function try_common_module_html($path, $file_name, $lang){
		
		$x = explode(".", $file_name);
		$len = mb_strlen($x[count($x)-1], "utf-8");
		if($len <= 3){
			$fn = $path.'/modules/_common/'.$file_name;
		}else{
			$fn = $path.'/modules/_common/'.$file_name.'.'.$lang.'.htm';
		}
		
		if(isset($this->require_cache['common_module_html_try'][$fn])){
			return $this->require_cache['common_module_html_try'][$fn];
		}

		if(!file_exists($fn)){
			$fn = false;
		}

		$this->require_cache['common_module_html_try'][$fn] = $fn;
		return $fn;
	}
	
	public function common_module_html($file_name){
	// This function controls the naming of files used by common modules
		if(isset($this->require_cache['common_module_html'][$file_name])){
			return $this->require_cache['common_module_html'][$file_name];
		}
		$fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH,$file_name,$this->lang);
		if(!$fn){
			$fn = $this->try_common_module_html(EMPS_WEBSITE_SCRIPT_PATH,$file_name,'nn');
			if(!$fn){
				$fn = $this->try_common_module_html(EMPS_SCRIPT_PATH,$file_name,$this->lang);
				if(!$fn){
					$fn = $this->try_common_module_html(EMPS_SCRIPT_PATH,$file_name,'nn');
					if(!$fn){
						$x = explode(".", $file_name);
						$len = mb_strlen($x[count($x)-1], "utf-8");
						if($len <= 3){
							$fn = EMPS_PATH_PREFIX.'/common/'.$file_name;	
							$fn = stream_resolve_include_path($fn);
							if(!$fn){
								$fn = EMPS_COMMON_PATH_PREFIX.'/common/'.$file_name;	
								$fn = stream_resolve_include_path($fn);
							}
						}else{
							$fn = EMPS_PATH_PREFIX.'/common/'.$file_name.'.'.$this->lang.'.htm';	
							$fn = stream_resolve_include_path($fn);
							if(!$fn){
								$fn = EMPS_PATH_PREFIX.'/common/'.$file_name.'.nn.htm';	
								$fn = stream_resolve_include_path($fn);
								if(!$fn){
									$fn = EMPS_COMMON_PATH_PREFIX.'/common/'.$file_name.'.'.$this->lang.'.htm';
									$fn = stream_resolve_include_path($fn);
									if(!$fn){
										$fn = EMPS_COMMON_PATH_PREFIX.'/common/'.$file_name.'.nn.htm';	
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
	
	public function try_common_module($path, $file_name){
		if(isset($this->require_cache['common_module_try'][$path][$file_name])){
			return $this->require_cache['common_module_try'][$path][$file_name];
		}
		$fn = $path.'/modules/_common/'.$file_name;	
		if(!file_exists($fn)){
			$fn = false;
		}
		$this->require_cache['common_module_try'][$path][$file_name] = $fn;
		return $fn;
	}
	
	public function common_module_ex($file_name, $level){
	// This function controls the naming of files used by common modules
		if(isset($this->require_cache['common_module'][$level][$file_name])){
			return $this->require_cache['common_module'][$level][$file_name];
		}

		$fn = $this->try_common_module(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
		if(!$fn || ($level > 0)){
			$fn = $this->try_common_module(EMPS_SCRIPT_PATH, $file_name);
			if(!$fn || ($level > 1)){
				$fn = EMPS_PATH_PREFIX.'/common/'.$file_name;	
				$fn = stream_resolve_include_path($fn);
				if(!$fn){
					$fn = EMPS_COMMON_PATH_PREFIX.'/common/'.$file_name;	
					$fn = stream_resolve_include_path($fn);
				}
			}
		}

		if($fn != false){
			$this->require_cache['common_module'][$level][$file_name] = $fn;
		}
		
		return $fn;
	}
	
	public function common_module($file_name){
		return $this->common_module_ex($file_name, 0);
	}

	public function core_module($file_name){
	    // for now it's a stub
	    return EMPS_COMMON_PATH_PREFIX."/core/".$file_name.".php";
    }
	
	public function try_plain_file($path, $file_name){
		if(isset($this->require_cache['plain_file_try'][$path][$file_name])){
			return $this->require_cache['plain_file_try'][$path][$file_name];
		}
		$fn = $path.$file_name;	
		if(!file_exists($fn)){
			$fn = false;
		}
		$this->require_cache['plain_file_try'][$path][$file_name] = $fn;
		return $fn;
	}	
	
	public function plain_file($file_name){
	// This function finds a file in the websites' folders
	// (first the primary website, then the base website) and then in the main EMPS folder
		if(isset($this->require_cache['plain_file'][$file_name])){
			return $this->require_cache['plain_file'][$file_name];
		}
		$fn = $this->try_plain_file(EMPS_WEBSITE_SCRIPT_PATH, $file_name);
		if(!$fn){
			$fn = $this->try_plain_file(EMPS_SCRIPT_PATH, $file_name);
			if(!$fn){
				$fn = EMPS_PATH_PREFIX.$file_name;	
				$fn = stream_resolve_include_path($fn);
				if(!$fn){
					$fn = EMPS_COMMON_PATH_PREFIX.$file_name;	
					$fn = stream_resolve_include_path($fn);
				}
			}
		}

		if($fn != false){
			$this->require_cache['plain_file'][$file_name] = $fn;
		}
		
		return $fn;
	}	
	
	public function not_found(){
		global $smarty;
		header("HTTP/1.0 404 Not Found");
		$smarty->assign("main_body", "db:page/notfound");
		$this->page_property("plain", $this->get_setting("plain_404"));
		$smarty->assign('page',$this->page_properties);
		$smarty->display("db:main");	
	}
	
	public function deny_access($reason){
		global $smarty;
		
		$this->retry_for_session();
		$smarty->assign($reason, 1);
	}

	public function retry_for_session(){
		if($this->should_prevent_session()){
			$retry = intval($_GET['retry']);
			if($retry < 3) {
				$retry++;
				$this->redirect_page("./?retry=".$retry);exit();
			}
		}
	}
	
	public function add_to_menu(&$menu,$variable,$code,$name){
	// Add a code/name pair to a $menu, the selection of a menu item is tracked by $variable
		$current_value=$GLOBALS[$variable];
		$e=array();
		$e['code']=$code;
		$GLOBALS[$variable]=$code;
		$e['link']=$this->elink();
		$this->loadvars();
		if($current_value==$code){
			$e['sel']=1;
		}
		$e['name']=$name;
		$menu[]=$e;
	}
	
	public function prepare_pad_menu($pads,$variable){
		$menu = array();
		if(!is_array($pads)){
			return false;
		}
		while(list($n,$v)=each($pads)){
			$this->add_to_menu($menu,$variable,$n,$v);
		}
		return $menu;
	}
	
	public function count_pages($total){
	// New pagination function 
		global $perpage;
		
		if($total<$GLOBALS[$this->page_var] && !$this->no_autopage && $total>0){
			$GLOBALS[$this->page_var] = 0;
			$this->redirect_elink();
		}
		
		if(!$perpage){
			$perpage=10;
		}
		$a=array();
		
		if(is_numeric($GLOBALS[$this->page_var])){
			if($GLOBALS[$this->page_var]>=$total){
				$GLOBALS[$this->page_var]=$total-$perpage;
				if($GLOBALS[$this->page_var]<0){
					$GLOBALS[$this->page_var]=0;
				}
				$this->savevars();
			}
		}
		
		$cs=$GLOBALS[$this->page_var];
		$f=ceil($total/$perpage);
		
		$cf=$f;
		$scl=floor($GLOBALS[$this->page_var]/$perpage)-4;
		if($scl<0) $scl=0;
		
		if($f>7) $f=7;
		
		if($f+$scl>$cf) $scl=$cf-$f;
		
		if($scl<0) $scl=0;
		
		$GLOBALS[$this->page_var]=0;
		$a['first']['start']=$GLOBALS[$this->page_var];
		$a['first']['page']=1;
		$a['first']['link']=$this->clink($this->page_clink);
		$pl=array();
		
		$selitem=-1;
		
		for($i=0;$i<$f;$i++){
			$GLOBALS[$this->page_var]=($i+$scl)*$perpage;
			
			$pl[$i]=array();
			
			$sel=false;
			if($GLOBALS[$this->page_var]==$cs){
				$pl[$i]['sel']=true;
				$sel=true;
				$selitem=$i;
			}
	
			$pl[$i]['start']=$GLOBALS[$this->page_var];
			$pl[$i]['link']=$this->clink($this->page_clink);
			$pl[$i]['page']=($i+$scl+1);
			
			$GLOBALS[$this->page_var]++;
			
			$pl[$i]['fi']=$GLOBALS[$this->page_var]+0;
	
			$res=($GLOBALS[$this->page_var]+$perpage-1);
			
			if($res>$total) $res=$total;
			
			$pl[$i]['li']=$res+0;
			$pl[$i]['count']=$res-$GLOBALS[$this->page_var]+1;
			
			if($pl[$i]['sel']){
				$a['cur']=$pl[$i];
			}
			
		}
		
		$GLOBALS[$this->page_var]=($cf-1)*$perpage;
		
		if($pl[$i-1]['start']==$GLOBALS[$this->page_var]){
			$a['last']=$pl[$i-1];
		}else{
			$a['last']=array();
			$a['last']['start']=$GLOBALS[$this->page_var];
			$a['last']['link']=$this->clink($this->page_clink);
			$a['last']['page']=$cf;
		}
		
		$npl=array();
		for($i=0;$i<$f;$i++){
			$npl[$i]=array_slice($pl[$i],0);
			if($i>0){
				$npl[$i]['prev']=array_slice($pl[$i-1],0);
			}else{
				$npl[$i]['prev']=$a['last'];
			}
			if($i<$f-1){
				$npl[$i]['next']=array_slice($pl[$i+1],0);
			}else{
				$npl[$i]['next']=$a['first'];
			}
		}
		
		if($selitem!=-1){
			$a['prev']=$npl[$selitem]['prev'];	
			$a['next']=$npl[$selitem]['next'];
		}
		
		$GLOBALS[$this->page_var]=$cs;
		
		$a['pl']=$npl;
		$a['count']=count($npl);
		$a['total']=$total;
		
		return $a;
	}

	public function clink($a){
	// Make up a link with the current variables plus another query part component (e.g. "x=1")
		$l=$this->elink();
		if($a){
			if(strstr($l,"?")){
				$l.="&".$a;
			}else{
				$l.="?".$a;
			}
		}
		return $l;
	}
	
	public function xrawurlencode($vle){
	// rawurlencode that doesn't encode hyphens
		$v=rawurlencode($vle);
		$v=str_replace("%2F","-",$v);
		$v=str_replace("%2C",",",$v);		
		return $v;
	}
	
	public function elink(){
	// Make up an internal link with the variables
		$x=explode(",",EMPS_URL_VARS);
		$rlist=array();
		while(list($n,$v)=each($x)){
			$rlist[$v]=$GLOBALS[$v];
		}
		
		$t="";$tc="";
		reset($x);
		while(list($n,$v)=each($x)){
			$v=$this->xrawurlencode($GLOBALS[$v]);
			if(!$v){
				$tc.="/-";
			}else{
				$t.=$tc;
				$t.="/$v";
				$tc="";
			}
		}
		$t.="/";

		$s=false;
		$xx=explode(",",EMPS_VARS);
		while(list($name,$value)=each($xx)){
			if($GLOBALS[$value]=="") continue;
			if($rlist[$value]!="") continue;
			if($s) $t.="&"; else $t.="?";
			$s=true;
			$t.=$value."=".rawurlencode($GLOBALS[$value]);
		}	
		return $t;
	}
	
	public function print_pages($found){
		global $smarty;
		
		$pages = $this->count_pages($found);		
		$smarty->assign("pages",$pages);
		return $smarty->fetch("db:page/paginator");
	}
	
	public function form_time($dt){
		return date("d.m.Y H:i",$dt+EMPS_TZ_CORRECT*60*60);
	}

	public function form_time_full($dt){
		return date("d.m.Y H:i:s",$dt+EMPS_TZ_CORRECT*60*60);
	}

	public function get_log_time(){
		$mt = microtime();
		$x = explode(' ',$mt,2);
		return date("d.m.Y H:i:s",$x[1]+EMPS_TZ_CORRECT*60*60).sprintf(':%d',$x[0]*1000);
	}

	public function form_date($dt){
		return date("d.m.Y",$dt+EMPS_TZ_CORRECT*60*60);
	}
	
	public function parse_time($v){
		$p=explode(" ",$v);
		$d=explode(".",$p[0]);
		$mon=intval($d[1]);
		$day=intval($d[0]);
		$year=intval($d[2]);
		if(!$p[1]){
			$p[1]='12:00:00';
		}
		
		$t=explode(":",$p[1]);
		$hour=intval($t[0]);
		$min=intval($t[1]);
		$sec=intval($t[2]);
		$dt=mktime($hour,$min,$sec,$mon,$day,$year)-EMPS_TZ_CORRECT*60*60;
	
		return $dt;
	}

	public function redirect_page($page){
		header("Location: ".$page);
	}
	
	public function redirect_elink(){
		$this->redirect_page($this->elink());
	}

	public function kill_flood($txt,$max){
		$l=strlen($txt);
		$res="";
		$intag=false;
		for($i=0;$i<$l;$i++){
			$c=$txt{$i};
	
			if($c=='<') $intag=true;
			if($c=='>'){
				$intag=false;
				$res.=$c;continue;
			}
	
			if(($c==' ' || $c=='\n' || $c=='\t') && (!$intag)){
				$cnt=0;
			}else{
				if(!$intag){
					$cnt++;
				}
			}
			
			if(($cnt>$max) && (!$intag)){
				$cnt=0;$res.=" ";
			}
			$res.=$c;
		}
		return $res;
	}
	
	public function check_required($arr,$list){
	// Check if $arr contains values named with comma-separated values in the $list. If an item from $list
	// does not exist in the $arr, it is added to the $err array so that Smarty could know which fields
	// are missing: style="field {{if $err.some_value}}error{{/if}}"
		$x=explode(",",$list);
		$err=array();
		while(list($n,$v)=each($x)){
			if(!$arr[$v]){
				$err[]=$v;
			}else{
				if(is_array($arr[$v])){
					if(!$arr[$v][0] && count($arr[$v])==1){
						$err[]=$v;
					}
				}
			}
		}
		return $err;
	}

	public function partial_array($arr,$list){
		$x=explode(",",$list);
		$parr=array();
		while(list($n,$v)=each($x)){
			if($arr[$v]){
				$parr[$v]=$arr[$v];
			}
		}
		return $parr;
	}
	
	public function utf8_urldecode($str){
		$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
		return html_entity_decode($str,null,'UTF-8');;
	}	
	
	public function ensure_protocol($protocol){
		$addr=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if($protocol=='https'){
			if($_SERVER['HTTPS']!='on'){
				header("Location: https://".$addr);
				exit();
			}			
		}elseif($protocol == 'http'){
			if($_SERVER['HTTPS']=='on'){
				header("Location: http://".$addr);
				exit();
			}
		}
	}
	
	public function is_https(){
		if($_SERVER['HTTPS'] == 'on'){
			return true;
		}
		return false;
	}
	
	public function load_enums_from_file_ex($file){
		if(file_exists($file)){
			$data = file_get_contents($file);
			$x = explode("\n",$data);
			while(list($n,$v)=each($x)){
				$v=trim($v);
				$m = explode(':',$v,2);
				$name = trim($m[0]);
				$value = trim($m[1]);
				if($name && $value){
					$this->make_enum($name,$value);
				}
			}
			$this->enums_loaded = true;
		}
	}
	
	public function load_enums_from_file(){
		$file_list = array();
		for($i = 2; $i >= 0; $i--){
			$file = $this->common_module_ex("config/enum.nn.txt", $i);
			if(!isset($file_list[$file])){
//				echo $file."<br/>";
				$this->load_enums_from_file_ex($file);		
				$file_list[$file] = true;
			}
			$file = $this->common_module_ex("config/enum.".$this->lang.".txt", $i);
			if(!isset($file_list[$file])){
//				echo $file."<br/>";
				$this->load_enums_from_file_ex($file);
				$file_list[$file] = true;
			}
		}
	}
	
	public function enum_val($enum, $code){
		$lst = $this->enum[$enum];
		while(list($n,$v)=each($lst)){
			if($v['code']==$code){
				return $v['value'];
			}
		}
		return false;
	}

	public function inflection($value){
		return $this->infliction($value);
	}
	
	public function infliction($value){
		$h=floor(($value%100)/10);
		$d=$value%10;
		
		if($d==1){
			if($h==1){
				return 5;
			}else{
				return 1;
			}
		}
		if($d>=2 && $d<=4){
			if($h==1){
				return 5;
			}else{
				return 2;
			}
		}
		
		return 5;
	}

	public function traceback(Exception $e) {
		$o = "";
		
		$trace = $e->getTrace();
		
		$i = count($trace);
		foreach($trace as $v){
			$o .= "#".$i.": at line ".$v['line']." of ".$v['file'].", ".$v['class'].$v['type'].$v['function']."\r\n";
			$i--;
		}
		
		return $o;
	}
	
	public function expire_guess(){
		$dt = time();
		if($this->last_modified > 0){
			$past = time() - $this->last_modified;
			$mins = floor($past / 60);
			$hours = floor($mins / (60));
			$days = floor($hours / 24);
			if($days > 7){
				return time() + 7*24*60*60;
			}
			if($days > 1){
				return time() + 2*24*60*60;
			}
			if($hours > 12){
				return time() + 12*60*60;
			}
			if($hours > 6){
				return time() + 6*60*60;
			}
			if($hours > 2){
				return time() + 2*60*60;
			}
			if($hours > 1){
				return time() + 60*60;
			}
			if($mins > 30){
				return time() + 30*60;
			}
			if($mins > 15){
				return time() + 15*60;
			}
			return time() + 60;
		}
		return $dt;
	}
	
	public function handle_modified(){
		if($this->last_modified > 0){
			header("Expires: ".date("r", $this->expire_guess()));
			header("Last-Modified: ".date("r", $this->last_modified));

			$if_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
			if($if_modified){
				$if_dt = strtotime($if_modified);
				if($this->last_modified <= $if_dt){
					header("HTTP/1.1 304 Not Modified");
					exit();
				}
			}
		}
	}
	
	public function should_prevent_session(){
		global $emps_bots, $emps_just_set_cookie;
		
		if($this->is_localhost_request()){
			return false;
		}
		
		if(!$_SERVER['HTTP_USER_AGENT']){
			return true;
		}
		$ua = $_SERVER['HTTP_USER_AGENT'];
		reset($emps_bots);
		foreach($emps_bots as $bot){
			if(strpos($ua, $bot) != false){
				return true;
			}
		}
		if(!$emps_just_set_cookie){
			if(!isset($_COOKIE['EMPS'])){
				return true;
			}
		}else{
			return true;
		}
		return false;
	}
	
	public function normalize_url(){
		$uri = $_SERVER['REQUEST_URI'];
		$x = explode("?", $uri, 2);
		$uri = $x[0];
		$elink = $this->elink();
		if($uri != $elink){
			$this->redirect_elink();exit();
		}
	}
	
	public function in_list($val, $list){
		$x = explode(",", $list);
		foreach($x as $v){
			if($v == $val){
				return true;
			}
		}
		return false;
	}

	public function copy_values(&$target, $source, $list){
		$x = explode(",", $list);
		foreach($x as $v){
			$v = trim($v);
			$xx = explode(":", $v);
			$v = trim($xx[0]);
			if(isset($source[$v])){
				$target[$v] = $source[$v];
			}
		}
	}

	public function transliterate($c){
		if($c == '0'){
			return $c;
		}
		$src="A.B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z.".
		"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z.".
		"1.2.3.4.5.6.7.8.9.0.А.Б.В.Г.Д.Е.Ё.Ж.З.И.Й.К.Л.М.Н.О.П.Р.С.Т.У.Ф.Х.Ц.Ч.Ш.Щ.Ъ.Ы.Ь.Э.Ю.Я.".
				"а.б.в.г.д.е.ё.ж.з.и.й.к.л.м.н.о.п.р.с.т.у.ф.х.ц.ч.ш.щ.ъ.ы.ь.э.ю.я";
		$dest=	"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z.".
			"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z.".
				"1.2.3.4.5.6.7.8.9.0.a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya.".
				"a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya";
		if(!$this->tl_array){
			$x = explode(".", $src);
			$y = explode(".", $dest);
			$l = count($x);
			$this->tl_array = array();
			for($i=0;$i<$l;$i++){
				$this->tl_array['_'.$x[$i]]=$y[$i];
			}
		}
		if($this->tl_array['_'.$c]){
			return $this->tl_array['_'.$c];
		}
		
		if($c==' ' || $c=='-' || $c=='_' || $c==':' || $c=='*'){
			return '-';
		}
		
		if($c=='\'' || $c=='"'){
			return "";
		}
		
		if($c==',' || $c==';'){
			return ',';
		}
		
		return '.';
	}
	
	public function transliterate_url($source){
		$s = $source;
		$t = "";
		$l = mb_strlen($s);
		$c = ''; $pc = '';
		for($i=0; $i < $l; $i++){
			$c = mb_substr($s, $i, 1, "UTF-8");
			$tc = $this->transliterate($c);
			if(($pc=='-' || $pc=='.' || $pc==',') && ($tc=='-' || $tc=='.' || $tc==',')){
				continue;
			}
			$pc = $tc;
			$t .= $tc;
		}
		$l = mb_strlen($t);
		$lc = mb_substr($t, $l-1, 1);
		if($lc=='.' || $lc==',' || $lc=='-'){
			$t = mb_substr($t, 0, $l-1);
		}
		return $t;
	}

	public function is_localhost_request(){
		if($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']){
			return true;
		}
		return false;
	}

	public function json_response($response){
		global $emps;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");

		echo json_encode($response, $this->json_options);
	}

	function indexes_list($ar){
		reset($ar);
		$lst="";
		while(list($n,$v)=each($ar)){
			if($lst!=""){
				$lst.=", ";
			}
			$lst.=$n;
		}
		return $lst;
	}

	public function recaptcha_check(){
		$response = $_POST['g-recaptcha-response'];
		if(!$response){
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
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			)
		);

		$context  = stream_context_create($opts);

		$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
		
		$data = json_decode($result, true);
		$this->last_recaptcha_result = $data;
		if($data['success']){
			return true;
		}
		
		return false;
	}
	
	public function referer_vars(){
		$referer = $_SERVER['HTTP_REFERER'];
		
		$x = explode(EMPS_SCRIPT_WEB, $referer);
		if($x[0] == "" && isset($x[1])){
			$xx = explode(",", EMPS_URL_VARS);
			$uri = mb_substr($x[1], 1);
			$x = explode("/", $uri);
			while(list($n, $v) = each($x)){
				if($v == "") continue;
				if($v != '-'){
					$GLOBALS[$xx[$n]] = urldecode($v);
				}
			}	
		}
	}

}

?>