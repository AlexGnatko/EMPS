<?php
require_once $emps->common_module('photos/photos.class.php');

class EMPS_BlueimpUploader {
	public $p, $up;
	
	public $can_save = true;
	
	public $jlst;
	
	public function __construct(){
		$this->p = new EMPS_Photos;
		$this->up = $this->p->up;
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
				$_REQUEST['context_id'] = $emps->db->oid($this->context_id);
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
			$emps->redirect_elink();exit;
		}
				
		if($_POST['postkill']){
			if($_POST['sel']){
				while(list($n,$v)=each($_POST['sel'])){
					$this->p->delete_photo($n);
				}
			}
		}
		
		if($_POST['post_descr']){
			$id = $_POST['id'];
			if($id){
                $this->p->up->update_file($id, ['descr' => $_POST['descr']]);
				$emps->redirect_elink();exit;
			}
		}
		
		if($_POST['post_reupload']){
			$id = intval($_POST['id']);
			if($id){
				$this->handle_reupload($id);
				$emps->redirect_elink();exit;
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
			$emps->redirect_elink();exit;
		}
		
		if($_POST['upload']){
			$this->handle_upload();
		}
		
	}
	
	public function handle_upload(){
		global $emps;

        $ord = 10;
        $lst = $this->up->list_files_ex(['context_id' => $this->context_id, 'ut' => 'i'], ['limit' => 1, 'sort' => ['ord' => -1]]);

        foreach($lst as $f){
            $ord = $f['ord'] + 10;
        }

		while(list($n,$v)=each($_FILES)){
//			echo "D: \r\n";
//dump($v);
			if($v['name'][0]){
				if(!$v['error'][0]){
					if(strstr($v['type'][0],"jpeg") || strstr($v['type'][0],"gif") || strstr($v['type'][0],"png") || strstr($v['type'][0],"svg")){

						$context_id = $this->context_id;

                        $data = [];
                        $data['ut'] = 'i';
                        $data['uniq_md5'] = md5(uniqid(time().$v['name'][0].$v['size'][0]));
                        $data['filename'] = $data['uniq_md5']."-".$data['ut'];
                        $data['orig_filename'] = $v['name'][0];
                        $data = $this->up->file_extension($data);
                        $data['context_id'] = $emps->db->oid($context_id);
                        $data['content_type'] = $v['type'][0];
                        $data['user_id'] = $emps->auth->USER_ID;
                        $data['qual'] = 100;
                        $data['thumb'] = EMPS_PHOTO_SIZE;
                        $data['ord'] = $ord;
                        $ord += 10;

                        $file_id = $this->p->new_photo($v['tmp_name'][0], $data);

                        unlink($v['tmp_name'][0]);

                        $lst = $this->up->list_files_ex(['_id' => $file_id, 'ut' => 'i'], ['limit' => 1, 'sort' => ['ord' => -1]]);

                        foreach($lst as $row){
                            $j = array();
                            $j['name'] = $row['orig_filename'];
                            $j['size'] = intval($row['length']);
                            $j['url'] = "/pic/".$row['filename']."/".urlencode($row['orig_filename'])."&dt=".$row['dt'];

                            $j['thumbnailUrl'] = "/freepic/".$row['filename']."/".$row['orig_filename']."?size=120x90&opts=inner&dt=".$row['dt'];
                            $j['deleteUrl'] = "./?delete_photo=".$row['_id'];
                            $j['fileId'] = $row['_id'];
                            $j['deleteType'] = "_GET";
                            $j['descr'] = $row['descr'];
                            $j['comment'] = $row['comment'];
                            $j['qual'] = $row['qual'];
                            $j['ord'] = $row['ord'];

                            $this->jlst[] = $j;
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
		global $emps, $smarty;
		
		$emps->uses_flash();
		
		$this->context_id = $context_id;
		
		$this->jlst=array();		
		
		if($this->can_save){
		
			if($_GET['links']){
				$emps->no_smarty = true;
				
				$id = $_GET['links'];
                $row = $this->p->up->file_info($id);
				$smarty->assign("row", $row);
				$smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
				$smarty->display("db:photos/blueimp/links");
				exit();
			}
			
			if($_GET['resize16x9']){
				$id = $_GET['resize16x9'];
				$mode = $_GET['mode'];
				$this->p->resize_16x9($id, $mode);
				$emps->redirect_elink();exit();
			}
			
			if($_GET['qual']){
				$id = $_GET['qual'];
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
				$this->handle_post();
			}

			if($_GET['delete_photo']){
				$this->p->delete_photo($_GET['delete_photo']);
				$r = array("status" => "OK");
				echo json_encode($r);
				exit();
			}
			
		}

        $lst = $this->up->list_files($this->context_id, 'i', 0);

        foreach($lst as $ra){
			$j = array();
			$j['name'] = htmlspecialchars($ra['orig_filename']);
			$j['size'] = intval($ra['length']);
			$j['url'] = "/pic/".$ra['filename']."/".$ra['orig_filename']."&dt=".$ra['dt'];
			$j['fileId'] = $ra['_id'];

			$j['thumbnailUrl'] = "/freepic/".$ra['filename']."/".$ra['orig_filename']."?size=120x90&opts=inner&dt=".$ra['dt'];
			$j['deleteUrl'] = "./?delete_photo=".$ra['_id'];
			$j['deleteType'] = "GET";
			$j['descr'] = $ra['descr'];
			$j['ord'] = $ra['ord'];
			$j['qual'] = $ra['qual'];
			
			$this->jlst[] = $j;
		
			$lst[] = $ra;
		}
		$emps->loadvars();
		
		if($_REQUEST['upload']){
			$emps->no_smarty = true;
			header("Content-Type: application/json; charset=utf-8");
			$a = array();
			$a['files'] = $this->jlst;
			echo json_encode($a);
		}else{
			$smarty->assign("ilst", $lst);
		}
		
	}
}
