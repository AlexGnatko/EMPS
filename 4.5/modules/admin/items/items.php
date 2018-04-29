<?php
$emps->page_property("tinymce",1);
$emps->page_property("calendar",1);
$emps->page_property("treeview",1);

require_once($emps->common_module('ited/ited.class.php'));
require_once($emps->page_file_name('_items,items.class','controller'));

class EMPS_ItemsEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_WS_ITEM;
	public $ref_structure_type = DT_WS_STRUCTURE;
	public $ref_sub = CURRENT_LANG;

	public $track_props = P_WS_ITEM;	

	public $table_name = "ws_items";
	public $structure_table_name = "ws_structure";
	public $link_table_name = "ws_items_structure";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/items,form";	
	
	public $order = " order by cdt asc, id asc ";
	
	public $immediate_add = true;
	
	public $items;
	
	public $pads = array(
		'info'=>'Общие сведения',
		'html'=>'Тексты описаний'
		);
		
	public $p;
		
	public function __construct(){
		$this->items = new EMPS_Items;
		$this->p = new EMPS_Photos;		
		parent::__construct();
	}
		
	public function handle_row($ra){
		global $emps,$ss,$key;
		
		$emps->clearvars();
		$ss="info";
	
		$emps->loadvars();
		$key=$ra['id'];
		$ss="info";
		$ra['nlink']=$emps->elink();	
		$emps->loadvars();	
		
		$ra=$this->items->explain_item($ra);
		
		return parent::handle_row($ra);
	}
	
	public function handle_filter($code){
		global $emps,$smarty,$pp,$key,$ss;
		$x=explode(',',$code);
		while(list($n,$v)=each($x)){
			$xx=explode('-',$v);
			$this->filter[$xx[0]]=$xx[1];
		}
		if($this->filter['node']){
			$node_id=$this->filter['node']+0;
			$node=$emps->db->get_row('ws_structure',"id=$node_id");
			if($node){
				$emps->clearvars();
				$pp="admin-structure";$key=$node['id'];$ss="info";
				$node['ilink']=$emps->elink();
				$emps->loadvars();
				$smarty->assign("node",$node);
				$_REQUEST['node_id']=$node_id;
				$this->where.=' and node_id='.$node_id;
			}
		}
	}
	
	public function handle_request(){
		global $smarty,$key,$sd,$sk,$emps;
		
		$this->where=' where 1=1 ';
		
		$emps->loadvars();
		
		if($sk){
			$this->handle_filter($sk);
		}
		
		if($_GET['search']){
			$smarty->assign("search",$_GET['search']);
			$this->where.=" and name like ('%".$_GET['search']."%') ";
		}		

		parent::handle_request();
	}
	
	public function handle_redirect(){
		global $ss;
		if($_POST['post_save_return']){
			$ss="";
			
		}
		
		parent::handle_redirect();
	}
	
}

$ited = new EMPS_ItemsEditor;

$ited->ref_id = $_REQUEST['item']+0;

$node_id = $_GET['node']+0;
$smarty->assign("node_id",$node_id);

$item_id = $_GET['item']+0;
$smarty->assign("item_id",$item_id);

$nlst = array();
while(true){
	$node = $emps->db->get_row("ws_structure","id=".$node_id);
	$nlst[] = $node;
	if(!$node['parent']){
		break;
	}
	$node_id = $node['parent'];
}

$nlst = array_reverse($nlst);
$smarty->assign("nlst",$nlst);

$ited->add_pad_template("admin/items/pads,%s");
$ited->add_ajax_template("admin/items/ajax,%s");

if($_POST['time']){
	$_REQUEST['cdt']=$emps->parse_time($_REQUEST['time']);
}

$perpage=50;

$ited->handle_request();
?>