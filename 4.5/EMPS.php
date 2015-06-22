<?php
// EMPS MULTI-WEBSITE ENGINE
// VERSION 4.5 / 01-2015 / COMMON
// "COMMON" means it's the first EMPS to be placed in an include_path folder to be shared by many EMPS projects.

// Each EMPS project can host a set of several websites sharing common PHP models and processors, but having, 
// for example, distinct HTML designs, or custom modules not present in the main project.

// The general class autoloading function. Not needed by EMPS, but could be neccessary for 3rd-party PHP classes.
// Will search for class files in the include path folders.
spl_autoload_register(function ($name) {
	$fn = $name.".php";
	$f = stream_resolve_include_path($fn);
	if($f === false){
		return false;
	}
	require_once $f;
	return true;
});

require_once "EMPS/4.5/common/config/general.php";

// Main EMPS Class

class EMPS {
	public $db;
	public $cas;
	public $p;	
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
	
	private $settings_cache = false;
	private $content_cache = array();
	
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
	
	public $require_cache = array();
	
	public $last_modified = 0;
	
	public $cli_mode = false;
	
	public $tl_array = array();
			
	public function __construct(){
		
		$this->lang = $GLOBALS['emps_lang'];
		$this->lang_map = $GLOBALS['emps_lang_map'];		
	}
	
	public function __destruct(){
		unset($this->db);
		unset($this->p);		
		ob_end_flush();
	}	
	
	public function early_init(){
		$this->db = new EMPS_DB;
		if(isset($GLOBALS['emps_cassandra_config'])){		
			$this->cas = new EMPS_Cassandra;		
		}
		$this->p = new EMPS_Properties;	
		if(!$this->fast){
			$this->auth = new EMPS_Auth;			
		}
		
		$this->p->db = $this->db;
		
		$this->db->query("SET SESSION sql_mode=''");				
	}
	
	public function unslash_prepare($a){
	// Unslash a nested array (remove slashes)
		reset($a);
		while(list($n,$v)=each($a)){
			if(is_array($v)){
				$a[$n]=$this->unslash_prepare($v);
			}else{
				$a[$n]=stripslashes($v);
			}
		}
		reset($a);
		return $a;
	}
	
	public function initialize(){
		if(!$this->cli_mode){
			if(get_magic_quotes_gpc()){
				$_REQUEST=$this->unslash_prepare($_REQUEST);
				$_POST=$this->unslash_prepare($_POST);
				$_GET=$this->unslash_prepare($_GET);
			}	
		}
		
		$this->early_init();
		$this->select_website();
		
		if(!$this->cli_mode){
			$this->parse_path();
			$this->import_vars();
			$this->savevars();
			
			if($_GET['plain']){
				$this->page_property('plain',true);
			}
		}
		
		$plugins = $this->common_module('smarty.plugins.php');
				   
		if(file_exists($plugins)){
			require_once($plugins);
		}	
	}
	
	public function text_headers(){
	// Specify the script's output standard content type
		header("Content-Type: text/html; charset=utf-8");
	}
	
	public function cut_text($s,$t){
		$i=$t;
		if(mb_strlen($s)<=$t) return $s;
		for($i=$t;$i>0;$i--){
			$c=mb_substr($s,$i,1);
			if($c==' '){
				return mb_substr($s,0,$i)." ...";
			}
		}
		return "";
	}
	
	// Flash is a session variable that is only used once - in the next request
	public function uses_flash(){
		global $smarty;
		if($_SESSION['flash']){
			$smarty->assign("flash",$_SESSION['flash']);
			unset($_SESSION['flash']);
		}
	}
	
	public function noflash(){
		unset($_SESSION['flash']);
	}
	
	public function flash($code,$value){
		$_SESSION['flash'][$code]=$value;
	}
	
