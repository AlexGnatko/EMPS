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


if($_POST['post_export']){
	$data = array();

	foreach($_POST['sel'] as $n => $v){
		$id = intval($n);
		$row = $emps->db->get_row("e_content", "id = ".$id);
		if($row){
			$row = $ited->handle_row($row);
			$context_id = $emps->p->get_context(DT_CONTENT, CURRENT_LANG, $row['id']);
			$pics = $ited->v->p->list_pics($context_id, 10000);
			$a = array();
			$emps->copy_values($a, $row, "uri,type,title,descr,html");
			$pl = array();
			foreach($pics as $pic){
				$b = array();
				$emps->copy_values($b, $pic, "descr,md5,ord,type,size,filename,wmark");
				$b['url'] = EMPS_SCRIPT_WEB.'/pic/'.$b['md5'].'.'.$pic['ext'];
				$pl[] = $b;
			}
			$a['pics'] = $pl;
			
			$data[] = $a;
		}
	}

	$smarty->assign("data", json_encode($data));
	$emps->no_smarty = true;
	$smarty->display("db:_admin/content,export");
}

if($_POST['post_import']){
	$lst = json_decode($_POST['json'], true);
	foreach($lst as $v){
		$uri = $emps->db->sql_escape($v['uri']);
		$row = $emps->db->get_row("e_content", "uri = '".$uri."' and context_id = ".$emps->website_ctx);
		$SET = $v;
		$SET['context_id'] = $emps->website_ctx;
		if($row){
			$emps->db->sql_update("e_content", "id = ".$row['id']);
			$id = $row['id'];
		}else{
			$emps->db->sql_insert("e_content");
			$id = $emps->db->last_insert();
		}
		$context_id = $emps->p->get_context($ited->ref_type, $ited->ref_sub, $id);
		$emps->p->save_properties($v, $context_id, $ited->track_props);
		
		$ited->v->p->import_photos($context_id, $v['pics']);
	}
}

$_REQUEST['type']='p';

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
?>