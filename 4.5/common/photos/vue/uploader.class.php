<?php

require_once $emps->common_module('photos/photos.class.php');

class EMPS_VuePhotosUploader {
    public $context_id = 0;
    public $p;

    public $can_save = true;

    public $photo_size = EMPS_PHOTO_SIZE;
    public $thumb_size = "300x300";

    public $files = [];

    public function __construct()
    {
        $this->p = new EMPS_Photos;
    }

    public function handle_upload()
    {
        global $emps, $emps_no_exit;

        $this->files = $this->list_uploaded_files();

        foreach($_FILES as $v){
            if($v['name'][0]){
                if(!$v['error'][0]){
                    if(strstr($v['type'][0],"jpeg") || strstr($v['type'][0],"gif") || strstr($v['type'][0],"png") || strstr($v['type'][0],"svg")){
                        $q = "select max(ord) from ".TP."e_uploads where context_id = {$this->context_id}";
                        $r = $emps->db->query($q);
                        $ra = $emps->db->fetch_row($r);
                        $ord = $ra[0];

                        $context_id = $this->context_id;

                        $nr = [];
                        $nr['md5'] = md5(uniqid(time()));
                        $nr['filename'] = $v['name'][0];
                        $nr['type'] = $v['type'][0];
                        $nr['size'] = $v['size'][0];
                        $nr['descr'] = $_POST['title'][0];
                        $nr['thumb'] = $this->photo_size;
                        $nr['context_id'] = $context_id;
                        $nr['qual'] = 100;
                        $nr['ord'] = $ord + 10;
                        $emps->db->sql_insert_row("e_uploads", ['SET' => $nr]);
                        $file_id = $emps->db->last_insert();
                        $oname = $this->p->up->upload_filename($file_id,DT_IMAGE);

                        move_uploaded_file($v['tmp_name'][0], $oname);

                        $row = $emps->db->get_row("e_uploads","id = {$file_id}");
                        if($row){
                            $fname = $this->p->thumb_filename($file_id);
                            $this->p->treat_upload($oname, $fname, $row);

                            $row = $this->p->image_extension($row);
                            $file = array();
                            $emps->copy_values($file, $row, "filename,descr,ord,qual,id");
                            $file['name'] = $row['filename'];
                            $file['size'] = intval($row['size']);
                            $file['url'] = "/pic/".$row['md5'].".".$row['ext']."&dt=".$row['dt'];
                            $file['thumbnail'] = "/freepic/".$row['md5'].".".$row['ext']."?size=".
                                $this->thumb_size."&opts=inner&dt=".$row['dt'];

                            $this->files[] = $file;
                        }
                    }
                }
            }
        }

        $response = [];
        $response['code'] = "OK";
        $response['files'] = $this->files;

        $emps->json_response($response);

        if (!$emps_no_exit) {
            exit;
        }
    }

    public function handle_reimport($id, $url){
        global $emps;

        $filename = false;

        $data = file_get_contents($url);
        if ($data === FALSE) {
            return false;
        }

        $type = "image/jpeg";

        $headers = get_headers($url, 1);

        foreach ($headers as $header) {
            if (stristr($header, "Content-Type")) {
                if (stristr($header, "png")) {
                    $type = "image/png";
                }
                if (stristr($header, "gif")) {
                    $type = "image/gif";
                }
            }
        }

        $path = parse_url($url, PHP_URL_PATH);

        $x = explode("/", $path);
        if (count($x) > 1) {
            $fn = trim($x[count($x) - 1]);
            if ($fn) {
                $filename = $fn;
            }
        }

        $nr = [];
        if ($filename) {
            $nr['filename'] = $filename;
        }

        $nr['type'] = $type;
        $nr['qual'] = 100;
        $emps->db->sql_update_row("e_uploads", ['SET' => $nr], "id = {$id}");
        $oname = $this->p->up->upload_filename($id,DT_IMAGE);
        $this->p->delete_photo_files($id);
        file_put_contents($oname, $data);
        $row = $emps->db->get_row("e_uploads", "id = {$id}");
        $fname = $this->p->thumb_filename($id);
        $this->p->treat_upload($oname, $fname, $row);

        $size = filesize($oname);
        $emps->db->query("update " . TP . "e_uploads set size=$size where id = {$id}");

        $this->handle_list();
    }

