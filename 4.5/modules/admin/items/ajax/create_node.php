<?php
global $SET;

$parent=$_REQUEST['parent'];

$r=$emps->db->query("select max(ord) from ".TP.$this->structure_table_name." where parent=$parent");
$ra=$emps->db->fetch_row($r);
$_REQUEST['ord']=$ra[0]+100;

$emps->db->sql_insert($this->structure_table_name);
$id=$emps->db->last_insert();
$this->after_insert($id);

$node=$emps->db->get_row($this->structure_table_name,"id=$id");

$SET=$node;
unset($SET['id']);
$SET['name']="Новый элемент №".$id;
$SET['full_id']=$emps->get_full_id($id,$this->structure_table_name,'parent','ord');
$emps->db->sql_update($this->structure_table_name,"id=$id");
$node=$emps->db->get_row($this->structure_table_name,"id=$id");

$smarty->assign("node",$node);

$smarty->display($this->ajax_template('treeitem','view'));

?>