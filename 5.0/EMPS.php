<?php
/**
 * EMPS MULTI-WEBSITE ENGINE
 *
 * Version 5.0 / MongoDB-based
 */

require_once EMPS_COMMON_PATH_PREFIX."/EMPS.php";

/** 
 * EMPS Class - Version 5.0 / MongoDB-based
 */
class EMPS extends EMPS_Common {

	/**
	 * Early initialization procedure
	 *
	 * This will set up the database connection, contexts/properties manager and the authentication class.
	 */
	public function early_init(){
		$this->db = new EMPS_MongoDB;
		$this->mdb = $this->mongodb->mdb;
		
		$this->p = new EMPS_Properties;	

		if(!$this->fast){
			$this->auth = new EMPS_Auth;			
		}
	}
	
	public function website_by_host($hostname){
		$params = array('query' => array());
		
		$cursor = $this->db->find("emps_websites", $params);
		foreach($cursor as $website){
			if((strtolower($website['hostname']) == strtolower($hostname))
			||
			(preg_match($website['hostname_regex'], $hostname) > 0)
				){
				if($website['lang']){
					$this->lang = $website['lang'];
				}
				return $website['id'];
			}
		}
		
		return 0;
	}

	public function select_website(){
		$hostname = $_SERVER['SERVER_NAME'];
		$this->default_ctx = $this->p->get_context("emps_websites", 1, 0);
		$website_id = $this->website_by_host($hostname);
		if($website_id){
			if($this->current_website['status'] == 100){
				$this->website_ctx = $this->default_ctx;
			}else{
				$this->website_ctx = $this->p->get_context("emps_websites", 1, $website_id);
			}
		}else{
			$this->website_ctx = $this->default_ctx;
		}
	}
	
	/**
	 * Get a property of the current website
	 *
	 * This will read all properties of the website's context and cache them in the EMPS object. Then return the property of name $code.
	 *
	 * @param $code string Setting (property) code
	 */
	public function get_setting($code){
		if(!is_array($this->settings_cache)){
			$this->load_settings();
		}
		return $this->settings_cache[$code];
	}
	
	/**
	 * Get a virtual HTML page from the database
	 *
	 * This will read the entire document of the virtual page from the MongoDB emps_content collection.
	 *
	 * @param $uri string Full relative URL of the page
	 */
	public function get_db_content_item($uri){
		$params = array();
		$params['query'] = array('uri' => $uri, 'website_ctx' => $this->website_ctx);
		$row = $this->db->get_row("emps_content", $params);
		if(!$row){
			if($this->default_ctx != $this->website_ctx){
				$params['query']['website_ctx'] = $this->default_ctx;
				$params['query']['uri'] = $uri;
				$row = $this->db->get_row("emps_content", $params);
				if($row){
					return $this->db->safe_array($row);
				}
			}
		}else{
			return $this->db->safe_array($row);
		}
		return false;
	}
	
	public function get_content_data($ra){
		return $ra;
	}
	
	public function load_settings(){
		$default_settings = $this->p->load_context($this->default_ctx);
		$dt = 0;
		if(!$default_settings){
			$default_settings = array();
		}else{
			$default_settings = $this->db->safe_array($default_settings);
			if($default_settings['dt'] > $dt){
				$dt = $default_settings['dt'];
			}
		}
		$website_settings = $this->p->load_context($this->website_ctx);
		if(!$website_settings){
			$website_settings = array();
		}else{
			$website_settings = $this->db->safe_array($website_settings);
			if($website_settings['dt'] > $dt){
				$dt = $website_settings['dt'];
			}
		}

		$this->settings_cache = array_merge($default_settings, $website_settings);
		$this->settings_cache['dt'] = $dt;
	}
	
	public function get_setting_time($code){
		if(!is_array($this->settings_cache)){
			$this->load_settings();
		}
		return $this->settings_cache['dt'];
	}
	
