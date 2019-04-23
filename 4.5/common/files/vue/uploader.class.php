<?php

require_once $emps->common_module('uploads/uploads.class.php');

class EMPS_VuePhotosUploader {
    public $context_id = 0;
    public $up;

    public $can_save = true;

    public $files = [];

    public function __construct()
    {
        $this->up = new EMPS_Uploads;
    }

    public function handle_upload()
    {
        global $emps, $emps_no_exit;

        $this->files = $this->list_uploaded_files();

        foreach($_FILES as $v){
            if($v['name'][0]){
                if(!$v['error'][0]){
                    if(true){
                        $q = "select max(ord) from ".TP."e_files where context_id = {$this->context_id}";
                        $r = $emps->db->query($q);
                        $ra = $emps->db->fetch_row($r);
                        $ord = $ra[0];

                        $context_id = $this->context_id;

                        $nr = [];
                        $nr['md5'] = md5(uniqid(time()));
                        $nr['file_name'] = $v['name'][0];
                        $nr['content_type'] = $v['type'][0];
                        $nr['size'] = $v['size'][0];
                        $nr['context_id'] = $context_id;
                        $nr['user_id'] = $emps->auth->USER_ID;
                        $nr['ord'] = $ord + 100;
                        $emps->db->sql_insert_row("e_files", ['SET' => $nr]);
                        $file_id = $emps->db->last_insert();
                        $oname = $this->up->upload_filename($file_id,DT_FILE);

                        move_uploaded_file($v['tmp_name'][0], $oname);

                        $row = $emps->db->get_row("e_files","id = {$file_id}");
                        if($row){
                            $row = $this->up->file_extension($row);
                            $file = array();
                            $emps->copy_values($file, $row, "file_name,descr,comment,content_type,ord,id");
                            $file['name'] = $row['file_name'];
                            $file['size'] = intval($row['size']);
                            $urlpart = $row['md5']."/".$row['file_name']."&dt=".$row['dt'];
                            $file['url'] = "/retrieve/".$urlpart;
                            $file['viewUrl'] = "/filepic/".$urlpart;

                            $this->files[] = $file;
                        }
                    }
                }
            }
        }

        $response = [];
        $response['code'] = "OK";
        $response['can_save'] = $this->can_save;
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

        $type = "application/octet-stream";

        $headers = get_headers($url, 1);

        foreach ($headers as $header) {
            if (stristr($header, "Content-Type")) {
                $x = explode(":", $header);
                $xx = explode(";", $x[1]);
                $type = trim($xx[0]);
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
            $nr['file_name'] = $filename;
        }

        $nr['content_type'] = $type;
        $emps->db->sql_update_row("e_files", ['SET' => $nr], "id = {$id}");
        $oname = $this->up->upload_filename($id,DT_FILE);
        file_put_contents($oname, $data);
        $size = filesize($oname);
        $emps->db->query("update " . TP . "e_files set size = {$size} where id = {$id}");

        $this->handle_list();
    }

    public function handle_reupload($id){
        global $emps;

        $id = intval($id);

        if ($this->can_save) {
            foreach($_FILES as $v){
                if($v['name'][0]){
                    $file = $emps->db->get_row("e_files", "id = {$id}");

                    $nr = [];
                    $nr['file_name'] = $v['name'][0];
                    $nr['content_type'] = $v['type'][0];
                    $nr['size'] = $v['size'][0];
                    $emps->db->sql_update_row("e_files", ['SET' => $nr], "id = {$id}");
                    $oname = $this->up->upload_filename($id,DT_FILE);
                    move_uploaded_file($v['tmp_name'][0], $oname);
                }
            }
        }

        $this->handle_list();
    }

    public function list_uploaded_files() {
        global $emps;

        $r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from ".TP."e_files where 
        context_id = {$this->context_id} order by ord asc");

        $lst = [];

        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->up->file_extension($ra);
            $file = [];
            $emps->copy_values($file, $ra, "file_name,descr,comment,content_type,ord,id");
            $file['name'] = $ra['file_name'];
            $file['size'] = intval($ra['size']);
            $urlpart = "{$ra['md5']}/{$ra['file_name']}&dt={$ra['dt']}";
            $file['url'] = "/retrieve/" . $urlpart;
            $file['viewUrl'] = "/filepic/" . $urlpart;

            $lst[] = $file;
        }

        return $lst;
    }

    public function handle_list() {
        global $emps, $emps_no_exit;

        $response = [];
        $response['code'] = "OK";
        $response['can_save'] = $this->can_save;
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
            if ($_POST['post_upload_file']) {
                $this->handle_upload();
            }

            if ($_POST['post_reupload_file']) {
                $this->handle_reupload(intval($_POST['file_id']));
            }

            if ($_POST['post_reimport_file']) {
                $this->handle_reimport(intval($_POST['file_id']), $_POST['url']);
            }

            if ($_POST['post_save_file_description']) {
                $id = intval($_POST['file_id']);
                $nr = [];
                $emps->copy_values($nr, $_REQUEST,"descr,file_name,comment");
                error_log(json_encode($nr));
                $emps->db->sql_update_row("e_files", ['SET' => $nr], "id = {$id}");
                $this->handle_list();
            }

            if ($_POST['post_import_files']) {
                $x = explode("\n", $_POST['list']);

                $r = $emps->db->query("select max(ord) from ".TP."e_files where 
                        context_id = {$this->context_id}");
                $ra = $emps->db->fetch_row($r);
                $this->up->ord = $ra[0];
                foreach($x as $v){
                    $v = trim($v);
                    if($v){
                        $this->up->ord += 100;
                        $this->up->download_file($this->context_id, $v, false);
                    }
                }
                $this->handle_list();
            }

            if ($_GET['delete_uploaded_file']) {
                $id = $_GET['delete_uploaded_file'];
                $r = $emps->db->query("select * from ".TP."e_files 
                            where context_id = {$this->context_id} and id in ({$id})");
                while($ra = $emps->db->fetch_named($r)){
                    $this->up->delete_file($ra['id'], DT_FILE);
                }
                $this->handle_list();
            }

            if ($_GET['reorder_files']) {
                $x = explode(",", $_GET['reorder_files']);
                $ord = 100;
                foreach($x as $id) {
                    $id = intval($id);
                    $nr = [];
                    $nr['ord'] = $ord;
                    $emps->db->sql_update_row("e_files", ['SET' => $nr], "id = {$id} and context_id = {$this->context_id} ");
                    //error_log("Updated: {$id} to ord = {$ord}");
                    $ord += 100;
                }
                $this->handle_list();
            }
        }else{
            $response = [];
            $response['code'] = "Error";
            $response['message'] = "You are not allowed to upload or edit files here.";

            $emps->json_response($response); exit;

        }

        if ($_GET['list_uploaded_files']) {
            $this->handle_list();
        }

    }
}