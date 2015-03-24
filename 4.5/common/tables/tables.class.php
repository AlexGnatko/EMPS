<?php
class EMPS_Tables {
	public $editlink = "%s";
	public $viewlink = "%s";
	public $corelink = "";	
	
	function text_parents($table_name,$parent){
		global $emps;
		$ra=$emps->db->get_row($table_name,"id=$parent");
		$txt="";
		while(true){
			$txt.=" - ".$ra['name'];
			if(!$ra['parent']) break;
			$ra=$emps->db->get_row($table_name,"id=".$ra['parent']);
			
		}
		return $txt;
	}

	function list_parents($tn,$id){
		global $emps;
		$lst=array();
		$row=$emps->db->get_row($tn,"id=$id");
		if($row){
			$row['editlink']=sprintf($this->editlink,$id);
			$row['viewlink']=sprintf($this->viewlink,$id);			
			$lst[]=$row;
			$lst=array_merge($this->list_parents($tn,$row['parent']),$lst);
			return $lst;	
		}
		return array();
	}
	
}
?>