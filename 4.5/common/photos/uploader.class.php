<?php
require_once $emps->common_module('photos/photos.class.php');

class EMPS_PhotosUploader
{
    public $context_id = 0;
    public $p;
    public $img_format;
    public $no_post_redirect = false;

    public function __construct()
    {
        $this->p = new EMPS_Photos;
        $this->img_format = EMPS_PHOTO_SIZE;
    }

    public function handle_save()
    {
        global $emps, $SET;
        reset($_POST['descr']);
        $SET = array();
        while (list($n, $v) = each($_POST['descr'])) {
            $SET['descr'] = $v;
            $SET['ord'] = $_POST['ord'][$n];
            $emps->db->sql_update("e_uploads", "id=$n");
        }
    }

    public function handle_upload()
    {
        global $emps;
        while (list($n, $v) = each($_FILES)) {
            if ($v['name']) {
                if (!$v['error']) {
                    if (strstr($v['type'], "jpeg")) {
                        $q = "select max(ord) from " . TP . "e_uploads where context_id=" . $this->context_id;
                        $r = $emps->db->query($q);
                        $ra = $emps->db->fetch_row($r);
                        $ord = $ra[0];

                        $_REQUEST['md5'] = md5(uniqid(time()));
                        $_REQUEST['filename'] = $v['name'];
                        $_REQUEST['type'] = $v['type'];
                        $_REQUEST['size'] = $v['size'];
                        $_REQUEST['thumb'] = $this->img_format;
                        $_REQUEST['context_id'] = $this->context_id;
                        $_REQUEST['ord'] = $ord + 10;
                        $emps->db->sql_insert("e_uploads");
                        $file_id = $emps->db->last_insert();
                        $oname = $this->p->up->upload_filename($file_id, DT_IMAGE);

                        move_uploaded_file($v['tmp_name'], $oname);

                        $row = $emps->db->get_row("e_uploads", "id=$file_id");
                        if ($row) {
                            $fname = $this->p->thumb_filename($file_id);
                            $this->p->treat_upload($oname, $fname, $row);
                        }
                    }
                }
            }
        }
    }

    public function handle_reupload()
    {
        global $emps;
        while (list($n, $v) = each($_FILES)) {
            if ($v['name']) {
                $file = $emps->db->get_row("e_uploads", "id=" . $_POST['file_id']);
                $_REQUEST = $file;
                $_REQUEST['filename'] = $v['name'];
                $_REQUEST['type'] = $v['type'];
                $_REQUEST['size'] = $v['size'];
                $_REQUEST['thumb'] = EMPS_PHOTO_SIZE;
                $_REQUEST['context_id'] = $this->context_id;
                $emps->db->sql_update("e_uploads", "id=" . $_POST['file_id']);
                $oname = $this->p->up->upload_filename($_POST['file_id'], DT_IMAGE);
                $this->p->delete_photo_files($_POST['file_id']);
                move_uploaded_file($v['tmp_name'], $oname);
                $row = $emps->db->get_row("e_uploads", "id=" . $_POST['file_id']);
                $fname = $this->p->thumb_filename($_POST['file_id']);
                $this->p->treat_upload($oname, $fname, $row);
            }
        }
    }

    public function handle_post()
    {
        global $emps;

        if ($_POST['postkill']) {
            if ($_POST['sel']) {
                while (list($n, $v) = each($_POST['sel'])) {
                    $this->p->delete_photo($n);
                }
            }
        }

        if ($_POST['postsave']) {
            $this->handle_save();
        }

        if ($_POST['upload']) {
            $this->handle_upload();
        }

        if ($_POST['reupload']) {
            $this->handle_reupload();
        }

        if (!$this->no_post_redirect) {
            $emps->redirect_elink();
        }

    }

    public function handle_request($context_id)
    {
        global $emps, $ss, $start, $perpage, $smarty;

        $emps->uses_flash();

        $this->context_id = $context_id;

        $emps->loadvars();

        $fl = array();
        for ($i = 0; $i < 5; $i++) {
            $e['id'] = $i + 1;
            $fl[] = $e;
        }

        $smarty->assign("fl", $fl);

        if ($_GET['up']) {
            $emps->db->query("update " . TP . "e_uploads set ord=ord-15 where id=" . $_GET['up']);
        }
        if ($_GET['down']) {
            $emps->db->query("update " . TP . "e_uploads set ord=ord+15 where id=" . $_GET['down']);
        }

        if ($_POST) {
            $this->handle_post();
        } else {
            $start += 0;
            $perpage = 10;

            $r = $emps->db->query('select SQL_CALC_FOUND_ROWS * from ' . TP . 'e_uploads where context_id=' . $this->context_id . " order by ord asc limit $start,$perpage");
            $smarty->assign("PrintPages", $emps->print_pages_found());

            $lst = array();
            while ($ra = $emps->db->fetch_named($r)) {
                $ss = $ra['id'];
                $ra['ilink'] = $emps->elink();
                $emps->loadvars();
                $ra['klink'] = $emps->clink("kill=" . $ra['id']);
                $ra['time'] = $emps->form_time($ra['dt']);

                $lst[] = $ra;
            }
            $emps->loadvars();

            $smarty->assign("ilst", $lst);
        }
    }
}
