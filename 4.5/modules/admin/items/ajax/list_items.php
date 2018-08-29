<?php

global $SET, $perpage, $start, $start2;

$perpage = 20;

$onode = $_REQUEST['node'];

$node = intval($_REQUEST['node']);
$item_id = intval($_REQUEST['item']);

if($_REQUEST['set_search'] && $_POST['search']){
	$_SESSION['items_search'] = $_POST['search'];
	$smarty->assign("ReturnSinkMode",1);		
	$smarty->assign("backlink", $emps->clink('view_search=1'));
}

if(!$item_id){
	// list mode
	$smarty->assign("elink", $emps->clink('node='.$onode));
	$smarty->assign("alink", $emps->clink('node=all'));
	
	if($_POST['post_add']){
		$smarty->assign("AddSinkMode",1);
		$item = false;
		
		if($item){
			$id = $item['id'];
		}else{
			$emps->db->sql_insert($this->table_name);
			$id = $emps->db->last_insert();
			$item = $emps->db->get_row($this->table_name,"id=$id");
			$SET = $item;
			$SET['name'] = $this->new_item." ".$id;
			$emps->db->sql_update($this->table_name, "id=$id");
		}
		
		$this->items->ensure_item_in_node($id, $node);
		
		$emps->loadvars();
		$editlink = $emps->clink('node='.$onode.'&item='.$item['id']);
		$smarty->assign("editlink", $editlink);
	}
	
	if($_POST['post_copy']){
		$smarty->assign("ReturnSinkMode",1);
		$smarty->assign("backlink",$emps->clink('node='.$onode));

		foreach($_POST['sel'] as $n => $v){
			if($_POST['removeall']) {
                $emps->db->query("delete from " . TP . $this->link_table_name . " where item_id=" . intval($n));
            }elseif($_POST['removesel']) {
                foreach ($_POST['othernode'] as $other_node) {
                    $emps->db->query("delete from " . TP . $this->link_table_name . " where item_id=" . intval($n) . " and structure_id = " . intval($other_node));
                }
            }elseif($_POST['togglepub']){
			    $item_id = intval($n);
			    $nr = [];
			    $row = $emps->db->get_row($this->table_name, "id = {$item_id}");
			    if($row){
			        if($row['pub']){
			            $nr['pub'] = "00";
                    }else{
			            $nr['pub'] = 10;
                    }
                    $emps->db->sql_update_row($this->table_name, ['SET' => $nr], "id = {$item_id}");
                }
			}else{
				foreach($_POST['othernode'] as $nn => $vv){
					$this->items->ensure_item_in_node($n, $nv);
				}
			}
		}
	}elseif($_POST['post_import']){
		$smarty->assign("ReturnSinkMode",1);
		$smarty->assign("backlink", $emps->clink('node='.$onode));
		
		$this->items->import_items($_POST['import'], $onode);
	}else{
		$node_id = $node;
		$smarty->assign("node_id", $node_id);
		if($node && $node!=-1){
			$node=$emps->db->get_row($this->structure_table_name, "id = {$node}");
			if($node){
				$smarty->assign("node", $node);
			}
		}
		
		$start2 = intval($start2);
		
		if($node_id == -1){
			$r = $emps->db->query("select SQL_CALC_FOUND_ROWS items.*,itst.structure_id from ".TP.$this->table_name." as items
				left join ".TP.$this->link_table_name." as itst
				on itst.item_id=items.id
				having itst.structure_id is null
				order by items.name asc, items.cdt desc, items.id desc limit {$start2}, {$perpage}");
			
		}elseif($_REQUEST['node'] == 'all'){
			$r = $emps->db->query("select SQL_CALC_FOUND_ROWS items.* from ".TP.$this->table_name." as items
				order by items.cdt desc, items.id desc limit {$start2}, {$perpage}");
		}elseif($_REQUEST['view_search']==1){
			$txt = $_SESSION['items_search'];
			$txt = trim($txt);
			
			if($txt){
				$ptxt="%".str_replace(" ", " % ", $txt)."%";
				$rtxt="*".str_replace(" ", " * ", $txt)."*";
			}
			$article = "article";
			if($this->article){
			    $article = $this->article;
            }
			$q = "select SQL_CALC_FOUND_ROWS i.*, (match(i.name,p.v_char,p.v_text,p.v_data) against ('$rtxt' in boolean mode)) as rel,(i.name like ('$ptxt')) as namel,(i.{$article} like ('$ptxt')) as articlel, 
			 ctx.ref_type as ref_type, ctx.ref_id as ref_id from ".TP.$this->table_name." as i
		
			 left join ".TP."e_contexts as ctx
			 on (ctx.ref_id  = i.id and ctx.ref_type = ".$this->ref_type.")
			 left join ".TP."e_properties as p
			 on p.context_id = ctx.id
			 
			 group by ref_id
			 
			 having rel>0 or namel>0 or articlel>0 order by rel desc limit 100";
	 			
			$r=$emps->db->query($q);			
		}else{
			$r=$emps->db->query("select SQL_CALC_FOUND_ROWS items.* from ".TP.$this->table_name." as items
				join ".TP.$this->link_table_name." as itst
				on itst.item_id=items.id
				and itst.structure_id=$node_id
				order by items.ord desc, items.cdt desc, items.id desc limit {$start2}, {$perpage}");
//				echo $emps->db->sql_error();
		}
		
		$lst = array();
		$emps->loadvars();
		$start = "list-items";
		$emps->savevars();
		$emps->page_var='start2';
		$emps->no_autopage = true;

		$emps->page_clink = "node=".$onode;
	
		$pages=$emps->count_pages($emps->db->found_rows());
	
		$smarty->assign("pages",$pages);
		
		while($ra=$emps->db->fetch_named($r)){
			$ra = $this->items->explain_item($ra);
			$emps->loadvars();			
			$ra['editlink'] = $emps->clink('node='.$onode."&item=".$ra['id']);
			$lst[] = $ra;
		}

		$smarty->assign("lst",$lst);
	}
	
	
}else{
	// edit mode	
	require_once($emps->common_module('photos/uploader.class.php'));

	$perpage = 1000;
	$xs = $start;
		
	$photos = new EMPS_PhotosUploader;
	$photos->photo_size = $this->photo_size;
	$photos->no_post_redirect = true;
	$od = $_REQUEST['descr'];
	unset($_REQUEST['descr']);
	$photos->handle_request($this->context_id);
	$_REQUEST['descr'] = $od;
	
	$emps->loadvars();
	$emps->changevar('start', $xs);
		
	if($_POST['post_save']){
		if($_POST['pic_take']){
		    foreach($_POST['pic_take'] as $n => $v){
				$descr = $emps->db->sql_escape($_POST['pic_descr'][$n]);
				$ord = $_POST['pic_ord'][$n]+0;
				$emps->db->query("update ".TP."e_uploads set descr = '{$descr}', ord = {$ord} where id = {$n}");
			}
		}
		
		$savenodes = [];
		foreach($_POST['savenode'] as $n => $v){
			if($v){
				$savenodes[$v] = true;
			}
		}
        foreach($_POST['newnode'] as $n => $v){
			if($v){
				$savenodes[$v] = true;
			}
		}		
		
		if($_POST['country_name']){
			$_REQUEST['country'] = $this->items->ensure_country($_POST['country_name']);
		}

		$emps->all_post_required();
		
		$this->items->update_item($item_id);
		$this->items->update_nodes($item_id, $savenodes);
		
		if($_POST['post_save_return']){
			$smarty->assign("ReturnSinkMode",1);		
			$smarty->assign("backlink", $emps->clink('node='.$onode));
		}else{
			$smarty->assign("SaveSinkMode",1);
			$smarty->assign("editlink", $emps->clink('node='.$onode.'&item='.$item_id));
		}
	}elseif($_POST['post_kill']){
		$smarty->assign("ReturnSinkMode",1);		
		$this->items->delete_item($item_id);
		$smarty->assign("backlink", $emps->clink('node='.$onode));
	}else{
		if($_GET['kill']==1){
			$smarty->assign("ReturnSinkMode",1);		
			$this->items->delete_item($item_id);
			$smarty->assign("backlink", $emps->clink('node='.$onode));
		}elseif($_GET['remove']==1){
			$smarty->assign("ReturnSinkMode",1);		
			$this->items->remove_item_from_node($item_id,$node);
			$smarty->assign("backlink", $emps->clink('node='.$onode));
			
			
		}else{
			

			$pic = $_GET['kill_pic'];
			if($pic){
				$photos->p->delete_photo($pic);
			}
			$smarty->assign("EditMode",1);
			$emps->loadvars();
			$smarty->assign("elink",$emps->clink('node='.$onode.'&item='.$item_id));	
			$smarty->assign("backlink",$emps->clink('node='.$onode));	
		
			$row = $emps->db->get_row($this->table_name,"id=$item_id");
			$row = $this->items->explain_item($row);
			
			$row['nodes'] = $this->items->list_nodes_ex($item_id, true);
			
			$row['pics'] = $photos->p->list_pics($this->context_id, 1000);
			
			$smarty->assign("row",$row);	
		}
	}
}


$smarty->display($this->ajax_template('list-items', 'view'));

