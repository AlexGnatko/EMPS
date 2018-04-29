<?php
$emps->page_property("autoarray",1);



if($emps->auth->credentials("admin")):

require_once $emps->common_module('ited/ited.class.php') ;
require_once $emps->page_file_name('_items,items.class','controller');

$items = new EMPS_Items;

$items->pp = "toner";

$emps->page_property("adminpage",1);

class EMPS_ItemsEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_WS_STRUCTURE;
	public $ref_sub = 1;

	public $track_props = P_WS_STRUCTURE;	

	public $table_name = "ws_structure";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/structure,form";	
	
	public $order = " order by ord desc, name asc ";
	
	public $pads = array(
		'info'=>'Общие сведения',
		'html'=>'Подробный текст',
		'objects'=>'Объекты',
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
		
		$ra = $items->explain_structure_node($ra);
		
		return parent::handle_row($ra);
	}
};



$ited = new EMPS_ItemsEditor;

$ited->what = " d.* ";
$ited->join = " as d ";
$ited->where = " where 1=1 ";

if($_GET['clear_search']){
    unset($_SESSION['structure_search']);
    $emps->redirect_elink();exit();
}

if($_POST['post_filt']){
    $_SESSION['structure_filt'] = $_POST['filt'];
    $emps->redirect_elink();exit();
}

if($_POST['post_search']){
    $_SESSION['structure_search'] = $_POST['search'];
    $emps->redirect_elink();exit();
}

if($_SESSION['structure_search']){
    $search = $_SESSION['structure_search'];
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

if($_SESSION['structure_filt']){
    $filt = $_SESSION['structure_filt'];
    $smarty->assign("filt", $filt);
}

if($key){
    unset($_SESSION['structure_search']);
}

if($filt['category']){
    $id = intval($filt['category']);
    $ited->where .= " and d.parent = {$id} ";
}


$emps->uses_flash();

$ited->ref_id = $key;

$ited->add_pad_template("admin/structure/pads,%s");

$emps->page_property('calendar',1);
$emps->page_property('lightbox',1);

if($_POST['time']){
	$_REQUEST['cdt']=$emps->parse_time($_REQUEST['time']);
}


$perpage=50;

$ited->handle_request();

else:
	$emps->deny_access("AdminNeeded");
endif;
?>