<?php
global $emps;

require_once "Smarty3/libs/Smarty.class.php"; 

$smarty = new Smarty; 

$smarty->left_delimiter = '{{';
$smarty->right_delimiter = '}}';
if(defined('EMPS_WEBSITE_SCRIPT_PATH')){
	$emps_smarty_compile_dir = EMPS_WEBSITE_SCRIPT_PATH."/local/temp_c/".$emps->lang."/";

}else{
	$emps_smarty_compile_dir = EMPS_SCRIPT_PATH."/local/temp_c/".$emps->lang."/";
}

if(!is_dir($emps_smarty_compile_dir)){
	mkdir($emps_smarty_compile_dir);
	chmod($emps_smarty_compile_dir, 0777);
}

$smarty->setCompileDir($emps_smarty_compile_dir); 

$smarty->cache_lifetime = 1800;
$smarty->compile_check = true;
$smarty->caching = false;

class Smarty_Resource_EMPS_DB extends Smarty_Resource_Custom {
	protected function fetch($name, &$source, &$mtime)
	{
		global $emps;
	
		$r = $emps->get_setting($name);

		if(!$r){
			$fn = $emps->page_file_name($name,'view');
			if(file_exists($fn)){
				$source = file_get_contents($fn);
			}else{
				$fn = $emps->common_module_html($name);
				if(file_exists($fn)){
					$source = file_get_contents($fn);
				}else{
					$source = "";
				}
			}
			$mtime = filemtime($fn);
		}else{
			$source = $r;
			$mtime = $emps->get_setting_time($name);						
		}
		return true;		
	}

	protected function fetchTimestamp($name) {
		global $emps;
	
		$r = $emps->get_setting_time($name);
		
		if($r==-1 || !$r){
			$fn = $emps->page_file_name($name,'view');
			if(!file_exists($fn)){
				$fn = $emps->common_module_html($name);
				if(!file_exists($fn)){
					return time() - 60*60*24;
				}else{
					$r = filemtime($fn);			
				}
			}else{
				$r = filemtime($fn);
			}
		}
		return $r;		
	}
};

class Smarty_Resource_EMPS_Page extends Smarty_Resource_Custom {
	protected function fetch($name, &$source, &$mtime)
	{
		global $emps;
		
		$ra = $emps->get_db_content_item($name);
		if($ra){
			$data = $emps->get_content_data($ra);
			if($data['html']){
				$source = $data['html'];
				$mtime = $ra['dt'];
			}
		}else{
			$source = "";
			$mtime = time() - 60*60*24;
		}		
		return true;
	
	
	}

	protected function fetchTimestamp($name) {
		global $emps;
		
		$ra = $emps->get_db_content_item($name);
		if($ra){
			return $ra['dt'];
		}else{
			return (time() - 60*60*24);
		}		
	}
};

$smarty->registerResource('db', new Smarty_Resource_EMPS_DB());
$smarty->registerResource('page', new Smarty_Resource_EMPS_Page());


function smarty_emps($params, Smarty_Internal_Template $template)
{
	global $emps;
	if($params['method']){
		if(method_exists($emps, $params['method'])){
			$method_name = $params['method'];
			return $emps->$method_name();
		}
	}
	
	if($params['plugin']){
		$function = $params['plugin'];
		$fname = 'smarty_plugin_'.$function;
		
		if(function_exists($fname)){
			return $fname($params);
		}
	}
	
	return "";
}

function smarty_AJ($params, Smarty_Internal_Template $template)
{
	if(isset($params['v'])){
		return '{{ '.$params['v'].' }}';
	}else{
		return '{{';
	}
}

function smarty_JA($params, Smarty_Internal_Template $template)
{
	return '}}';
}

$smarty->registerPlugin("function", "emps", "smarty_emps");

// Angular JS markup helpers
$smarty->registerPlugin("function", "AJ", "smarty_AJ");
$smarty->registerPlugin("function", "JA", "smarty_JA");

function smarty_modifier_hyp($v){
	if($v==0){
		return '-';
	}else{
		return $v;
	}
}

$smarty->registerPlugin("modifier", "hyp", "smarty_modifier_hyp");
?>