<?php
class EMPS_TableEditor {
	public $table_name = false;
	
	public $what='*', $addjoin='', $tord='order by id asc', $addfilt='', $groupby='', $having='';
	
	public $website_ctx = 1;
	
	public $total, $pages;
	
	public function handle_post(){
		global $emps,$key;
		
		if($_POST['sadd'] || $_POST['sedit']){
			$_REQUEST['data']=serialize($_POST);
			unset($GLOBALS['data']);
		}
	
		if($_POST['sadd']){
			$id='';
			$_REQUEST['context_id']=$this->website_ctx;
			$emps->db->sql_insert($this->table_name);
			$this->post_save($emps->db->last_insert());
			$emps->loadvars();
		}
		
		if($key){
			if($_POST['confkill']){
				$this->handle_kill($id);
				
				$emps->db->query("delete from ".TP.$this->table_name." where id=$key");
	
				$this->post_kill($key);
	
				$key="";$emps->savevars();
			}
		}
		
		if($_POST['sedit']){
			$id='';
			$emps->db->sql_update($this->table_name,"id=$key");
			$this->post_save($key);
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
	
	public function handle_row($ra){
		global $key,$emps;
		$emps->loadvars();
		$key=$ra['id'];
		$ra['klink']=$emps->clink('kill=1');
		$emps->loadvars();
		return $ra;
	}
	
	public function handle_input($ra){
		return $this->handle_row($ra);
	}
	
	public function handle_request(){
		global $emps,$smarty,$key,$start,$perpage;
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
				$smarty->assign("key",$key);
				$smarty->assign("KillForm",1);
			}
		}
	
		if(!$start) $start=0;
		if(!$perpage) $perpage=25;
	
		$r=$emps->db->query('select SQL_CALC_FOUND_ROWS '.$this->what.' from '.TP.$this->table_name.' '.$this->addjoin.' where 1=1 '.$this->addfilt.' '.$this->groupby.' '.$this->having.' '.$this->tord." limit $start,$perpage");
		
		$this->total=$emps->db->found_rows();
		$this->pages=$emps->count_pages($this->total);
	
		$smarty->assign("pages",$this->pages);
		$lst=array();
		while($ra=$emps->db->fetch_named($r)){
			$key=$ra['id'];
			$ra['ilink']=$emps->elink();
			if($ra['name']=="") $ra['name']="-";
			if(isset($ra['data'])){
				$ra['data']=unserialize($ra['data']);
			}
			$ra=$this->handle_row($ra);
			$lst[]=$ra;
			$emps->loadvars();
		}
		$smarty->assign("lst",$lst);
		
		$emps->loadvars();
	
		if($key){
			$r=$emps->db->query("select * from ".TP.$this->table_name." where id=$key");
			$ra=$emps->db->fetch_named($r);
			if(isset($ra['data'])){			
				$ra['data']=unserialize($ra['data']);
			}
			$ra=$this->handle_input($ra);
			$smarty->assign("vle",$ra);
			$smarty->assign("EditMode",1);
		};
	
	}
}
?>