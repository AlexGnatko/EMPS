<?php
// Angular.JS table editor

class EMPS_NGTed {
	
	public $order_by = " order by id asc ";
	
	public $pad_templates = array();
	
	public $pad_code = "";
	
	public $explain_list_rows = false;
	
	public $track_mce = array();
	
	public $what = "t.*";
	public $where = "";
	public $join = " as t ";
	
	public $show_cols = "id,name";
	
	public function __construct(){
		global $emps;
		
		$emps->no_autopage = true;
		
		$this->pad_templates[] = "ngted/pads,%s";
	}

	public function json_list($st, $perp){
		global $emps, $start, $perpage;
		
		$emps->no_smarty = true;
		$start = $st;
		$perpage = $perp;
		
		header("Content-Type: application/json; charset=utf-8");
		
		$r = $emps->db->query("select SQL_CALC_FOUND_ROWS ".$this->what." from ".TP.$this->table_name.$this->join." where 1=1 ".$this->where." ".$this->order_by." limit $start, $perpage");
		$pages = $emps->count_pages($emps->db->found_rows());
		
		$lst = array();
		
		while($ra = $emps->db->fetch_named($r)){
			if($this->explain_list_rows){
				$ra = $this->handle_row($ra);
				
				$nrow = array();
				$emps->copy_values($nrow, $ra, $this->show_cols);
				$ra = $nrow;

			}
			$lst[] = $ra;
		}
		
		$response = array();
		$response['pages'] = $pages;
		$response['list'] = $lst;
		$response['path'] = $emps->elink();
		$response['code'] = "OK";
		
		echo json_encode($response);
	}
	
	public function handle_row($row){
		global $emps, $ss, $key;
		$emps->loadvars();
		
		if($this->ref_type){
			$context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $row['id']);
			$row['own_context_id'] = $context_id;
			$row = $emps->p->read_properties($row, $context_id);
		}
		
		$pads = array();
		$key = $row['id'];
		foreach($this->pads as $n => $v){
			$ss = $n;
			$pads[$n] = $emps->elink();
		}
		$row['pads'] = $pads;
		$ss = "";
		$key = "";
		$row['backlink'] = $emps->elink();
		$emps->loadvars();
		
		$row['display_name'] = $row['name'];
		
		return $row;
	}
	
	public function json_load($id){
		global $emps;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");
		
		$id = intval($id);
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$row = $this->handle_row($row);
		
		unset($row['_full']);
		
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
			$this->after_kill($id);
			$response['row'] = $row;
			$response['code'] = "OK";
		}
		
		echo json_encode($response);
	}
	
	public function after_kill($id){
		global $emps;
		
		if($this->ref_type){
			$context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);
			
			$emps->p->delete_context($context_id);	
			
			if($this->multilevel){
				$r = $emps->db->query("select id from ".TP.$this->table_name." where parent = ".$id);
				while($ra = $emps->db->fetch_row($r)){
					$emps->db->query('delete from '.TP.$this->table_name.' where id='.$ra[0]);				
					$this->after_kill($ra[0]);
				}
			}
		}
	}

	public function save_item($id){
		global $emps, $SET;
		
		$emps->no_smarty = true;
		header("Content-Type: application/json; charset=utf-8");
		
		foreach($this->track_mce as $v){
			$_REQUEST[$v] = str_replace('<br data-mce-bogus="1">', '&nbsp;<br>', $_REQUEST[$v]);
		}

		unset($_REQUEST['id']);
		if(!$_REQUEST['url']){
			$_REQUEST['url'] = $emps->transliterate_url($_REQUEST['name']);
		}
		$emps->db->sql_update($this->table_name, "id = ".$id);
		
		if($this->ref_type){
			$context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);
			$emps->p->save_properties($_REQUEST, $context_id, $this->track_vars);
		}

		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$row = $this->handle_row($row);
		
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
		
		if($this->ref_type){
			$context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);
			$emps->p->save_properties($_REQUEST, $context_id, $this->track_vars);
		}
		
		$row = $emps->db->get_row($this->table_name, "id = ".$id);
		
		$row = $this->handle_row($row);
		
		$response = array();
		if($row){
			$response['row'] = $row;
			$response['code'] = "OK";
		}
		echo json_encode($response);
	}
	
	public function can_view_pad(){
		return true;
	}
	
	public function handle_request(){
		global $emps, $ss, $key, $smarty;
		
		$this->prepare_menu();
		
		$emps->loadvars();
		
		if($key){
			$id = intval($key);
			$this->row = $emps->db->get_row($this->table_name , "id = ".$id);
		}
		
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

		$tab_mode = false;
		if($_GET['load_tab']){
			$this->pad_code = $_GET['load_tab'];
			$tab_mode = true;
		}elseif($ss){
			$this->pad_code = $ss;
			$tab_mode = true;
		}
		
		$pad_page = "";
		
		if($tab_mode){
			$fn = $this->current_pad('controller');
		
			if(file_exists($fn) && $this->can_view_pad()){
				$pad_page = $this->current_pad('view');
				$smarty->assign('subpage', $pad_page);			
				require_once $fn;
			}
		}
		
		if($_GET['load_tab']){
			$emps->no_smarty = true;
			if($pad_page){
				$smarty->assign("pad", $this->pad_code);
				$smarty->display($pad_page);
			}
		}else{
			if($pad_page){
				$smarty->assign("pad", $ss);
				$smarty->assign("ss", $ss);
			}
		}
		
		if($_POST['post_add_item']){
			return $this->add_item();
		}
		if($_POST['post_save_item']){
			return $this->save_item(intval($_POST['post_save_item']));
		}
		
	}
	
	public function handle_display($row){
		$row = $this->handle_row($row);
		return $row;
	}
	
	public function handle_view_row(){
		global $smarty;
		$this->row = $this->handle_display($this->row);
		
		$smarty->assign('row', $this->row);
	}

	public function current_pad($type){
		global $emps;

		reset($this->pad_templates);
		while(list($n,$v) = each($this->pad_templates)){
			$uv = sprintf($v, $this->pad_code);
			if($type == 'view'){
				$fn = $emps->page_file_name('_'.$uv, 'view');
			}else{
				$fn = $emps->page_file_name('_'.$uv.'.php', 'inc');
			}

			if(!file_exists($fn)){
				$v = str_replace(',', '/', $v);
				$uv = sprintf($v, $this->pad_code);
				if($type=='view'){
					$fn = $emps->common_module($uv.'.'.$emps->lang.'.htm');
					if(!file_exists($fn)){
						$fn = $emps->common_module($uv.'.nn.htm');
					}
				}else{
					$fn = $emps->common_module($uv.'.php');
				}
				if(file_exists($fn)){
					return $fn;
				}
			}else{
				return $fn;
			}
		}
	}

	public function add_pad_template($txt){
		array_unshift($this->pad_templates, $txt);
	}

	public function prepare_menu(){
		global $emps, $smarty, $ss;
		
		if(!$ss){
			$ss = "info";
		}
		$menu = $emps->prepare_pad_menu($this->pads, 'ss');
		$smarty->assign('smenu', $menu);
	}

}

?>