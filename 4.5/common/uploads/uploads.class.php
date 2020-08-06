<?php

class EMPS_Uploads
{
    public $UPLOAD_PATH;

    public $ord = 10;

    public function __construct()
    {
        global $emps;
        if(defined('EMPS_UPLOAD_PATH')){
            $this->UPLOAD_PATH = EMPS_UPLOAD_PATH;
        }else {
            $this->UPLOAD_PATH = EMPS_SCRIPT_PATH . EMPS_UPLOAD_SUBFOLDER;
        }

        $emps->p->register_cleanup(array($this, 'delete_files_context'));
    }

    public function current_folder()
    {
        $dt = time();
        $folder = "up" . floor($dt / (60 * 60 * 24 * 7));
        $fname = $this->UPLOAD_PATH . $folder;

        if (!file_exists($fname)) {
            mkdir($fname);
            mkdir($fname . "/thumb");
            chmod($fname, 0777);
            chmod($fname . "/thumb", 0777);
        }
        return $folder;
    }

    public function pick_folder($id, $mode)
    {
        global $emps;

        switch ($mode) {
            case DT_FILE:
                $tname = "e_files";
                break;
            case DT_STORAGE:
                $tname = "e_storage";
                break;
            case DT_IMAGE:
            case DT_IMAGEWM:
                $tname = "e_uploads";
                break;
            default:
                return false;
        }

        $file = $emps->db->get_row($tname, "id=$id");
        if (!$file) return false;

        if ($file['folder'] == "") {
            $folder = $this->current_folder();

            $table = TP . $tname;
            $emps->db->query("update $table set folder='$folder' where id=$id");
        } else {
            $folder = $file['folder'];
        }
        return $folder;
    }

    public function upload_filename($file_id, $mode)
    {
        $folder = $this->pick_folder($file_id, $mode);

        if (!$folder) return false;

        switch ($mode) {
            case DT_FILE:
                $pfx = "-file";
                $tname = "e_files";
                break;
            case DT_STORAGE:
                $pfx = "-stg";
                $tname = "e_storage";
                break;
            case DT_IMAGEWM:
                $pfx = "-wm";
                $tname = "e_uploads";
                break;
            case DT_IMAGE:
                $pfx = "-img";
                $tname = "e_uploads";
                break;
            default:
                return false;
        }

        $file_name = $this->UPLOAD_PATH . $folder . "/" . $file_id . $pfx . ".dat";
        return $file_name;
    }

    public function list_files($context_id, $limit)
    {
        global $emps;
        $lst = array();
        $sql_limit = "";
        if ($limit) {
            $sql_limit = " limit $limit ";
        }

        $r = $emps->db->query("select * from " . TP . "e_files where context_id=" . $context_id . " order by ord asc, id asc $sql_limit");
        while ($ra = $emps->db->fetch_named($r)) {
            $ra['fsize'] = format_size($ra['size']);
            $lst[] = $ra;
        }

        return $lst;
    }

    public function count_files($context_id){
        global $emps;

        $r = $emps->db->query("select count(*) from " . TP . "e_files where context_id=" . $context_id);
        $ra = $emps->db->fetch_row($r);

        return intval($ra[0]);
    }

    public function delete_file($file_id, $mode)
    {
        global $emps;

        $file_name = $this->upload_filename($file_id, $mode);
        unlink($file_name);

        switch ($mode) {
            case DT_FILE:
                $tname = "e_files";
                break;
            case DT_STORAGE:
                $tname = "e_storage";
                break;
            case DT_IMAGE:
            case DT_IMAGEWM:
//			$this->delete_photo($file_id);
                $tname = "e_uploads";
                break;
            default:
                return false;
        }
        $emps->db->query("delete from " . TP . $tname . " where id=$file_id");
    }

    public function delete_files_context($context_id)
    {
        global $emps;
        $r = $emps->db->query("select * from " . TP . "e_files where context_id=$context_id");
        while ($ra = $emps->db->fetch_named($r)) {
            $this->delete_file($ra['id'], DT_FILE);
        }
    }

