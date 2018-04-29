<?php

function handle_price($qty, $id, $value){
	global $emps, $SET, $items;
	
	$row = $emps->db->get_row("ws_items", "id = ".$id);
	
	if($row){
		
		$price = $items->load_price($id, $qty);
		if($price){
			if(!$value){
				$items->clear_price($id, $qty);
			}else{
				$items->update_price($id, $qty, floatval($value));
			}
		}else{
			if($value){
				$items->update_price($id, $qty, floatval($value));
			}
		}
	}
}


if($emps->auth->credentials("admin")):
	$emps->page_property("ited", 1);

	require_once($emps->page_file_name('_items,items.class','controller'));
	
	$items = new EMPS_Items;
	
	if($_POST['post_filter']){
		$_SESSION['items_table_filter'] = $_POST['filt'];
		$emps->redirect_elink();exit();
	}
	
	if($_POST['post_values']){

		foreach($_POST['item'] as $n => $v){
			$id = intval($n);
            foreach($_POST['price'][$n] as $nn => $vv){
				handle_price(intval($nn), $id, $vv);
			}

		}
		
		$emps->redirect_elink();exit();
//exit();
	}
	
	$filt = $_SESSION['items_table_filter'];
	
	$addjoin = "";
	if($filt){
		if($filt['node_id']){
			$list = $items->list_child_nodes_self($filt['node_id']);
			$addjoin = " join ".TP."ws_items_structure as s on i.id = s.item_id and s.structure_id in ($list) ";
		}
		$smarty->assign("filt", $filt);
	}
	
	$perpage = 15;
	
	$start = intval($start);
	
	$r = $emps->db->query("select SQL_CALC_FOUND_ROWS i.* from ".TP."ws_items as i $addjoin where 1=1 order by name asc limit $start, $perpage");
	
	//echo $emps->db->sql_error();
	$pages = $emps->count_pages($emps->db->found_rows());
	$smarty->assign("pages", $pages);
	
	$lst = array();
	
	while($ra = $emps->db->fetch_named($r)){
		$ra = $items->explain_item($ra);
		$lst[] = $ra;
	}
	
	$sects = array();
	for($i=1; $i<=18; $i++){
		$a = array();
		$a['num'] = $i;
		$sects[] = $a;
	}
	$smarty->assign("sects", $sects);
	
	$smarty->assign("lst", $lst);
	
else:
	$emps->deny_access("AdminNeeded");
endif;
