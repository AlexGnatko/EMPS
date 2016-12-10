<?php
require_once($emps->common_module('photos/photos.class.php'));

class EMPS_BlueimpUploader {
	public $context_id = 0;
	public $p;
	
	public $can_save = true;
	
	public $jlst;
	
	public function __construct(){
		$this->p = new EMPS_Photos;
	}
	
	public function handle_reupload($id){
		global $emps;
		$id = intval($id);
		while(list($n,$v)=each($_FILES)){
			if($v['name']){
				$file=$emps->db->get_row("e_uploads","id=".$id);
				$_REQUEST=$file;
				$_REQUEST['filename']=$v['name'];
				$_REQUEST['type']=$v['type'];
				$_REQUEST['size']=$v['size'];
				$_REQUEST['thumb']=EMPS_PHOTO_SIZE;
				$_REQUEST['context_id']=$this->context_id;
				$_REQUEST['dt'] = time();
				$emps->db->sql_update("e_uploads","id=".$id);
				$oname=$this->p->up->upload_filename($id,DT_IMAGE);
				$this->p->delete_photo_files($id);
				move_uploaded_file($v['tmp_name'],$oname);
				$row=$emps->db->get_row("e_uploads","id=".$id);
				$fname=$this->p->thumb_filename($id);
				$this->p->treat_upload($oname,$fname,$row);
			}
		}	
	}	
	
	public function handle_post(){
		global $emps,$SET;

		if($_POST['save'] && !$_POST['upload']){
			while(list($n,$v)=each($_POST['save'])){
				$SET=array();
				$SET['descr']=$_POST['descr'][$n];
				$SET['ord']=$_POST['ord'][$n];
				$emps->db->sql_update("e_uploads","id=$n");
			}
			$emps->redirect_elink();exit();
		}
				
		if($_POST['postkill']){
			if($_POST['sel']){
				while(list($n,$v)=each($_POST['sel'])){
					$this->p->delete_photo($n);
				}
			}
		}
		
		if($_POST['post_descr']){
			$id = intval($_POST['id']);
			if($id){
				$emps->db->query("update ".TP."e_uploads set descr='".$emps->db->sql_escape($_POST['descr'])."' where id=".$id);
				$emps->redirect_elink();exit();
			}
		}
		
		if($_POST['post_reupload']){
			$id = intval($_POST['id']);
			if($id){
				$this->handle_reupload($id);
				$emps->redirect_elink();exit();
			}
		}
		
		if($_POST['post_by_url']){
			$x = explode("\n", $_POST['links']);
			foreach($x as $v){
				$v = trim($v);
				if($v){
					$this->p->download_image($this->context_id, $v);
				}
			}
			$emps->redirect_elink();exit();			
		}
		
		if($_POST['upload']){
			$this->handle_upload();
		}
		
	}
	
	public function handle_upload(){
		global $emps;
		
		while(list($n,$v)=each($_FILES)){
//			echo "D: \r\n";
//dump($v);
			if($v['name'][0]){
				if(!$v['error'][0]){
					if(strstr($v['type'][0],"jpeg") || strstr($v['type'][0],"gif") || strstr($v['type'][0],"png") || strstr($v['type'][0],"svg")){
						$q = "select max(ord) from ".TP."e_uploads where context_id=".$this->context_id;
						$r=$emps->db->query($q);
						$ra=$emps->db->fetch_row($r);
						$ord=$ra[0];
						
						$context_id = $this->context_id;
		
						$_REQUEST['md5']=md5(uniqid(time()));
						$_REQUEST['filename']=$v['name'][0];
						$_REQUEST['type']=$v['type'][0];
						$_REQUEST['size']=$v['size'][0];
						$_REQUEST['descr']=$_POST['title'][0];
						$_REQUEST['thumb']=EMPS_PHOTO_SIZE;
						$_REQUEST['context_id']=$context_id;
						$_REQUEST['qual'] = 100;
						$_REQUEST['ord']=$ord+10;
						$emps->db->sql_insert("e_uploads");
						$file_id=$emps->db->last_insert();
						$oname=$this->p->up->upload_filename($file_id,DT_IMAGE);
			
						move_uploaded_file($v['tmp_name'][0],$oname);
		
						$row=$emps->db->get_row("e_uploads","id=$file_id");
						if($row){
							$fname=$this->p->thumb_filename($file_id);	
							$this->p->treat_upload($oname,$fname,$row);
							

							$row = $this->p->image_extension($row);
							$j=array();
							$j['name']=$row['filename'];
							$j['size']=$row['size']+0;
							$j['url']="/pic/".$row['md5'].".".$row['ext']."&dt=".$row['dt'];
							$j['thumbnailUrl']="/freepic/".$row['md5'].".".$row['ext']."?size=120x90&opts=inner&dt=".$row['dt'];
							$j['deleteUrl']="./?delete=".$row['id'];
							$j['fileId']=$row['id'];
							$j['deleteType']="GET";
							$j['descr']=$row['descr'];
							$j['ord']=$row['ord'];
							$j['qual'] = $row['qual'];
							
							$this->jlst[]=$j;							
						}

					}
				}
			}
		}	
		
		$emps->no_smarty=true;
		header("Content-Type: application/json; charset=utf-8");

		$a = array();
		$a['files'] = $this->jlst;
		echo json_encode($a);		
		exit();
//						file_put_contents(EMPS_SCRIPT_PATH.'/POST2.txt',ob_get_clean(),FILE_APPEND);			
	}		