    public function save_file($context_id, $file_name, $content_type, $comment, $data)
    {
        global $emps;

        $e_file_name = $emps->db->sql_escape($file_name);
        $e_content_type = $emps->db->sql_escape($content_type);
        $e_comment = $emps->db->sql_escape($comment);
        $row = $emps->db->get_row("e_files", "context_id=$context_id and file_name='$e_file_name' and content_type='$e_content_type' and comment='$e_comment'");
        if ($row) {
            $this->delete_file($row['id'], DT_FILE);
        }

        $_REQUEST['md5'] = md5(uniqid(time()));
        $_REQUEST['file_name'] = $file_name;
        $_REQUEST['context_id'] = $context_id;
        $_REQUEST['content_type'] = $content_type;
        $_REQUEST['size'] = strlen($data);
        $_REQUEST['comment'] = $comment;
        $emps->db->sql_insert("e_files");
        $file_id = $emps->db->last_insert();
        $xfname = $this->upload_filename($file_id, DT_FILE);

        file_put_contents($xfname, $data);

    }

    public function load_file($context_id, $file_name)
    {
        global $emps;

        $e_file_name = $emps->db->sql_escape($file_name);
        $row = $emps->db->get_row("e_files", "context_id=$context_id and file_name='$e_file_name'");
        if ($row) {
            $fname = $this->upload_filename($row['id'], DT_FILE);
            $data = file_get_contents($fname);
            return $data;
        }
        return false;
    }

    public function file_extension($ra)
    {

        $x = explode(".", $ra['file_name']);
        $ra['ext'] = $x[count($x) - 1];

        return $ra;
    }

    public function download_file($context_id, $url, $filename)
    {
        global $emps, $SET;

        if (!$filename) {
            $a = parse_url($url);
            $path = $a['path'];
            $x = explode("/", $path);
            $filename = urldecode($x[count($x) - 1]);
        }

        $data = file_get_contents($url);

        $headers = get_headers($url, 1);

        $type = $headers['Content-Type'];
        if (!$type) {
            $type = "application/octet-stream";
        }

        $SET = array();
        $SET['md5'] = md5(uniqid(time()));
        if ($this->use_md5) {
            $SET['md5'] = $this->use_md5;
        }
        $SET['file_name'] = $filename;
        if ($this->download_filename) {
            $SET['file_name'] = $this->download_filename;
        }
        $SET['content_type'] = $type;
        $SET['context_id'] = $context_id;
        $SET['size'] = strlen($data);
        $SET['user_id'] = $emps->auth->USER_ID;
        if($this->set_comment){
            $SET['comment'] = $this->set_comment;
        }
        $SET['ord'] = $this->ord;
        $emps->db->sql_insert("e_files");
        $file_id = $emps->db->last_insert();

        $xfname = $this->upload_filename($file_id, DT_FILE);

        file_put_contents($xfname, $data);
    }

    public function import_files($context_id, $data)
    {
        global $SET, $emps;
        $this->delete_files_context($context_id);

        $SET = array();
        foreach ($data as $pic) {
            $ord = $pic['ord'];
            $type = $pic['content_type'];
            $name = $pic['file_name'];
            $descr = $pic['descr'];
            $md5 = $pic['md5'];
            $size = $pic['size'];
            $url = $pic['url'];

            if (!$url) continue;

            $row = $emps->db->get_row("e_files", "md5='$md5'");
            if ($row) {
                $nr = array();
                $nr['file_name'] = $name;
                $nr['descr'] = $descr;
                $nr['content_type'] = $type;
                $nr['size'] = $size;
                $nr['ord'] = $ord;
                $emps->db->sql_update_row("e_files", ['SET' => $nr], "id=" . $row['id']);

                $file_id = $row['id'];
            } else {
                $nr = array();
                $nr['md5'] = $md5;
                $nr['file_name'] = $name;
                $nr['descr'] = $descr;
                $nr['content_type'] = $type;
                $nr['size'] = $size;
                $nr['context_id'] = $context_id;
                $nr['ord'] = $ord;
                $emps->db->sql_insert_row("e_files", ['SET' => $nr]);
                $file_id = $emps->db->last_insert();
            }

            $oname = $this->upload_filename($file_id, DT_FILE);

            $data = file_get_contents($url);
            file_put_contents($oname, $data);

        }
    }

    public function copy_files($old_context_id, $new_context_id) {
        global $emps;

        $lst = $this->list_files($old_context_id, 1000000);
        foreach ($lst as $file) {
            $nr = $file;
            unset($nr['id']);
            unset($nr['cdt']);
            unset($nr['dt']);
            $nr['context_id'] = $new_context_id;
            $nr['md5'] = md5(uniqid(time().$file['id']));
            $emps->db->sql_insert_row("e_files", ['SET' => $nr]);
            $file_id = $emps->db->last_insert();

            $old_name = $this->upload_filename($file['id'], DT_FILE);
            $new_name = $this->upload_filename($file_id, DT_FILE);

            copy($old_name, $new_name);
        }
    }
}
