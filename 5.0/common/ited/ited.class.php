<?php
class EMPS_ImprovedTableEditor {
	public $ref_sub = 0;
	public $ref_id = 0;
	
	public $context_id = 0;
	
	public $use_context = false;
	
	public $website_ctx = "";
	
	public $id_field = "id";
	
	public $track_props = '';	
	public $table_name = "";
	public $credentials = "admin";

	public $action_open_ss = "info";
	
	public $form_name = "db:ited/generic";
	
	public $pad_templates = array();
	public $ajax_templates = array();	
	
	public $where = array();
	
	public $row, $old_row;
	
	public $tree = false;
	
	public $immediate_add = false;	
	
	public $multilevel = false;
	public $preview_row = true;

	public $pads = array(
		'info'=>'General'
		);
		
	public function __construct(){
		$this->pad_templates[] = "ited/pads,%s";
	}

	public function current_pad($type){
		global $emps,$ss;
		$emps->loadvars();

		reset($this->pad_templates);
		while(list($n,$v)=each($this->pad_templates)){
			$uv = sprintf($v,$ss);
			if($type == 'view'){
				$fn = $emps->page_file_name('_'.$uv,'view');
			}else{
				$fn = $emps->page_file_name('_'.$uv.'.php','inc');
			}

			if(!file_exists($fn)){
				$v = str_replace(',','/',$v);
				$uv = sprintf($v,$ss);
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
	
	public function add_ajax_template($txt){
		array_unshift($this->ajax_templates, $txt);
	}
	
	
	public function can_save(){
		return true;
	}
	
	public function can_delete(){
		return true;
	}

	public function can_view_pad(){
		return true;
	}	
		
	public function after_insert($id){
		global $emps;

	}
	
	public function after_save($id){
		global $emps;

	}	

	public function after_kill($id){
		global $emps;
		
	}
	
	public function handle_redirect(){
		global $emps;
		$emps->redirect_elink();
	}	
	
	public function get_row($id){
		global $emps;
		
		$params = array();
		$params['query'] = array('_id' => $id);
		$row = $emps->db->get_row($this->table_name, $params);
		if($row){
			$row = $emps->db->safe_array($row);
			return $this->handle_row($row);
		}
		return false;
	}
	
	public function count_children($id){
		global $emps;
		
		if($id){
			$params = array();
			$params['query'] = array('parent' => $emps->db->oid($id));
			$params['options'] = array();
			$count = $emps->db->count_rows($this->table_name, $params);
		}else{
			$count = 0;
		}
		
		return $count;
	}

	public function handle_row($row){
		global $emps, $ss, $key, $sd;
		if($this->use_context){
			$context_id = $emps->p->get_context($this->table_name, $this->ref_sub, $row['_id']);
		}

		$emps->loadvars();
		$key = $emps->db->oid_string($row['_id']);
		$ss = "info";
		$row['nlink'] = $emps->elink();	
		$ss = "html";
		$row['ilink'] = $emps->elink();
		$ss = "photos";
		$row['hlink'] = $emps->elink();	
		$ss = "props";
		$row['plink'] = $emps->elink();	
		$emps->loadvars();
		
		$ss = "";
		$key = $emps->db->oid_string($row['_id']);
		$row['elink'] = $emps->clink("part=edit");
		$row['klink'] = $emps->clink("part=kill");		
		$emps->loadvars();
		
		$sd = $emps->db->oid_string($row['_id']);
		$row['clink'] = $emps->elink();
		$emps->loadvars();
		
		if($row['parent']){
			$row['parent_data'] = $this->get_row($row['parent']);
		}
		
		$row['children'] = $this->count_children($row['_id']);
				
		return $row;
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
	
	public function handle_orig(){
		global $emps, $smarty;
		
		require_once $emps->common_module('diff/diff.class.php');
		
		$diff = new EMPS_Diff;

		$orig = $_POST['orig'];

		$row = $this->row;		
		
		$rv = false;
		foreach($orig as $var => $value){
			if($this->row[$var] != $value){
				$row[$var] = $value;
				$row['new'][$var] = $_POST[$var];
				$row['other'][$var] = $this->row[$var];
				
				$result = $diff->diff_result($this->row[$var], $_POST[$var]);
				$row['new_cur'][$var] = $result;
				$result = $diff->diff_result($value, $this->row[$var]);
				$row['cur_old'][$var] = $result;
//				dump($result);exit();
				$rv = true;
			}
		}
		
		if($rv){
			$smarty->assign("Differences", 1);
//			exit();
		}
		
		$smarty->assign("row", $row);
		
		return $rv;
	}
	
	public function handle_post(){
		global $emps,$smarty;
		
		$smarty->assign("PostEnabled",1);
		
		if($_POST['post_save']){		
			if($this->can_save()){
				if($this->preview_row){
					$this->handle_view_row();
					$this->old_row = $this->row;
				}
				
				$_POST['name'] = trim($_POST['name']);
				
				$rv = false;
				if(isset($_POST['orig'])){
					$rv = $this->handle_orig();
				}
				
				if(!$rv){
					$params = array();
					$params['query'] = array("_id" => $this->ref_id);
					$params['update'] = array('$set' => $emps->prepare_doc($_POST, $this->doc_filter));
					$emps->db->update_one($this->table_name, $params);
					
					$this->after_save($this->ref_id);
					
					$this->handle_redirect();
				}
			}
		}

	}
	
	public function prepare_menu(){
		global $emps,$smarty;
		$menu = $emps->prepare_pad_menu($this->pads, 'ss');
		$smarty->assign('smenu', $menu);
	}
			
	public function handle_detail_mode(){
		global $emps, $smarty, $ss, $key;
		if($this->ref_id){
			$ss = '';
			$smarty->assign('def_edit', $emps->clink('part=edit'));
			$smarty->assign('def_kill', $emps->clink('part=kill'));
		}
		$emps->loadvars();
		
		if($_POST['action_kill']){
			if($this->can_delete()){
				$emps->db->delete_one($this->table_name, array("query" => array("_id" => $this->ref_id)));
				$this->after_kill($this->ref_id);
	
				$key = ""; $ss = "";
				$emps->redirect_elink(); exit();					
			}
		}	
		
		$ss = '';
		$key = '';
		$smarty->assign('BackITEDLink', $emps->elink());
		$emps->loadvars();
	
		$this->row = $this->get_row($this->ref_id);
		if(!$this->row){
			$key = "";
			$ss = "";
			$emps->redirect_elink();exit();
		}

		$smarty->assign('row',$this->row);

		$smarty->assign("CanSave", $this->can_save());
	
		$smarty->assign('Zoom', 1);
		
		$this->prepare_menu();
	
		$fn = $this->current_pad('controller');
		
		if(file_exists($fn) && $this->can_view_pad()){
			$smarty->assign('subpage', $this->current_pad('view'));			
			require_once($fn);
		}
		
		if($this->use_context){
			$smarty->assign('context_id', $this->context_id);	
		}
	
	}
	
	public function handle_list_mode(){
		global $smarty, $emps, $key, $ss, $start, $perpage, $total;

		$smarty->assign("lang", $emps->lang);
		
		$emps->loadvars();
		
		if($_GET['part']){
			$emps->no_smarty = true;
			$emps->text_headers();
			$smarty->assign("itedpart", $_GET['part']);
			if($_GET['part'] != "add"){
				$row = $this->get_row($this->ref_id);
				$row = $this->handle_display($row);
				$smarty->assign("row", $row);
			}
		}
		if($_GET['part'] == "edit"){
			$smarty->assign("Mode", "edit");
			$smarty->display("db:ited/iactpart");
		}elseif($_GET['part'] == "kill"){
			$smarty->assign("Mode", "kill");
			$smarty->display("db:ited/iactpart");
		}elseif($_GET['part'] == "add"){
			$smarty->assign("Mode", "add");
			if($_REQUEST['target']){
				$smarty->assign("add_target", $_REQUEST['target']);
			}
			$smarty->assign("autolink", $_REQUEST['autolink']);
			$smarty->assign("return_to", $_REQUEST['return_to']);
			$smarty->display("db:ited/iactpart");
		}else{
			if($_POST['action_open']){
				$ss = $this->action_open_ss;
				$key = intval($_POST['id']);
				$emps->redirect_elink(); exit();
			}
			if($_POST['action_add']){
				$_POST['website_ctx'] = $this->website_ctx;
				$_POST[$this->id_field] = $emps->db->get_next_id($this->table_name);				
				
				$params = array();
				$params['doc'] = $emps->prepare_doc($_POST, $this->doc_filter);
//				dump($params); exit();
				$emps->db->insert($this->table_name, $params);
				$emps->redirect_elink();exit();
			}
			if($_POST['action_save']){
				if($this->can_save()){
					$params = array();
					$params['query'] = array("_id" => $this->ref_id);
					$params['update'] = array('$set' => $emps->prepare_doc($_POST, $this->doc_filter));
					$emps->db->update_one($this->table_name, $params);
					$this->after_save($this->ref_id);
				}
				$emps->redirect_elink();exit();
			}
			if($_POST['action_kill']){
				if($this->can_delete()){
					$emps->db->delete_one($this->table_name, array("query" => array("_id" => $this->ref_id)));
					$this->after_kill($this->ref_id);
			
					$key="";$ss="";
					$emps->redirect_elink();exit();					
				}
			}	
			
			if($this->ref_id){
				$smarty->assign("def_edit", $emps->clink("part=edit"));
				$smarty->assign("def_kill", $emps->clink("part=kill"));
			}			
			
			$start = intval($start);
			if(!$perpage){
				$perpage = 25;
			}

			$params = array();
			$params['query'] = $this->where;
			$params['options'] = array('limit' => $perpage, 'skip' => $start);
			$cursor = $emps->db->find($this->table_name, $params);
		
			$this->total = $emps->db->found_rows;
			$this->pages = $emps->count_pages($this->total);
		
			$smarty->assign("pages", $this->pages);

			$lst = array();
			
			foreach($cursor as $ra){
				$ra = $emps->db->safe_array($ra);
				$ra = $this->handle_row($ra);
				$lst[] = $ra;
			}
			
			$emps->loadvars();
			
			$smarty->assign("lst", $lst);

		}		
	}
		
	public function handle_request(){
	// the main entry point for request processing
		global $emps, $smarty, $ss, $key;

		$emps->page_property('ited', true);		
		
		$emps->loadvars();
		if($key){
			$this->ref_id = $emps->db->oid($key);
			if($this->use_context){
				$this->context_id = $emps->p->get_context($this->table_name, $this->ref_sub, $this->ref_id);		
			}
		}

		if($key == 'ajax'){
			return $this->handle_ajax();
		}
		
		$smarty->assign("form", $this->form_name);
		
		if($this->immediate_add){
			$emps->loadvars();
			$smarty->assign("fastadd", 1);
			$key = ""; $ss = "";
			$smarty->assign("def_addfast", $emps->clink("part=add"));
			$emps->loadvars();
		}		
		
		if($emps->auth->credentials($this->credentials) || $this->override_credentials){
			if($ss && !isset($_REQUEST['action_kill'])){
				$this->handle_detail_mode();
			}else{
				$this->handle_list_mode();
			}
		}else{
			$emps->deny_access("AdminNeeded");
		}
	}

}
?>