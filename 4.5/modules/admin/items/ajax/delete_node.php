<?php

$node=$_REQUEST['node'];

function delete_structure_node($node,$table){
	global $emps;
	$r=$emps->db->query("select * from ".TP.$table." where parent=$node");
	while($ra=$emps->db->fetch_named($r)){
		delete_structure_node($ra['id'],$table);
	}
	$emps->db->query("delete from ".TP.$table." where id=$node");
}

delete_structure_node($node,$this->structure_table_name);

echo "DELETED";