    public function handle_reupload($id){
        global $emps;

        $id = intval($id);

        foreach($_FILES as $v){
            if($v['name'][0]){
                $file = $emps->db->get_row("e_uploads", "id = {$id}");

                $nr = [];
                $nr['filename'] = $v['name'][0];
                $nr['type'] = $v['type'][0];
                $nr['size'] = $v['size'][0];
                $nr['thumb'] = $this->photo_size;
                $nr['qual'] = 100;
                $emps->db->sql_update_row("e_uploads", ['SET' => $nr], "id = {$id}");
                $oname = $this->p->up->upload_filename($id,DT_IMAGE);
                $this->p->delete_photo_files($id);
                move_uploaded_file($v['tmp_name'][0], $oname);
                //error_log("Moving uploaded file: {$v['tmp_name'][0]} to {$oname}");
                $row = $emps->db->get_row("e_uploads", "id = {$id}");
                $fname = $this->p->thumb_filename($id);
                $this->p->treat_upload($oname, $fname, $row);
            }
        }

        $this->handle_list();
    }

    public function list_uploaded_files() {
        global $emps;

        $r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from ".TP."e_uploads where 
        context_id = {$this->context_id} order by ord asc");

        $lst = [];

        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->p->image_extension($ra);
            $file = [];
            $emps->copy_values($file, $ra, "filename,descr,ord,qual,id");
            $file['name'] = $ra['filename'];
            $file['size'] = intval($ra['size']);
            $file['url'] = "/pic/{$ra['md5']}.{$ra['ext']}&dt={$ra['dt']}";
            $file['thumbnail'] = "/freepic/{$ra['md5']}.{$ra['ext']}?size={$this->thumb_size}&opts=inner&dt={$ra['dt']}";

            $lst[] = $file;
        }

        return $lst;
    }

    public function handle_list() {
        global $emps, $emps_no_exit;

        $response = [];
        $response['code'] = "OK";
        $response['files'] = $this->list_uploaded_files();
        $emps->json_response($response);
        if (!$emps_no_exit) {
            exit;
        }
    }

    public function handle_request()
    {
        global $emps;

        if ($this->can_save) {
            if ($_POST['post_upload_photo']) {
                $this->handle_upload();
            }

            if ($_POST['post_reupload_photo']) {
                $this->handle_reupload(intval($_POST['photo_id']));
            }

            if ($_POST['post_reimport_photo']) {
                $this->handle_reimport(intval($_POST['photo_id']), $_POST['url']);
            }

            if ($_POST['post_save_description']) {
                $id = intval($_POST['photo_id']);
                $nr = ['descr' => $_POST['descr']];
                $emps->db->sql_update_row("e_uploads", ['SET' => $nr], "id = {$id}");
                $this->handle_list();
            }

            if ($_POST['post_import_photos']) {
                $x = explode("\n", $_POST['list']);

                $r = $emps->db->query("select max(ord) from ".TP."e_uploads where 
                        context_id = {$this->context_id}");
                $ra = $emps->db->fetch_row($r);
                $this->p->ord = $ra[0];
                foreach($x as $v){
                    $v = trim($v);
                    if($v){
                        $this->p->ord += 100;
                        $this->p->download_image($this->context_id, $v);
                    }
                }
                $this->handle_list();
            }

            if ($_GET['delete_uploaded_photo']) {
                $id = $_GET['delete_uploaded_photo'];
                $r = $emps->db->query("select * from ".TP."e_uploads 
                            where context_id = {$this->context_id} and id in ({$id})");
                while($ra = $emps->db->fetch_named($r)){
                    $this->p->delete_photo($ra['id']);
                }
                $this->handle_list();
            }

            if ($_GET['reorder_photos']) {
                $x = explode(",", $_GET['reorder_photos']);
                $ord = 100;
                foreach($x as $id) {
                    $id = intval($id);
                    $nr = [];
                    $nr['ord'] = $ord;
                    $emps->db->sql_update_row("e_uploads", ['SET' => $nr], "id = {$id} and context_id = {$this->context_id} ");
                    error_log("Updated: {$id} to ord = {$ord}");
                    $ord += 100;
                }
                $this->handle_list();
            }
        }else{
            $response = [];
            $response['code'] = "Error";
            $response['message'] = "You are not allowed to upload or edit photos here.";

            $emps->json_response($response); exit;

        }

        if ($_GET['list_uploaded_photos']) {
            $this->handle_list();
        }

    }
}