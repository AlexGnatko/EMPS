<?php
require_once $emps->common_module('uploads/uploads.class.php');

$emps->page_property("blueimp_uploader",true);

class EMPS_BlueimpUploader {
    public $context_id = 0;
    public $up;

    public $can_save = true;

    public $jlst;

    public function __construct(){
        $this->up = new EMPS_Uploads;
    }

    public function handle_reupload($id){
        global $emps;
        $file_id = $emps->db->oid($id);
        $file = $this->up->file_info($file_id);
        while(list($n,$v)=each($_FILES)){
            if($v['name']){

                $data = array();
                $emps->copy_values($data, $file, "ut,uniq_md5,filename,context_id,ord");

                $data['context_id'] = $emps->db->oid($data['context_id']);
                $this->up->delete_file($file_id);

                $data['content_type'] = $v['type'];
                $data['orig_filename'] = $v['name'];
                $data = $this->up->file_extension($data);
                $data['user_id'] = $emps->auth->USER_ID;

                $file_id = $this->up->new_file($v['tmp_name'], $data);

                unlink($v['tmp_name']);

            }
        }
    }

    public function handle_post(){
        global $emps;


        if($_POST['post_descr']){
            $file_id = $emps->db->oid($_POST['id']);

            $data = array();
            $emps->copy_values($data, $_POST, "descr, comment, orig_filename");

            if($data) {
                $this->up->update_file($file_id, $data);
            }
            $emps->redirect_elink();exit();
        }

        if($_POST['post_reupload']){
            $id = $_POST['id'];
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
                    $this->up->download_file($this->context_id, $v, false);
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

        $ord = 10;
        $lst = $this->up->list_files_ex(['context_id' => $this->context_id, 'ut' => 'f'], ['limit' => 1, 'sort' => ['ord' => -1]]);

        foreach($lst as $f){
            $ord = $f['ord'] + 10;
        }

        while(list($n,$v)=each($_FILES)){
//			echo "D: \r\n";
//dump($v);
            if($v['name'][0]){
                if(!$v['error'][0]){


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
                        }
                    }

                    $data = [];
                    $data['ut'] = 'f';
                    $data['uniq_md5'] = md5(uniqid(time().$v['name'][0].$v['size'][0]));
                    $data['filename'] = $data['uniq_md5']."-".$data['ut'];
                    $data['orig_filename'] = $v['name'][0];
                    $data = $this->up->file_extension($data);
                    $data['context_id'] = $emps->db->oid($context_id);
                    $data['content_type'] = $v['type'][0];
                    $data['user_id'] = $emps->auth->USER_ID;
                    $data['ord'] = $ord;
                    $ord += 10;

                    $file_id = $this->up->new_file($v['tmp_name'][0], $data);

                    unlink($v['tmp_name'][0]);

                    $lst = $this->up->list_files_ex(['_id' => $file_id, 'ut' => 'f'], ['limit' => 1, 'sort' => ['ord' => -1]]);

                    foreach($lst as $row){
                        $j = array();
                        $j['name'] = $row['orig_filename'];
                        $j['size'] = intval($row['length']);
                        $j['url'] = "/retrieve/".$row['filename']."/".urlencode($row['orig_filename']);
                        $j['deleteUrl'] = "./?delete_file=".$row['_id'];
                        $j['fileId'] = $row['_id'];
                        $j['deleteType'] = "_GET";
                        $j['descr'] = $row['descr'];
                        $j['comment'] = $row['comment'];
                        $j['ord'] = $row['ord'];

                        $this->jlst[] = $j;

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
        exit;
    }

    public function handle_request($context_id){
        global $emps, $ss, $smarty;

        $emps->uses_flash();

        $this->context_id = $context_id;

        $this->jlst = array();

        if($this->can_save){

            if($_GET['links']){
                $emps->no_smarty = true;

                $file_id = $emps->db->oid($_GET['links']);

                $row = $this->up->file_info($file_id);

                $row['link'] = "/retrieve/".$row['filename']."/".urlencode($row['orig_filename']);
                $smarty->assign("row", $row);
                $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
                $smarty->display("db:files/blueimp/links");
                exit;
            }


            if(isset($_REQUEST['reorder_files'])) {
                $files = $_REQUEST['p'];
                $ord = 10;
                foreach($files as $file_id) {
                    $this->up->update_file($file_id, ['ord' => $ord]);
                    $ord += 10;
                }
                exit;
            }

            if($_POST){
                $this->handle_post();

            }

            if($_GET['delete_file']){
                $this->up->delete_file($_GET['delete_file']);
                $r = ["status" => "OK"];
                echo json_encode($r);
                exit;
            }
        }

        $lst = $this->up->list_files($this->context_id, 'f', 0);

        foreach($lst as $ra){
            $ra['time'] = $emps->form_time($ra['dt']);

            $j = array();
            $j['name'] = htmlspecialchars($ra['orig_filename']);
            $j['size'] = intval($ra['length']);
            $j['url'] = "/retrieve/".$ra['filename']."/".urlencode($ra['orig_filename']);
            $j['fileId'] = $ra['_id'];
            $j['deleteUrl'] = "./?delete_file=".$ra['_id'];
            $j['deleteType'] = "GET";
            $j['descr'] = $ra['descr'];
            $j['comment'] = $ra['comment'];
            $j['ord'] = $ra['ord'];

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

