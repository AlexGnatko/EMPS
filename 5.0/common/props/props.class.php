<?php
class EMPS_PropertiesEditor {
	public $table_name = "";
	public $ref_id = 0;
	public $row = array();
	public $skip = "";
	
	public function closed_field($name){
		if(!isset($this->skip_arr)){
			$skip = $this->skip;
			if($skip){
				$skip .= ",";
			}
			$skip .= "_id,parent,cdt,dt,website_ctx,id";
			$this->skip_arr = explode(",", $skip);
		}
		reset($this->skip_arr);
		foreach($this->skip_arr as $v){
			if(trim($v) == $name){
				return true;
			}
		}
		return false;
	}
	
	public function detect_type($val){
		$type = 't';
		if(is_integer($val)){
			$type = 'i';
		}
		if(is_float($val)){
			$type = 'f';
		}
		if(is_bool($val)){
			$type = 'b';
		}
		if(is_object($val)){
			$type = 'o';
		}
		return $type;
	}
	
	public function handle_request(){
		global $emps, $sd, $smarty;
		
		$emps->uses_flash();
		

		if($_GET['kill']){
			$params = array();
			$params['query'] = array("_id" => $this->ref_id);
			$params['update'] = array('$unset' => array($_GET['kill'] => ''));
			$emps->db->update_one($this->table_name, $params);
		}
		
		if($_POST['post_saveset']){
			$code = $_POST['code'];
			$type = $_POST['type'];
			$value = $_POST['value'];
			
			$a = array($code => $value);
			$s = $code.":".$type;
			
			$params = array();
			$params['query'] = array("_id" => $this->ref_id);
			$params['update'] = array('$set' => $emps->prepare_doc($a, $s));
			$emps->db->update_one($this->table_name, $params);
			$emps->redirect_elink();exit();	
		}
		
		if($_POST['post_import']){
			$lst = json_decode($_POST['import_json'], true);
			
			$set = array();
			foreach($lst as $v){
				$code = $v['code'];
				$type = $v['type'];
				$value = $v['value'];
				
				$a = array($code => $value);
				$s = $code.":".$type;
				
				$f = $emps->prepare_doc($a, $s);
				$set[$code] = $f[$code];
			}

			$params = array();
			$params['query'] = array("_id" => $this->ref_id);
			$params['update'] = array('$set' => $set);
			$emps->db->update_one($this->table_name, $params);

			$emps->redirect_elink();exit();
		}
		
		if($_POST['post_delete']){
			$params = array();
			$params['query'] = array("_id" => $this->ref_id);
			$params['update'] = array('$unset' => $_POST['sel']);
			$emps->db->update_one($this->table_name, $params);
			
			$list = "";
			while(list($n,$v)=each($_POST['sel'])){
				if($list != ""){
					$list .= ", ";
				}
				$list .= $n;
			}
			$emps->flash("killedcode", $list);
			$emps->redirect_elink(); exit();
		}
		
		if(!$this->row){
			$params = array();
			$params['query'] = array('_id' => $this->ref_id);
			$this->row = $emps->db->get_row($this->table_name, $params);
		}
		
		if($_POST['post_export']){
			$lst = array();
			foreach($this->row as $n => $v){
				$a = array();
				if($this->closed_field($n)){
					continue;
				}
				if(!$_POST['sel'][$n]){
					continue;
				}
				$a['code'] = $n;
				$a['value'] = $v;
				$a['type'] = $this->detect_type($v);
				$lst[] = $a;
			}
			$smarty->assign("exportcode", json_encode($lst));
		}
		
		if($_GET['field']){
			$field = $_GET['field'];
			if(isset($this->row[$field])){
				if(!$this->closed_field($field)){
					$arr = array();
					$arr['code'] = $field;
					$arr['type'] = 't';
					$arr['value'] = $this->row[$field];
					$arr['type'] = $this->detect_type($arr['value']);
					$smarty->assign("set", $arr);
					$smarty->assign("KillLink", $emps->clink('kill='.$arr['code']));
				}
			}
		}
		
		$lst = array();
		foreach($this->row as $n => $v){
			$a = array();
			if($this->closed_field($n)){
				continue;
			}
			$a['code'] = $n;
			$a['value'] = $v;
			$a['link'] = $emps->clink("field=".$n);
			$lst[] = $a;
		}
		$smarty->assign("lst", $lst);		
	}
}
?>