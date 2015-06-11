<?php
// Angular.JS table editor

class EMPS_NGTed {
	
	public $order_by = " order by id asc ";
	
	public function json_list($st, $perp){
		global $emps, $start, $perpage;
		
		$emps->no_smarty = true;
		$start = $st;
		$perpage = $perp;
		
		header("Content-Type: application/json; charset=utf-8");
		
		$r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from ".TP.$this->table_name." ".$this->order_by." limit $start, $perpage");
		$pages = $emps->count_pages($emps->db->found_rows());
		
		$lst = array();
		
		while($ra = $emps->db->fetch_named($r)){
			$lst[] = $ra;
		}
		
		$response = array();
		$response['pages'] = $pages;
		$response['list'] = $lst;
		$response['path'] = $emps->elink();
		$response['code'] = "OK";
		
		echo json_encode($response);
	}
	
	public function json_load($id){
		global $emps;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");
		
		$id = intval($id);
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$response = array();
		$response['row'] = $row;
		$response['code'] = "OK";
		
		echo json_encode($response);
	}
	
	public function json_kill($id){
		global $emps;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");
		
		$id = intval($id);
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$response = array();
		if($row){
			$emps->db->query("delete from ".TP.$this->table_name." where id = ".$id);
			$response['row'] = $row;
			$response['code'] = "OK";
		}
		
		echo json_encode($response);
	}

	public function save_item($id){
		global $emps, $SET;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");

		unset($_REQUEST['id']);
		if(!$_REQUEST['url']){
			$_REQUEST['url'] = $emps->transliterate_url($_REQUEST['name']);
		}
		$emps->db->sql_update($this->table_name, "id = ".$id);
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$response = array();
		if($row){
			$response['row'] = $row;
			$response['code'] = "OK";
		}
		echo json_encode($response);
	}
	
	public function add_item(){
		global $emps, $SET;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");

		unset($_REQUEST['id']);
		if(!$_REQUEST['url']){
			$_REQUEST['url'] = $emps->transliterate_url($_REQUEST['name']);
		}
		$emps->db->sql_insert($this->table_name);
		$id = $emps->db->last_insert();
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$response = array();
		if($row){
			$response['row'] = $row;
			$response['code'] = "OK";
		}
		echo json_encode($response);
	}
	
	public function handle_request(){
		global $emps;
		if($_GET['json']){
			$json = $_GET['json'];
			if($json == "list"){
				return $this->json_list($_GET['start'], $_GET['perpage']);
			}
			if($json == "load"){
				return $this->json_load($_GET['id']);
			}
			if($json == "kill"){
				return $this->json_kill($_GET['id']);
			}
		}
		
		if($_POST['post_add_item']){
			return $this->add_item();
		}
		if($_POST['post_save_item']){
			return $this->save_item(intval($_POST['post_save_item']));
		}
		
	}
}

?>