	public function check_fast(){
		global $pp;
		
		$x = explode(',', EMPS_FAST);
		$skip = false;
		while(list($n,$v)=each($x)){
			if($v == $pp){
				$skip = true;
			}
		}
		if($skip){
			$this->fast = true;
		}		
	}
	
	public function post_parse(){
		global $pp;
		
		require_once $this->common_module('config/postparse.php');			
		
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
			session_start();
			if($_SESSION['lsu'] < (EMPS_SESSION_COOKIE_LIFETIME / 30)){
				$_sess_name = session_name();
				$_sess_id = session_id();
				setcookie($_sess_name, $_sess_id, time() + EMPS_SESSION_COOKIE_LIFETIME, "/");
				$_SESSION['lsu'] = time();
			}
		}
	}
	
	public function changevar($n,$v){
	// Change a variable in the $this->VA
		$this->VA[$n]=$v;
		$GLOBALS[$n]=$v;
	}
	
	public function clearvars(){
	// $this->VA still contains the tracked vars, but we can clear the $GLOBALS of these vars. Except $lang.
		$x=explode(",",EMPS_VARS);
		while(list($name,$value)=each($x)){
			if($value=='lang') continue;
			$GLOBALS[$value]="";
		}	
	}
	
	public function loadvars(){
	// Load the variables from the $this->VA backup array to $GLOBALS
		$pp=explode(",",EMPS_VARS);
		while(list($name,$value)=each($pp)){
			$GLOBALS[$value]=$this->VA[$value];
		}	
	}
	
	public function savevars(){
	// Populate $this->VA with values from $GLOBALS that match the EMPS_VARS
		$x=explode(",",EMPS_VARS);
		while(list($name,$value)=each($x)){
			if(isset($GLOBALS[$value])){
				$this->VA[$value]=$GLOBALS[$value];
			}
		}	
	}	
	
	public function page_property($name,$value){
	// The function to set page properties from the script
		$this->page_properties[$name]=$value;
	}	

	public function add_to_spath($v){
		reset($this->spath);
//		dump($v);
		while(list($n,$cv)=each($this->spath)){
			if(($cv['id'] == $v['id'])){
				reset($this->spath);
				return false;
			}
		}
//		echo "adding";
//		dump($v);
		reset($this->spath);
		$this->spath[]=$v;
		return true;
	}
	
	public function scan_selected(&$menu){
	// Scan a menu for selected items and populate $spath
		reset($menu);
		$mr=0;
		
		$found_one = false;

		while(list($n,$v)=each($menu)){
			if($v['sub']){
				$res=$this->scan_selected($v['sub']);
				$menu[$n]['sub']=$v['sub'];
				if($res>0){
					$menu[$n]['ssel']=$res;
				}
				if($res>0) $mr=1;
			}
			if($v['sel']>0) {
				$this->add_to_spath($v);
				$found_one = true;
				$mr=1;
			}
			
		}
		
		reset($menu);
		while(list($n,$v)=each($menu)){
			if(!$found_one){
				if($v['ssel']>0) {
					$menu[$n]['sel'] = $v['ssel'];
					$this->add_to_spath($v);
					$mr=1;
				}
			}
		}
		return $mr;
	}	
	
	public function section_menu_ex($code,$parent,$default_parent){
	// Load the menu "grp=$code" and return it as a nested array (if subenus are present)
		$menu=array();
		
		$use_context = $this->website_ctx;
		
		$query = 'select * from '.TP."e_menu where parent=$parent and context_id=".$use_context." and grp='$code' and enabled=1 order by ord asc";
		$r = $this->db->query($query);
		
//		echo $query."<br/>";

		$mlst = array();
		while($ra = $this->db->fetch_named($r)){
			$mlst[] = $ra;
		}

		if($parent==0 || $default_parent){
			$use_parent = $parent;
			if($default_parent){
				$use_parent = $default_parent;
			}
			$q = 'select * from '.TP."e_menu where parent=$use_parent and context_id=".$this->default_ctx." and grp='$code' and enabled=1 order by ord asc";

			$r = $this->db->query($q);
			$dlst = array();
			while($ra=$this->db->fetch_named($r)){
				$ra['default_id'] = $ra['id'];
				$dlst[]=$ra;
			}
			$ndlst=array();
			while(list($n,$v)=each($dlst)){
				reset($mlst);
				$add=true;
				while(list($nn,$vv)=each($mlst)){
					if($vv['uri']==$v['uri'] && $vv['grp']==$v['grp']){
						$mlst[$nn]['default_id'] = $v['id'];
						$add=false;
					}
				}
				if($add){
					$ndlst[]=$v;
				}
			}
			if($ndlst){
				reset($ndlst);
				while(list($nn,$vv)=each($ndlst)){
					$mlst[]=$vv;
				}
				
				uasort($mlst, array($this, 'sort_menu'));
			}
		}
		reset($mlst);
		while(list($n,$ra) = each($mlst)){
			$md = $this->get_menu_data($ra);
			
			$ra['link'] = $ra['uri'];
			
			if(!$md['name']){
				$use_name = $p;
			}else{
				if($md['name$'.$this->lang]){
					$use_name = $md['name$'.$this->lang];	
				}else{
					$use_name = $md['name'];
				}
			}
			
			$ra['dname'] = $use_name;
			
			if($md['width']){
				$ra['width'] = $md['width'];
			}
	
			if(!$md['regex']){
				if($ra['uri'] == $this->menu_URI){
					$ra['sel'] = 1;
				}else{
					if($ra['uri']){
						$x = explode($ra['uri'],$this->menu_URI);
						if($x[0] == '' && $x[1] != ''){
							$ra['sel'] = 1;
						}
					}
				}
			}
			
			if($md['regex']){
				if(preg_match('/'.$md['regex'].'/',$this->menu_URI)){
					$ra['sel']=1;
				}
			}
			
			if($md['grant']){
				if(!$this->auth->credentials($md['grant'])) continue;
			}
	
			if($md['hide']){
				if($this->auth->credentials($md['hide'])) continue;
			}
			
			if($md['nouser']){
				if($this->auth->USER_ID) continue;
			}
	
			$smenu = $this->section_menu_ex($code,$ra['id'],$ra['default_id']);
	
			$ra['sub'] = $smenu;
			$ra['md'] = $md;
			$menu[] = $ra;
		}
		return $menu;
	
	}
	
	function sort_menu($a, $b){
		if($a['ord']==$b['ord']){
			return 0;
		}
		if($a['ord']<$b['ord']){
			return -1;
		}else{
			return 1;
		}
	}
	
	public function section_menu($code,$parent){
		return $this->section_menu_ex($code,$parent,0);
	}
	
	public function menu_levels($menu,$mlv){
	// Create the menu levels: Top menu (0), then selected submenu (1), then selected sub-submenu (2), etc.
	// Used to make the popup-menu for the current page
		reset($menu);
		$mlv[]=$menu;
		while(list($n,$v)=each($menu)){
			if($v['sel']>0 && $v['sub']){
				$mlv=$this->menu_levels($v['sub'],$mlv);
				break;
			}
		}
		return $mlv;
	}
		
	public function prepare_menus(){
		global $smarty;
		
		if($this->auth->credentials("admin,author,editor,oper")){
			$menu=$this->section_menu("admin",0);
			$this->scan_selected($menu);
			$this->menus['admin']=$menu;
		}
		
		$r = $this->get_setting('handle_menus');
		if(!$r){
			return false;
		}		
		
		$x = explode(',',$r);
		while(list($n,$v)=each($x)){
			unset($menu);
			$xx=explode('/',$v);
			$code=$xx[0];
			$t=$xx[1];
			$menu=$this->section_menu($code,0);
			$this->scan_selected($menu);
			if($t=='mlv'){
				$mlv=array();
				$mlv=$this->menu_levels($menu,$mlv);
				$this->mlv[$code]=$mlv;
			}
			$this->menus[$code]=$menu;
		}
		
		$smarty->assign("menus",$this->menus);
		$smarty->assign("mlv",$this->mlv);
		return true;
	}
	
	public function post_init(){
		$this->prepare_menus();		
		
	}
	
	public function pre_controller(){
		global $pp,$smarty;
		$x=explode('-',$pp);
		if($x[0]=="admin" || $x[0]=="manage"){
			$this->page_property("adminpage",1);
		}

		$smarty->assign("enum",$this->enum);		
	}
		
	public function pre_display(){
		global $smarty;
		
		if(!$this->page_properties['title']){
			$this->page_properties['title']="";
			while(list($n,$v)=each($this->spath)){
				if($this->page_properties['title']!=""){
					$this->page_properties['title'].=" - ";
				}
				$this->page_properties['title'].=strip_tags($v['dname']);
			}
		}
		
		$smarty->assign("enum",$this->enum);				
		
		require_once $this->common_module('config/predisplay.php');
		
		$smarty->assign("spath",$this->spath);
	
		$smarty->assign('page',$this->page_properties);
		$smarty->assign('lang',$this->lang);
		
		$html_lang = $this->lang;
		
		if($html_lang == 'nn'){
			$html_lang = 'ru';
		}
		$smarty->assign("html_lang",$html_lang);
		
		$smarty->assign("df_format",EMPS_DT_FORMAT);
		
		$smarty->assign("current_host",$_SERVER['HTTP_HOST']);
		$smarty->assign("current_uri",$_SERVER['REQUEST_URI']);
		
	}
	
	public function make_enum($name,$list){
		$lst=array();
		$x=explode(";",$list);
		while(list($n,$v)=each($x)){
			$xx=explode("=",$v,3);
			$e=array();
			$e['code']=trim($xx[0]);
			$e['value']=$xx[1];
			$dx=explode(",",$xx[2]);
			while(list($nn,$vv)=each($dx)){
				$e[$vv]=1;
			}
			$lst[]=$e;
		}
		$this->enum[$name]=$lst;
	}
	
	public function save_setting($code,$value){
		$x=explode(':',$code);
		$name=$x[0];
		$a=array($name=>$value);
		$this->p->save_properties($a,$this->website_ctx,$code);
	}
	
	public function get_setting($code){
	// Get a fine-tuning setting
		if(!is_array($this->settings_cache)){
			$default_settings=$this->p->read_properties(array(),$this->default_ctx);			
			if(!$default_settings){
				$default_settings = array();
			}
			$website_settings=$this->p->read_properties(array(),$this->website_ctx);
			if(!$website_settings){
				$website_settings = array();
			}
			if(!$default_settings['_full']){
				$default_settings['_full']=array();
			}
			if(!$website_settings['_full']){
				$website_settings['_full']=array();
			}			
			$website_settings['_full']=array_merge($default_settings['_full'],$website_settings['_full']);
			$this->settings_cache=array_merge($default_settings,$website_settings);
//			dump($this->settings_cache);
		}
		return $this->settings_cache[$code];
	}
	
	public function website_by_host($hostname){
		$website = $this->db->get_row("e_websites","'".$this->db->sql_escape($hostname)."' regexp hostname_filter or hostname = '".$this->db->sql_escape($hostname)."'");
		if($website){
//			dump($website);
			$this->current_website = $website;
			if($website['lang']){
				$this->lang = $website['lang'];
			}
			return $website['id'];
		}
		return 0;
	}
	
	private function select_website(){
		// URL parser to decide which website is active
		$hostname = $_SERVER['SERVER_NAME'];
		$this->default_ctx = $this->p->get_context(1,1,0);
		$website_id = $this->website_by_host($hostname);
		if($website_id){
			if($this->current_website['status']==100){
				$this->website_ctx = $this->default_ctx;
			}else{
				$this->website_ctx = $this->p->get_context(DT_WEBSITE,1,$website_id);
			}
		}else{
			$this->website_ctx = $this->default_ctx;
		}
//		echo "ctx: ".$this->website_ctx;
	}
	
	public function base_url_by_ctx($website_ctx){
		$ctx = $this->db->get_row("e_contexts","id = ".$website_ctx);
		if($ctx){
			if($ctx['ref_type'] == DT_WEBSITE){
				$website = $this->db->get_row("e_websites","id=".$ctx['ref_id']);
				if($website){
					return "http://".$website['hostname'];
				}
			}
		}
		return EMPS_SCRIPT_WEB;
	}
	
	public function not_default_website(){
		global $smarty;
		if($this->current_website['status']==100){
			if($this->website_ctx == $this->default_ctx){
				$this->deny_access('WebsiteNeeded');
				
				$r = $this->db->query("select * from ".TP."e_websites where status = 50 and pub = 10 and parent = ".$this->current_website['id']." order by hostname asc");
				$lst=array();
				while($ra = $this->db->fetch_named($r)){
					$lst[]=$ra;
				}
				
				$smarty->assign("wlst",$lst);
				$smarty->assign("current_url",$_SERVER['REQUEST_URI']);
				
				return false;
			}
		}
		return true;
	}
	
	private function parse_path(){
		// URL parser for virtual path
	
		$uri = $_SERVER["REQUEST_URI"];
		
		$first = substr($uri, 1);
		
		$ouri = $this->db->sql_escape($uri);
		$row = $this->db->get_row("e_redirect","'$ouri' regexp olduri");
//		echo $this->db->sql_error();
		if($row){
			// redirect if there is an entry in the e_redirect table
			header("HTTP/1.1 301 Moved Permanently");
			$this->redirect_page($row['newuri']);
			exit();
		}
	
		if(function_exists("emps_uri_filter")){
			// define "emps_uri_filter" in extension to re-write the URI
			$uri = emps_uri_filter($uri);
		}
	
		$s = explode("?",$uri,2);
	
		$uri = $s[0];
		$uri = str_replace(EMPS_SCRIPT_URL_FOLDER, '', $uri);	// remove initial path from the URI
		
		$user = $this->db->get_row("e_users", "lcase(username)=lcase('".$this->db->sql_escape($first)."')");
		if($user){
			$uri = "/user/".$user['id']."/";
		}
	
		$this->PLURI = $uri;	
		$this->menu_URI = $uri;			
		
		if($uri{0} == '/') $uri = substr($uri,1);		
		$ouri = $uri;		
		$this->PURI = $ouri;

		$this->savevars();
		$uri=$this->PURI;
		if($uri{strlen($uri)-1}=='/') $uri = substr($uri,0,strlen($uri)-1);
	
		$this->URI = $uri;
		
//		dump($this->URI);		
	
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
	
	private function import_vars(){
	// Import the track-list variables from the GET, POST and put them to globals
	// Effecively this is a filtered track-vars
		$x = explode(",", EMPS_VARS);
		while(list($n,$v) = each($x)){
			if(!isset($GLOBALS[$v])) $GLOBALS[$v]='';
			if(isset($_GET[$v])) $GLOBALS[$v]=$_GET[$v];
			if(isset($_POST[$v])) $GLOBALS[$v]=$_POST[$v];
		}	
	}	
	
	public function page_exists($uri){
	// Return get_db_content_item if this URI exists in the CMS database
		$ra = $this->get_db_content_item($uri);
		if($ra) return $ra;
		return false;
	}
	
	public function get_db_content_item($uri){
	// Return the e_content item by URI, cache the response
	
		if(isset($this->content_cache[$uri])) return $this->content_cache[$uri];
	
		$q = "select * from ".TP."e_content where uri='$uri' and context_id = ".$this->website_ctx;
		$r = $this->db->query($q);
		$ra = $this->db->fetch_named($r);
		if(!$ra){
			$q = "select * from ".TP."e_content where uri='$uri' and context_id = ".$this->default_ctx;
			$r = $this->db->query($q);
			$ra = $this->db->fetch_named($r);
		}
		$content_cache[$uri] = $ra;
		return $ra;
	}
	
	public function get_content_data($page){
	// Read the properties of a content item (effectively page_properties)
		$context_id = $this->p->get_context(DT_CONTENT,1,$page['id']);
		$ra=$this->p->read_properties(array(), $context_id);
		$ra['context_id'] = $context_id;
		return $ra;
	}
	
	public function get_menu_data($item){
	// Read the properties of a menu item 
		$ra=$this->p->read_properties(array(),$this->p->get_context(DT_MENU,1,$item['id']));	
		return $ra;
	}
	
	
	public function copy_properties($code){
	// Load properties from get_content_data for the content item $code and save them as $page_properies
		global $smarty;
		
		$item = $this->get_db_content_item($code);
		$props = $this->get_content_data($item);
		
		$this->page_properties = array_merge($this->page_properties,$props);
	}
	
	public function get_setting_time($code){
	// Get the timestamp of a fine-tuning setting
		$ra = $this->get_setting($code);
		if($ra){
//			echo "has setting $code: ".$this->settings_cache['_full'][$code]['dt'].", ";
			return $this->settings_cache['_full'][$code]['dt']+0;
		}else{
			return false;
		}
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
						}else{
							$fn = EMPS_PATH_PREFIX.'/common/'.$file_name.'.'.$this->lang.'.htm';	
							$fn = stream_resolve_include_path($fn);
							if(!$fn){
								$fn = EMPS_PATH_PREFIX.'/common/'.$file_name.'.nn.htm';	
								$fn = stream_resolve_include_path($fn);
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
		$smarty->assign($reason,1);
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
		
		if($GLOBALS[$this->page_var]>=$total){
			$GLOBALS[$this->page_var]=$total-$perpage;
			if($GLOBALS[$this->page_var]<0){
				$GLOBALS[$this->page_var]=0;
			}
			$this->savevars();
		}
		
		$cs=$GLOBALS[$this->page_var];
		$f=ceil($total/$perpage);
		
		$cf=$f;
		$scl=floor($GLOBALS[$this->page_var]/$perpage)-4;
		if($scl<0) $scl=0;
		
		if($f>9) $f=9;
		
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
	
	public function print_pages_found(){
		$found = $this->db->found_rows();
		return $this->print_pages($found);
	}
	
	public function display_log(){
		global $smarty;
		$smarty->assign("ShowTiming",EMPS_SHOW_TIMING);
		$smarty->assign("ShowErrors",EMPS_SHOW_SQL_ERRORS);
		$end_time = emps_microtime_float(microtime(true));
		
		$span = $end_time - $this->start_time;
		
		$smarty->assign("timespan",sprintf("%02d",$span*1000));
		$smarty->assign("errors",$this->db->sql_errors);
		if($_GET['sql_profile']){
			$smarty->assign("SqlProfile",1);
			$smarty->assign("timing",$this->db->sql_timing);		
		}
		
		return $smarty->fetch("db:page/foottimer");		
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
		header("Location: $page");
	}
	
	public function redirect_elink(){
		if(count($this->db->sql_errors)>0){
//			dump($this->db->sql_errors);
			return false;
		}
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
	
	public function reset_antibot(){
	// Discard a used antibot key
		$pk=$_SESSION['antibot_pin'];
		$this->db->query('delete from '.TP.'e_pincode where pincode='.($pk['pin']+0).' and access='.($pk['sid']+0));
		unset($_SESSION['antibot_pin']);
	}
	
	public function uses_antibot(){
	// Prepare the current script for using the anti-bot feature
		global $emps,$smarty,$pk;
		$ip=$this->auth->get_num_ip($_SERVER['REMOTE_ADDR']);
		$pk=$_SESSION['antibot_pin'];
	
		if(!$pk){
			$dt=time();
			$dt<<=16;
			$ip&=0xFFFF;
			$sid=($ip|$dt)&(0x7FFFFFFF);
		
			mt_srand($sid);
			$pin=mt_rand(1114122,9912988);
	
			$pk['pin']=$pin;
			$pk['sid']=$sid;
			$_SESSION['antibot_pin']=$pk;
	
			$this->db->query('insert into '.TP."e_pincode values ($pin,$dt,$sid)");
		}
	
		$smarty->assign("pk",$pk);
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
	
	function get_full_id($id,$table,$pf,$vf){
		global $emps;
		$row=$emps->db->get_row($table,"id=$id");
		if(!$row){
			return "";
		}
		
		if($row[$pf]){
			$full_id=$this->get_full_id($row[$pf],$table,$pf,$vf);
		}else{
			$full_id="";
		}
		
		$value="";
		$vle=$row[$vf];
		$id=-$vle+0;
		for($i=0;$i<4;$i++){
			$cur=($id>>((3-$i)*8))&255;
			$value.=chr($cur);
		}
		return $full_id.$value;
	}	

	function utf8_urldecode($str){
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
		}else{
			if($_SERVER['HTTPS']=='on'){
				header("Location: http://".$addr);
				exit();
			}
		}
	}
	
	public function is_https(){
		if($_SERVER['HTTPS']=='on'){
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
	
	public function all_post_required(){
		reset($_POST);
		while(list($n,$v)=each($_POST)){
			if(!$v){
				$this->db->sql_null[$n] = true;
			}
		}
	}
	
	public function is_empty_database(){
		$r = $this->db->query("show tables");
		$lst = array();
		while($ra = $this->db->fetch_row($r)){
			$lst[] = $ra;
		}
		if(count($lst) == 0){
			return true;
		}
		return false;
	}
	
	public function shadow_properties_link($link){
		$link = $this->db->sql_escape($link);

		$shadow = $this->db->get_row("e_shadows","url='".$link."'");
		if($shadow){
			$context_id = $this->p->get_context(DT_SHADOW,1,$shadow['id']);
			$props = $this->p->read_properties(array(),$context_id);
			$this->page_properties = array_merge($this->page_properties,$props);			
		}
	}
		
	public function shadow_properties($vars){
		$link = $this->raw_elink($vars);
		
		return $this->shadow_properties_link($link);
		
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
	
	public function ensure_browser($name){
		global $SET;
		if(isset($this->db)){
			$row = $this->db->get_row("e_browsers", "name = '".$this->db->sql_escape($name)."'");
			if($row){
				return $row['id'];
			}else{
				$SET = array();
				$SET['name'] = $name;
				$this->db->sql_insert("e_browsers");
				$id = $this->db->last_insert();
				return $id;
			}
		}else{
			return -1;
		}
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
//			header("Cache-Control: cache");
//			header("Pragma: cache");			
			header("Expires: ".date("r", $this->expire_guess()));
			header("Last-Modified: ".date("r", $this->last_modified));

			$if_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
			if($if_modified){
				$if_dt = strtotime($if_modified);
//				echo "Last: ".$this->last_modified.", if: ".$if_dt;
//				exit();
				if($this->last_modified >= $if_dt){
					header("HTTP/1.1 304 Not Modified");
					exit();
				}
			}
		}
	}
	
	public function should_prevent_session(){
		global $emps_bots, $emps_just_set_cookie;
		
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
				$this->tl_array[$x[$i]]=$y[$i];
			}
		}
		if($this->tl_array[$c]){
			return $this->tl_array[$c];
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
	
	function transliterate_url($source){
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

}

?>