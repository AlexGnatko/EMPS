<?php
require_once $emps->common_module('ited/ited.class.php');

class EMPS_ContentEditor extends EMPS_ImprovedTableEditor {
	public $ref_sub = 1;

	public $table_name = "emps_content";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/content,form";	
	
	public $order = " order by uri asc ";
	
	public $v;	
	
	public $pads = array(
		'info'=>'Общие сведения',
		'html'=>'HTML',
		'props'=>'Свойства',
		'photos'=>'Изображения',
		'files'=>'Файлы',
		'videos'=>'Видео'
		);
		

	public function __construct(){
		parent::__construct();
	}	
		
	public function handle_row($ra){
		global $emps,$ss,$key;
		
		$ra['name'] = $ra['uri'];
		
		return parent::handle_row($ra);
	}
}

$ited = new EMPS_ContentEditor;

$ited->ref_id = $key;
$ited->website_ctx = $emps->website_ctx;
$ited->where = array('website_ctx' => $emps->website_ctx);
$ited->use_context = true;


$_REQUEST['type'] = 'p';

$ited->add_pad_template("admin/content/pads,%s");

if($emps->lang == "en"){
	$ited->pads = array(
		'info'=>'General',
		'html'=>'HTML',
		'props'=>'Properties',
		'photos'=>'Images',
		'files'=>'Files',
		'videos'=>'Videos'
		);
}

$ited->handle_request();
