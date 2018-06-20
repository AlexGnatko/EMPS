<?php

$node = intval($_REQUEST['node']);

$node = $emps->db->get_row($this->structure_table_name, "id = {$node}");
if($node){
	echo $node['id'].'|'.$node['name'];
}
