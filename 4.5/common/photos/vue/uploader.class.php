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

        if ($_REQUEST['single_mode']) {
            foreach ($this->files as $file) {
                $this->p->delete_photo($file['id']);
            }
            $this->files = [];
        }

        foreach($_FILES as $v){
            if($v['name'][0]){
                if(!$v['error'][0]){
                    if(strstr($v['type'][0],"webp") || strstr($v['type'][0],"jpeg") || strstr($v['type'][0],"gif") || strstr($v['type'][0],"png") || strstr($v['type'][0],"svg")){
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
                        $nr['ord'] = $ord + 100;
                        $emps->db->sql_insert_row("e_uploads", ['SET' => $nr]);
                        $file_id = $emps->db->last_insert();
                        $oname = $this->p->up->upload_filename($file_id,DT_IMAGE);

                        move_uploaded_file($v['tmp_name'][0], $oname);

                        $row = $emps->db->get_row("e_uploads","id = {$file_id}");
                        if($row){

                            $fname = $this->p->thumb_filename($file_id);
                            $this->p->treat_upload($oname, $fname, $row);

                            $row = $this->p->explain_for_editor($row);

                            $file = array();
                            $emps->copy_values($file, $row, "filename,descr,ord,md5,qual,id,dt,ext,has_orig,has_mod,type,new_type,size,orig_size,mod_size");
                            $file['name'] = $row['filename'];
                            $file['md5'] = $row['md5'];
                            $file['size'] = intval($row['size']);
                            $file['url'] = "/pic/".$row['md5'].".".$row['ext']."?dt=".$row['dt'];
                            $file['thumbnail'] = "/freepic/".$row['md5'].".jpg?size=".
                                $this->thumb_size."&opts=inner&dt=".$row['dt'];
                            $file['dt'] = $row['dt'];
                            $file['ext'] = $row['ext'];
                            $file['has_orig'] = $row['has_orig'];

                            $this->files[] = $file;
                        }
                    }
                }
            }
        }

        $response = [];
        $response['code'] = "OK";
        $response['files'] = $this->files;
        $response['context_id'] = $this->context_id;
        $response['context'] = $emps->p->load_context($this->context_id);

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
            $ra = $emps->db->row_types("e_uploads", $ra);
            $ra = $this->p->explain_for_editor($ra);
            $file = [];
            $emps->copy_values($file, $ra, "filename,descr,ord,md5,qual,id,dt,ext,has_orig,has_mod,type,new_type,size,orig_size,mod_size");
            $file['name'] = $ra['filename'];
            $file['size'] = intval($ra['size']);
            $file['url'] = "/pic/{$ra['md5']}/{$ra['filename']}?dt={$ra['dt']}";
            $file['thumbnail'] = "/freepic/{$ra['md5']}.jpg?size={$this->thumb_size}&opts=inner&dt={$ra['dt']}";

            $lst[] = $file;
        }

        return $lst;
    }

    public function handle_list() {
        global $emps, $emps_no_exit;

        $response = [];
        $response['code'] = "OK";
        $response['files'] = $this->list_uploaded_files();
        $response['context_id'] = $this->context_id;
        $response['context'] = $emps->p->load_context($this->context_id);
        $emps->json_response($response);
        if (!$emps_no_exit) {
            exit;
        }
    }

    public function handle_zip() {

        $ids = $_GET['zip'];
        $x = explode(",", $ids);
        $lst = $this->list_uploaded_files();
        $files = [];
        foreach ($lst as $file) {
            foreach ($x as $zip_id) {
                if ($zip_id == $file['id']) {
                    $files[] = $file;
                }
            }
        }

        $tmpfname = tempnam($this->p->up->UPLOAD_PATH, "zip");

        $zip = new ZipArchive();
        if ($zip->open($tmpfname, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$tmpfname>\n");
        }

        $names = [];
        foreach ($files as $n => $file) {
            if (!isset($names[$file['filename']])) {
                $names[$file['filename']] = 1;
            } else {
                $names[$file['filename']]++;
            }
        }

        foreach ($files as $n => $file) {
            $filepath = $this->p->up->upload_filename($file['id'], DT_IMAGE);
            $filename = $file['filename'];
            if ($names[$filename] > 1) {
                $filename = $names[$filename] . " " . $filename;
                $names[$filename]--;
            }
            $zip->addFile($filepath, $filename);
            $zip->setCompressionIndex($n, ZipArchive::CM_STORE);
        }
        $zip->close();

        $fh = fopen($tmpfname, "rb");

        $zip_filename = "Photos-" . $this->context_id . ".zip";

        if ($fh) {
            ob_end_clean();

            $size = filesize($tmpfname);

            if (class_exists('http\Env\Response')) {
                $body = new http\Message\Body($fh);
                $resp = new http\Env\Response;
                $resp->setContentType("application/octet-stream");
                $resp->setHeader("Content-Length", $size);
                $resp->setHeader("Last-Modified", date("r", time()));
                $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
                $resp->setContentDisposition(["attachment" => ["filename" => $zip_filename]]);
                $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
                $resp->setBody($body);
                //			$resp->setThrottleRate(50000, 1);
                $resp->send();
            } else {
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . $size);
                header("Last-Modified: ", date("r", time()));
                header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
                header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
                header("Content-Disposition: attachment; filename=\"" . $zip_filename . "\"");

                fpassthru($fh);
            }

            fclose($fh);
        }

        unlink($tmpfname);

    }

    public function handle_upload_zip() {
        global $emps;


        foreach($_FILES as $v){
            if($v['name'][0]){
                $zip = new ZipArchive;
                $source = $v['tmp_name'][0];

                $tmpfname = $this->p->up->UPLOAD_PATH."zip-upload-".md5(uniqid(time().$source));

                if (!file_exists($tmpfname)) {
                    mkdir($tmpfname);
                    chmod($tmpfname, 0777);
                }

                if ($zip->open($source) === TRUE) {
                    $zip->extractTo($tmpfname);
                    $zip->close();
                    $lst = scandir($tmpfname);
                    foreach ($lst as $s_name) {
                        if ($s_name == "." || $s_name == "..") {
                            continue;
                        }
                        if (is_dir($s_name)) {
                            continue;
                        }
                        $s_path = $tmpfname . "/" . $s_name;
                        $this->p->check_type = true;
                        $this->p->download_filename = $s_name;
                        $this->p->download_image($this->context_id, $s_path);
                        unlink($s_path);
                    }
                    unlink($tmpfname);
                }
            }
        }


        $this->handle_list();
    }

    public function handle_manipulate($file_id, $command) {
        if (isset($command['set_webp'])) {
            $this->p->set_webp($file_id, $command['set_webp']);
        }
        if (isset($command['set_quality'])) {
            $this->p->set_quality($file_id, $command['set_quality']);
        }
        if (isset($command['set_angle'])) {
            $this->p->ensure_tilt($file_id, -1 * floatval($command['set_angle']), false);
        }
        $this->handle_list();
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

            if ($_POST['post_upload_zip']) {
                $this->handle_upload_zip();
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

            if ($_POST['post_manipulate']) {
                $id = intval($_POST['photo_id']);
                $this->handle_manipulate($id, $_REQUEST['payload']);
            }

            if ($_POST['post_import_photos']) {
                $emps->no_time_limit();
                $x = explode("\n", $_POST['list']);

                $r = $emps->db->query("select max(ord) from ".TP."e_uploads where 
                        context_id = {$this->context_id}");
                $ra = $emps->db->fetch_row($r);
                $this->p->ord = $ra[0];
                foreach($x as $v){
                    $v = trim($v);
                    if($v){
                        $this->p->ord += 100;
                        $this->p->check_type = true;
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

        if ($_GET['zip']) {
            $this->handle_zip();
        }

        if ($_GET['list_uploaded_photos']) {
            $this->handle_list();
        }

    }
}