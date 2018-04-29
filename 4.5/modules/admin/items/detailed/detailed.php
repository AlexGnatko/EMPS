<?php
$emps->page_property("autoarray",1);



if($emps->auth->credentials("admin")):

require_once $emps->common_module('ited/ited.class.php') ;
require_once $emps->page_file_name('_items,items.class','controller');

$items = new EMPS_Items;

$emps->page_property("adminpage",1);

class EMPS_ItemsEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_WS_ITEM;
	public $ref_sub = 1;

	public $track_props = P_WS_ITEM;	

	public $table_name = "ws_items";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/items/detailed,form";	
	
	public $order = " order by ord desc, name asc ";
	
	public $pads = array(
		'info'=>'Общие сведения',
		'html'=>'Подробный текст',
		'props'=>'Свойства',
		'photos'=>'Фотографии',
		);
		
	public $p;
		
	public function __construct(){
		$this->p = new EMPS_Photos;	
		parent::__construct();
	}
	
	public function handle_post(){
		global $emps;
		$id=$this->ref_id;
		
		return parent::handle_post();
	}
		
	public function handle_row($ra){
		global $emps,$ss,$key,$start,$sd, $items;
		
		$ra = $items->explain_item($ra);
		
		$ra['nodes']=$items->list_nodes_ex($ra['id'], true);
		
		
		return parent::handle_row($ra);
	}
};



$ited = new EMPS_ItemsEditor;

$ited->items = $items;


$ited->what = " d.* ";
$ited->join = " as d ";

if($_GET['clear_search']){
	unset($_SESSION['detailed_search']);
	$emps->redirect_elink();exit();
}

if($_POST['post_filt']){
	$_SESSION['detailed_filt'] = $_POST['filt'];
	$emps->redirect_elink();exit();
}

if($_POST['post_search']){
	$_SESSION['detailed_search'] = $_POST['search'];
	$emps->redirect_elink();exit();
}

if($_SESSION['detailed_search']){
	$search = $_SESSION['detailed_search'];
	$search = trim($search);
	
	$smarty->assign("search", $search);
	
	$txt = $search;
	
	if($txt){
		$ptxt = "%".$emps->db->sql_escape("".str_replace(" ", "%", str_replace("-", "%", $txt))."")."%";
		$rtxt = $emps->db->sql_escape("+".str_replace(" ", " +", $txt)."");
		
		$ited->what = " d.*, sum((match(d.name) against ('$rtxt' in boolean mode))) as name_rel, sum((match(p.v_char,p.v_text,p.v_data) against ('$rtxt' in boolean mode))) as rel, sum((d.name like ('$ptxt'))) as namel, ctx.ref_type as ref_type, ctx.ref_id as ref_id ";
		$ited->join = " as d
		 left join ".TP."e_contexts as ctx
		 on (ctx.ref_id  = d.id and ctx.ref_type = ".$ited->ref_type.")
		 left join ".TP."e_properties as p
		 on p.context_id = ctx.id ";
		
		$ited->group = " group by d.id ";
		
		$ited->having = " having name_rel>0 or rel>0 or namel>0 ";
		
		$ited->order = " order by name_rel desc, rel desc, namel desc ";
	}
}

if($_SESSION['detailed_filt']){
	$filt = $_SESSION['detailed_filt'];
	$smarty->assign("filt", $filt);
}

if($key){
	unset($_SESSION['detailed_search']);
}

if($filt['category']){
	$id = intval($filt['category']);
	$ited->join .= " join ".TP."ws_items_structure as ist on
	d.id = ist.item_id
	and ist.structure_id = ".$id."
	";
}



$emps->uses_flash();

$ited->ref_id = $key;

$ited->add_pad_template("admin/items/detailed/pads,%s");

$emps->page_property('calendar',1);
$emps->page_property('lightbox',1);

if($_POST['time']){
	$_REQUEST['cdt']=$emps->parse_time($_REQUEST['time']);
}



$perpage=50;

$ited->handle_request();

//dump($emps->db->sql_errors);

else:
	$emps->deny_access("AdminNeeded");
endif;
