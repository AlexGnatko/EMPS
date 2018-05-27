<?php
class EMPS_TableEditor {
	public $table_name = false;
	
	public $order_by = array('_id', -1);
	
	public $doc_filter = "";
	
	public $id_field = "id";
	
	public $website_ctx = 1;
	
	public $total, $pages;

	public $sort = [];
	
	public function handle_post(){
		global $emps, $key;

		if($_POST['sadd'] || $_POST['sedit']){
		    if($key){
                $this->pre_save($id = $emps->db->oid($key));
            }
		}
		
		if($_POST['sadd']){
			$id = '';
			$_POST['context_id'] = $this->website_ctx;
			$_POST[$this->id_field] = $emps->db->get_next_id($this->table_name);
			$emps->db->insert($this->table_name, array('doc' => $emps->prepare_doc($_POST, $this->doc_filter)));
			if(isset($emps->db->last_id)){
				$this->post_save($emps->db->last_id);
			}
			$emps->loadvars();
		}
		
		if($key){
			if($_POST['confkill']){
				$id = $emps->db->oid($key);
				$this->handle_kill($id);
				
				$emps->db->delete_one($this->table_name, array("query" => array("_id" => $id)));
				
				$this->post_kill($id);
	
				$key = ""; $emps->savevars();
			}
		}
		
		if($_POST['sedit']){
			$id = $emps->db->oid($key);
			$params = array();
			$params['query'] = array("_id" => $id);
			$params['update'] = array('$set' => $emps->prepare_doc($_POST, $this->doc_filter));
			$emps->db->update_one($this->table_name, $params);
			$this->post_save($id);
			$emps->loadvars();
		}		
		
		$emps->redirect_elink();
	}
	
	public function handle_kill($id){
	}
	
	public function post_kill($id){
	}	
	
	public function post_save($id){
	
	}
	
	public function pre_save($id){
	
	}
	
	public function handle_row($ra){
		global $key,$emps;
		$emps->loadvars();
		$key = $ra['_id'];
		$ra['klink'] = $emps->clink('kill=1');
		$emps->loadvars();
		return $ra;
	}
	
	public function handle_input($ra){
		return $this->handle_row($ra);
	}
	
	public function handle_request(){
		global $emps, $smarty, $key, $start, $perpage;
		$emps->loadvars();
	
		if($key){
			$smarty->assign("RefreshLink",$emps->elink());
			$key="";
			$smarty->assign("DeselectLink",$emps->elink());
			$emps->loadvars();
			$smarty->assign("RemoveLink",$emps->clink("kill=1"));
			$emps->loadvars();
		}
		
		if($_POST){
			$this->handle_post();
		}
		
		if($key){
			if($_REQUEST['kill']){
				$smarty->assign("key", $key);
				$smarty->assign("KillForm", 1);
			}
		}
	
		$start = intval($start);
		if(!$perpage){
			$perpage = 25;
		}
		
		$params = array();
		$params['query'] = array();
		$params['options'] = array('limit' => $perpage, 'skip' => $start, 'sort' => $this->sort);
		$cursor = $emps->db->find($this->table_name, $params);
	

		$this->total = $emps->db->found_rows;
		$this->pages = $emps->count_pages($this->total);
	
		$smarty->assign("pages", $this->pages);
		
		$lst = array();
		
		foreach($cursor as $ra){
			$key = $ra['_id'];
			$ra['ilink'] = $emps->elink();
			if($ra['name'] == ""){
				$ra['name'] = "-";
			}
			$ra = $this->handle_row($ra);
			$lst[] = $ra;
			$emps->loadvars();
		}
		$smarty->assign("lst", $lst);
		
		$emps->loadvars();
	
		if($key){
			$id = $emps->db->oid($key);
			$params = array();
			$params['query'] = array('_id' => $id);
			
			$ra = $emps->db->get_row($this->table_name, $params);

			$ra = $this->handle_input($ra);
			$smarty->assign("vle", $ra);
			$smarty->assign("EditMode", 1);
		};
	}
}
