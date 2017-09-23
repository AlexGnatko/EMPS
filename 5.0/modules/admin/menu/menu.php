<?php
require_once $emps->common_module('ited/ited.class.php');

class EMPS_MenuEditor extends EMPS_ImprovedTableEditor {
	public $ref_sub = 1;

	public $table_name = "emps_menu";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/menu,form";	
	
	public $order = " order by ord asc ";
	
	public $v;	
	
	public $multilevel = true;

	public $parent_filed = "id";
	
	public $pads = array(
		'info'=>'Общие сведения',
		'props'=>'Свойства',
		'photos'=>'Изображения'
		);
		

	public function __construct(){
		parent::__construct();
	}	
		
	public function handle_row($ra){
		global $emps,$ss,$key;
		
		return parent::handle_row($ra);
	}
}

$ited = new EMPS_MenuEditor;

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;
$ited->use_context = true;

$ited->doc_filter = "ord:i,enabled:i,parent:i";

$ited->pads = $emps->pad_menu("db:_admin/menu,padmenu");

$perpage = 50;

$ited->where = array('website_ctx' => $emps->website_ctx);

$emps->loadvars();

$cur_grp = $sk;

$cur_parent = $sd;

if($sk){
	if($sk == '00'){
		$sk = '';
	}
	$ited->where['grp'] = $sk;
}

if($sd){
	$ited->where['parent'] = intval($sd);
	
	$parent = $ited->get_row($ited->where['parent']);
	
	$smarty->assign("parent", $parent);
	
}else{
    $ited->where['$or'] = [
            ['parent' =>
                ['$exists' => false]
            ],
            ['parent' => 0]
        ];
	//$ited->where['parent'] = array('$exists' => false);
}

//dump($ited->where);

$sd = "";
$smarty->assign("totop", $emps->elink());
$emps->loadvars();

$params = array();
$params['query'] = $ited->where;
$params['options'] = array('sort' => array('ord' => -1));
$last_row = $emps->db->get_row($ited->table_name, $params);
if($last_row){
	$next_ord = $last_row['ord'] + 100;
}else{
	$next_ord = 100;
}
$smarty->assign("next_ord", $next_ord);

if($_POST){
	if(!$_POST['ord']){
		$_POST['ord'] = $next_ord;
	}
}

$params = array('query' => array());
$cursor = $emps->db->distinct($ited->table_name, "grp", $params);

$grp = array();
$emps->clearvars();

$pp = "admin-menu";

foreach($cursor as $ra){
	$a = array();
	$a['name'] = $ra;
	$sk = $ra;
	if(!$sk){
		$sk = '00';
	}
	$sd = '';
	$a['link'] = $emps->elink();
	if(($cur_grp == $ra) && $cur_grp != ''){
		$a['sel'] = true;
	}
	if(($ra == "") && $cur_grp == '00'){
		$a['sel'] = true;
	}
	if(!$a['name']){
		$a['name'] = "_nocode";
	}
	
	$grp[]=$a;
}

$a = array();
$a['name'] = "_all";
$emps->loadvars();
$sk = '';
$sd = '';
$a['link'] = $emps->elink();
if($cur_grp == ''){
	$a['sel'] = true;
}
$grp[] = $a;

$smarty->assign("grp", $grp);		


$emps->loadvars();
if($sk == '00'){
	$sk = '';
}

if($sk){
	$_POST['grp'] = $sk;
}

if($_POST['action_add']){
	$_POST['enabled'] = 1;
}

if($sd){
	if($_POST['action_add']){
		$_POST['parent'] = $sd;
	}
	$mi = $ited->get_row($sd);
	if($mi){
		$_POST['grp'] = $mi['grp'];
	}
}

$ited->add_pad_template("admin/menu/pads,%s");

$ited->handle_request();