	public function handle_request($context_id){
		global $emps,$ss,$start,$perpage,$smarty;
		
		$emps->uses_flash();
		
		$this->context_id = $context_id;
		
		$this->jlst=array();		
		
		if($_GET['links']){
			$emps->no_smarty = true;
			
			$id = intval($_GET['links']);
			$row = $emps->db->get_row("e_uploads","id=$id");
			$row = $this->p->image_extension($row);			
			$smarty->assign("row",$row);
			$smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
			$smarty->display("db:photos/blueimp/links");
			exit();
		}
		
		if($_GET['resize16x9']){
			$id = intval($_GET['resize16x9']);
			$mode = $_GET['mode'];
			$this->p->resize_16x9($id, $mode);
			$emps->redirect_elink();exit();
		}
		
		if($_GET['qual']){
			$id = intval($_GET['qual']);
			$mode = intval($_GET['mode']);
			$this->p->set_quality($id, $mode);
			$emps->redirect_elink();exit();
		}
		
		if($_GET['add_watermark']){
			$emps->no_smarty = true;
			$id = intval($_GET['add_watermark']);
			$this->p->ensure_watermark($id);
			echo "OK";
			exit();
		}
		
		if($_GET['remove_watermark']){
			$emps->no_smarty = true;
			$id = intval($_GET['remove_watermark']);
			$this->p->cancel_watermark($id);			
			echo "OK";
			exit();
		}			
		
		if($_GET['add_tilt']){
			$emps->no_smarty = true;
			$id = intval($_GET['add_tilt']);
			$this->p->ensure_tilt($id, floatval($_GET['angle']));
			echo "OK";
			exit();
		}
		
		if($_GET['remove_tilt']){
			$emps->no_smarty = true;
			$id = intval($_GET['remove_tilt']);
			$this->p->cancel_tilt($id);			
			echo "OK";
			exit();
		}			
		

		if(isset($_REQUEST['reorder_files'])) {
			$files = $_REQUEST['p'];
			$ord = 10;
			foreach($files as $file_id) {
				$emps->db->query(sprintf("update ".TP."e_uploads set ord=%d where id=%d ",
					$ord,
					intval($file_id)));
				$ord+=10;
			}
			exit;
		}							
		
		if($_POST){
			if($this->can_save){
				$this->handle_post();
			}
		}
		
		if($this->can_save){
			if($_GET['delete']){
				$this->p->delete_photo($_GET['delete']+0);
				$r = array("status"=>"OK");
				echo json_encode($r);
				exit();
			}
		}
		
		$r=$emps->db->query('select SQL_CALC_FOUND_ROWS * from '.TP.'e_uploads where context_id='.$this->context_id." order by ord asc");
		
		$lst=array();

		while($ra=$emps->db->fetch_named($r)){
			$ra = $this->p->image_extension($ra);
			$ss=$ra['id'];
			$ra['ilink']=$emps->elink();
			$emps->loadvars();
			$ra['klink']=$emps->clink("kill=".$ra['id']);
			$ra['time']=$emps->form_time($ra['dt']);
			
			$j=array();
			$j['name']=htmlspecialchars($ra['filename']);
			$j['size']=$ra['size']+0;
			$j['url']="/pic/".$ra['md5'].".".$ra['ext']."&dt=".$ra['dt'];
			$j['fileId']=$ra['id'];
			$j['thumbnailUrl']="/freepic/".$ra['md5'].".".$ra['ext']."?size=120x90&opts=inner&dt=".$ra['dt'];
			$j['deleteUrl']="./?delete=".$ra['id'];
			$j['deleteType']="GET";
			$j['descr']=$ra['descr'];
			$j['ord']=$ra['ord'];			
			$j['qual'] = $ra['qual'];
			
			$this->jlst[]=$j;
		
			$lst[]=$ra;
		}
		$emps->loadvars();
		
		if($this->can_save){
			if($_REQUEST['upload']){
				$emps->no_smarty=true;
				header("Content-Type: application/json; charset=utf-8");
				$a = array();
				$a['files'] = $this->jlst;
				echo json_encode($a);
			}else{
				$smarty->assign("ilst",$lst);
			}
		}
		
	}
}

?>