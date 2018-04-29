<?php

$parent=$_REQUEST['node'];

$r=$emps->db->query("select * from ".TP.$this->structure_table_name." where parent=$parent order by ord asc,id asc");
$lst=array();
while($ra=$emps->db->fetch_named($r)){
	$lst[]=$ra;
}

$smarty->assign("lst",$lst);

$smarty->assign("treeitem",$this->ajax_template('treeitem','view'));

$smarty->display($this->ajax_template('subtree','view'));

?>