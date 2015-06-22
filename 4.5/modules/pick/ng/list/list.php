<?php

class EMPS_NG_PickList {
	public $id;
	public $table_name;
	public $filter;
	
	public $perpage = 15;
	
	public $what = "*";
	public $join = "";
	public $where = "";
	public $orderby = " order by id asc ";
	
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
		
	public function handle_request(){
		global $emps, $start, $perpage;

		$this->parse_request();
		
		header("Content-Type: application/json; charset=utf-8");
		
		$perpage = $this->perpage;
		$start = intval($start);
		
		$r = $emps->db->query("select SQL_CALC_FOUND_ROWS ".$this->what." from ".TP.$this->table_name.$this->join." where 1=1 ".$this->where.$this->orderby." limit $start, $perpage");
		
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