	public function section_menu_ex($code, $parent, $default_parent){

		$menu = array();
		
		$use_context = $this->website_ctx;
		
		$query = array();
		$query['website_ctx'] = $use_context;
		$query['grp'] = $code;
		$query['enabled'] = 1;
		if($parent){
			$query['parent'] = $this->db->oid($parent);
		}else{
			$query['parent'] = array('$exists' => false);
		}
		
		$params = array();
		$params['query'] = $query;
		$params['options'] = array('sort' => array('ord' => 1));
		$cursor = $this->db->find("emps_menu", $params);
		
		$mlst = array();
		foreach($cursor as $ra){
			$ra = $this->db->safe_array($ra);
			$mlst[] = $ra;
		}

		if(!$parent || $default_parent){
			$use_parent = 0;
			if($default_parent){
				$use_parent = $default_parent;
			}
			
			$query = array();
			$query['website_ctx'] = $this->default_ctx;
			$query['grp'] = $code;
			$query['enabled'] = 1;
			if($use_parent){
				$query['parent'] = $this->db->oid($use_parent);
			}else{
				$query['parent'] = array('$exists' => false);
			}
			
			$params = array();
			$params['query'] = $query;
			$params['options'] = array('sort' => array('ord' => 1));
			$cursor = $this->db->find("emps_menu", $params);
			
			$dlst = array();
			foreach($cursor as $ra){
				$ra = $this->db->safe_array($ra);
				$ra['default_id'] = $ra['_id'];
				$dlst[] = $ra;
			}

			$ndlst = array();
			while(list($n, $v) = each($dlst)){
				reset($mlst);
				$add = true;
				while(list($nn, $vv) = each($mlst)){
					if($vv['uri'] == $v['uri'] && $vv['grp'] == $v['grp']){
						$mlst[$nn]['default_id'] = $v['_id'];
						$add = false;
					}
				}
				if($add){
					$ndlst[] = $v;
				}
			}
			if($ndlst){
				reset($ndlst);
				while(list($nn,$vv) = each($ndlst)){
					$mlst[] = $vv;
				}
				
				uasort($mlst, array($this, 'sort_menu'));
			}
		}
		reset($mlst);
		while(list($n,$ra) = each($mlst)){
			$md = $ra;
			
			$ra['link'] = $ra['uri'];

			$ra['splink'] = $md['splink'];
			if(!$ra['splink']){
				$ra['splink'] = $ra['link'];
			}
			
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
	
			$smenu = $this->section_menu_ex($code, $ra['_id'], $ra['default_id']);
	
			$ra['sub'] = $smenu;
			$ra['md'] = $md;
			$menu[] = $ra;
		}
		return $menu;

	}
	
	public function prepare_doc($doc, $fields){
		$new_doc = $doc;
		
		$fx = explode(",", $fields);
		
		unset($new_doc['sadd']);
		unset($new_doc['sedit']);
		
		$evx = explode(",", EMPS_VARS);
		
		foreach($new_doc as $n => $v){
			if(mb_substr($n, 0, 5) == 'post_'){
				unset($new_doc[$n]);
				continue;
			}
			if(mb_substr($n, 0, 7) == 'action_'){
				unset($new_doc[$n]);
				continue;
			}
			if($n == 'return_to'){
				unset($new_doc[$n]);
				continue;
			}
			$skip = false;
			reset($evx);
			foreach($evx as $vv){
				if($vv == $n){
					$skip = true;
					break;
				}
			}
			if($skip){
				unset($new_doc[$n]);
				continue;
			}
			
			reset($fx);
			foreach($fx as $f){
				$vx = explode(":", $f);
				if($n == $vx[0]){
					switch($vx[1]){
					case 'i':
						$new_doc[$n] = intval($v);
						break;
					case 'f':
						$new_doc[$n] = floatval($v);
						break;
					case 'o':
						if($v){
							$new_doc[$n] = $this->db->oid($v);
						}else{
							unset($new_doc[$n]);
						}
						break;
					case 'b':
						if($v){
							$new_doc[$n] = true;
						}else{
							$new_doc[$n] = false;
						}
						break;
					}
				}
			}
		}
		
		return $new_doc;
	}
}

