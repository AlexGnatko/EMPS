<?php
require_once($emps->common_module('uploads/uploads.class.php'));

class EMPS_BlueimpUploader {
	public $context_id = 0;
	public $up;
	
	public $jlst;
	
	public function __construct(){
		$this->up = new EMPS_Uploads;
	}
	
	public function handle_reupload($id){
		global $emps;
		$id = intval($id);
		while(list($n,$v)=each($_FILES)){
			if($v['name']){
				$file=$emps->db->get_row("e_files","id=".$id);
				$_REQUEST=$file;
				$_REQUEST['file_name']=$v['name'];
				$_REQUEST['content_type']=$v['type'];
				$_REQUEST['size']=$v['size'];
				$emps->db->sql_update("e_files","id=".$id);
				$fname=$this->up->upload_filename($id,DT_FILE);
				move_uploaded_file($v['tmp_name'],$fname);
			}
		}	
	}	
	
	public function handle_post(){
		global $emps,$SET;

	
		if($_POST['post_descr']){
			$id = intval($_POST['id']);
			if($id){
				if($_POST['descr']){
					$emps->db->query("update ".TP."e_files set descr='".$emps->db->sql_escape($_POST['descr'])."' where id=".$id);
				}
				if($_POST['comment']){
					$emps->db->query("update ".TP."e_files set comment='".$emps->db->sql_escape($_POST['comment'])."' where id=".$id);
				}
				if($_POST['file_name']){
					$emps->db->query("update ".TP."e_files set file_name='".$emps->db->sql_escape($_POST['file_name'])."' where id=".$id);
				}
				
				$emps->redirect_elink();exit();
			}
		}
		
		if($_POST['post_reupload']){
			$id = intval($_POST['id']);
			if($id){
				$this->handle_reupload($id);
//				exit();
				$emps->redirect_elink();exit();
			}
		}		
		
		if($_POST['post_by_url']){
			$x = explode("\n", $_POST['links']);
			foreach($x as $v){
				$v = trim($v);
				if($v){
					$this->up->download_file($this->context_id, $v);
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
		
//		dump($_FILES);
			
		while(list($n,$v)=each($_FILES)){
//			echo "D: \r\n";
//dump($v);
			if($v['name'][0]){
				if(!$v['error'][0]){
					$q = "select max(ord) from ".TP."e_files where context_id=".$this->context_id;
					$r=$emps->db->query($q);
					$ra=$emps->db->fetch_row($r);
					$ord=$ra[0];
					
					$context_id = $this->context_id;
						
					if($this->other_context){
						$oc = $_POST['post_other_context'];
						if($oc){
							$oc = json_decode($oc, true);
							foreach($oc as $code => $list){
								foreach($list as $ocfile){
									if($ocfile['name'] == $v['name'][0]
									&& $ocfile['size'] == $v['size'][0]){
										$context_id = $this->other_context[$code];
									}
								}
							}
						}else{
						}
					}else{
					}

											
					$_REQUEST['md5']=md5(uniqid());
					$_REQUEST['file_name']=$v['name'][0];
					$_REQUEST['context_id']=$context_id;
					$_REQUEST['content_type']=$v['type'][0];
					$_REQUEST['size']=$v['size'][0];
					$_REQUEST['user_id']=$emps->auth->USER_ID;
					$_REQUEST['ord']=$ord+10;			
					
					$emps->db->sql_insert("e_files");
					$file_id=$emps->db->last_insert();
					$fname=$this->up->upload_filename($file_id,DT_FILE);
					move_uploaded_file($v['tmp_name'][0],$fname);
		
					$row=$emps->db->get_row("e_files","id=$file_id");
					if($row){
						$row = $this->up->file_extension($row);
						$j=array();
						$j['name']=$row['file_name'];
						$j['size']=$row['size']+0;
						$j['url']="/retrieve/".$row['md5']."/".urlencode($row['file_name']);
						$j['deleteUrl']="./?delete=".$row['id'];
						$j['fileId']=$row['id'];
						$j['deleteType']="GET";
						$j['descr']=$row['descr'];
						$j['comment']=$row['comment'];						
						$j['ord']=$row['ord'];
						
						$this->jlst[]=$j;							
					}
				}
			}
		}	
		
		$emps->no_smarty=true;
		header("Content-Type: application/json; charset=utf-8");
		$a = array();
		$a['files'] = $this->jlst;
		echo json_encode($a);		

//						file_put_contents(EMPS_SCRIPT_PATH.'/POST2.txt',ob_get_clean(),FILE_APPEND);			
		exit();						
	}		

	public function handle_request($context_id){
		global $emps,$ss,$start,$perpage,$smarty;
		
		$emps->uses_flash();
		
		$this->context_id = $context_id;
		
		$this->jlst=array();		
		
		if($_GET['links']){
			$emps->no_smarty = true;
			
			$id = intval($_GET['links']);
			$row = $emps->db->get_row("e_files","id=$id");
			$row = $this->up->file_extension($row);			
			$row['link'] = "/retrieve/".$row['md5']."/".urlencode($row['file_name']);
			$smarty->assign("row",$row);
			$smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
			$smarty->display("db:files/blueimp/links");
			exit();
		}		
		

		if(isset($_REQUEST['reorder_files'])) {
			$files = $_REQUEST['p'];
			$ord = 10;
			foreach($files as $file_id) {
				$emps->db->query(sprintf("update ".TP."e_files set ord=%d where id=%d ",
					$ord,
					intval($file_id)));
				$ord+=10;
			}
			exit;
		}							
		
		if($_POST){
			$this->handle_post();
		
		}
		
		if($_GET['delete']){
			$this->up->delete_file(intval($_GET['delete']), DT_FILE);
			$r = array("status"=>"OK");
			echo json_encode($r);
			exit();
		}
		
		$r=$emps->db->query('select SQL_CALC_FOUND_ROWS * from '.TP.'e_files where context_id='.$this->context_id." order by ord asc");
		
		$lst=array();

		while($ra=$emps->db->fetch_named($r)){
			$ra = $this->up->file_extension($ra);
			
			$ss=$ra['id'];
			$ra['ilink']=$emps->elink();
			$emps->loadvars();
			$ra['klink']=$emps->clink("kill=".$ra['id']);
			$ra['time']=$emps->form_time($ra['dt']);
			
			$j=array();
			$j['name']=htmlspecialchars($ra['file_name']);
			$j['size']=$ra['size']+0;
			$j['url']="/retrieve/".$ra['md5']."/".urlencode($ra['file_name']);
			$j['fileId']=$ra['id'];
			$j['deleteUrl']="./?delete=".$ra['id'];
			$j['deleteType']="GET";
			$j['descr']=$ra['descr'];
			$j['comment']=$ra['comment'];			
			$j['ord']=$ra['ord'];			
			
			$this->jlst[]=$j;
		
			$lst[]=$ra;
		}
		$emps->loadvars();
		
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

?>