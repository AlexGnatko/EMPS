<?php
require_once($emps->common_module('ited/ited.class.php'));
require_once($emps->common_module('videos/videos.class.php'));

class EMPS_ContentEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_CONTENT;
	public $ref_sub = CURRENT_LANG;

	public $track_props = P_CONTENT;	

	public $table_name = "e_content";
	
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
		$this->v = new EMPS_Videos;
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
$ited->where = " where context_id = ".$emps->website_ctx;

$_REQUEST['type']='p';

$ited->add_pad_template("admin/content/pads,%s");

$ited->handle_request();
?>