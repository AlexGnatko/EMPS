<?php

class EMPS_NG_PickList {
	public $id;
	public $table_name;
	public $filter;
	
	public $perpage = 10;
	
	public $what = "t.*";
	public $join = "";
	public $where = "";
	public $orderby = " order by t.id asc ";
	
	public function parse_request(){
		global $emps, $key;
		$x = explode("|", $key, 2);
		$this->table_name = $emps->db->sql_escape($x[0]);
		$this->filter = $x[1];
	}
	
	public function handle_row($ra){
		$ra['display_name'] = $ra['name'];
		return $ra;
	}
	
	public function make_and($extra){
		global $emps;
		$and = "";
		if($extra){
			$x = explode("|",$extra);
			while(list($n, $v) = each($x)){
				$xx = explode("=", $v, 2);
				if(count($xx) == 2){
					$and .= " and ";
					$and .= $emps->db->sql_escape($xx[0])." = '".$emps->db->sql_escape($xx[1])."'";
				}else{
					$xx = explode("<>",$v,2);
					if(count($xx) == 2){
						$and .= " and ";
						$and .= $emps->db->sql_escape($xx[0])." <> '".$emps->db->sql_escape($xx[1])."'";
					}else{
						$xx = explode("_in_", $v, 2);
						if(count($xx) == 2){
							$and .= " and ";
							$and .= $emps->db->sql_escape($xx[0])." in (".$emps->db->sql_escape($xx[1]).")";
						}
					}
				}
			}
		}		
		return $and;
	}
		
	public function handle_request(){
		global $emps, $start, $perpage;

		$this->parse_request();
		
		header("Content-Type: application/json; charset=utf-8");
		
		
		$text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['text']));
		$id = 0;
		if($text){
			$matches = array();
			preg_match_all("/<([^>]+)>/", $text, $matches);
			$id = $matches[1][count($matches[1])-1];
		}
		
		$default_text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['default_text']));
		if($text == $default_text){
			$text = "";
		}
		
		$and = $this->make_and($this->filter);
		
		$perpage = $this->perpage;
		$start = intval($start);
		
		$r = $emps->db->query("select SQL_CALC_FOUND_ROWS ".$this->what." from ".TP.$this->table_name." as t ".$this->join." where t.name like '%$text%' ".$and." ".$this->where.$this->orderby." limit $start, $perpage");
		
		$pages = $emps->count_pages($emps->db->found_rows());
		
		$lst = array();
		while($ra = $emps->db->fetch_named($r)){
			$ra = $this->handle_row($ra);
			$lst[] = $ra;
		}
		
		$response = array();
		$response['code'] = "OK";
		$response['list'] = $lst;
		$response['pages'] = $pages;

		echo json_encode($response);
	}
}

$fn = $emps->page_file_name('_pick/ng/list,project','controller');
if(file_exists($fn)){
	require_once $fn;
}

if(!isset($pick)){
	$pick = new EMPS_NG_PickList;
}

$emps->no_smarty = true;
$pick->handle_request();

?>