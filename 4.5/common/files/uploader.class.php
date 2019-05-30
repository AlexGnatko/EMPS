<?php
require_once $emps->common_module('uploads/uploads.class.php');

class EMPS_FilesUploader
{
    public $context_id = 0;
    public $up;

    public function __construct()
    {
        $this->up = new EMPS_Uploads;
    }

    public function handle_save()
    {
        global $emps, $SET;
        reset($_POST['item']);
        $SET = array();
        foreach ($_POST['item'] as $n => $v) {
            $SET['comment'] = $_POST['comment'][$n];
            $SET['ord'] = $_POST['ord'][$n];
            $emps->db->sql_update("files", "id=$n");
        }
    }

    public function handle_upload()
    {
        global $emps;
        foreach ($_FILES as $n => $v) {
            if ($v['name']) {
                $_REQUEST['md5'] = md5(uniqid());
                $_REQUEST['file_name'] = $v['name'];
                $_REQUEST['context_id'] = $this->context_id;
                $_REQUEST['content_type'] = $v['type'];
                $_REQUEST['size'] = $v['size'];
                $_REQUEST['user_id'] = $emps->auth->USER_ID;
                $emps->db->sql_insert("files");
                $file_id = $emps->db->last_insert();
                $fname = $this->up->upload_filename($file_id, DT_FILE);
                move_uploaded_file($v['tmp_name'], $fname);
            }
        }
    }

    public function handle_reupload()
    {
        global $emps;
        foreach ($_FILES as $v) {
            if ($v['name']) {
                $file = $emps->db->get_row("files", "id=" . $_POST['file_id']);
                $_REQUEST = $file;
                $_REQUEST['file_name'] = $v['name'];
                $_REQUEST['content_type'] = $v['type'];
                $_REQUEST['size'] = $v['size'];
                $emps->db->sql_update("files", "id=" . $_POST['file_id']);
                $fname = $this->up->upload_filename($_POST['file_id'], DT_FILE);
                move_uploaded_file($v['tmp_name'], $fname);
            }
        }
    }

    public function handle_post()
    {
        if ($_POST['postsave']) {
            $this->handle_save();
        }

        if ($_POST['postkill'] && !$_POST['postsave']) {
            foreach ($_POST['sel'] as $n => $v) {
                $this->up->delete_file($n, DT_FILE);
            }
        }
        if ($_POST['upload']) {
            $this->handle_upload();
        }

        if ($_POST['reupload']) {
            $this->handle_reupload();
        }
    }

    public function handle_request($context_id)
    {
        global $emps, $ss, $start, $perpage, $smarty, $pp, $key;

        $emps->uses_flash();

        $this->context_id = $context_id;

        $emps->loadvars();

        $smarty->assign("user_id", $emps->auth->USER_ID);

        $fl = array();
        for ($i = 0; $i < 5; $i++) {
            $e['id'] = $i + 1;
            $fl[] = $e;
        }

        $smarty->assign("fl", $fl);

        if ($_POST) {
            $this->handle_post();
            $emps->redirect_elink();
        }

        $start += 0;
        $perpage = 25;

        $r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from " . TP . "files where context_id=" . $this->context_id . " order by ord asc, id asc limit $start,$perpage");
        $smarty->assign("PrintPages", $emps->print_pages_found());
        $lst = array();
        while ($ra = $emps->db->fetch_named($r)) {
            $ra['time'] = $emps->form_time($ra['dt']);

            $emps->clearvars();
            $pp = "retrieve";
            $key = $ra['md5'];
            $ra['dlink'] = $emps->elink() . $ra['file_name'];
            $pp = "filepic";
            $ra['imlink'] = $emps->elink() . $ra['file_name'];
            $ra['file_name'] = $emps->kill_flood($ra['file_name'], 50);
            $lst[] = $ra;
        }
        $emps->loadvars();

        $smarty->assign("ilst", $lst);

    }
}
