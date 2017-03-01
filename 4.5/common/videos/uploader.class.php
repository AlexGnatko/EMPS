<?php
require_once $emps->common_module('videos/videos.class.php');

class EMPS_VideoUploader
{
    public $context_id = 0;
    public $v;

    public $can_save = true;

    public function __construct()
    {
        $this->v = new EMPS_Videos;
    }

    public function handle_save()
    {
        global $emps, $SET;
        reset($_POST['name']);
        $SET = array();
        while (list($n, $v) = each($_POST['name'])) {
            $SET['name'] = $v;
            $SET['description'] = $_POST['descr'][$n];
            $SET['ord'] = $_POST['ord'][$n];
            $emps->db->sql_update("e_videos", "id=$n");
        }
    }

    public function handle_new()
    {
        global $emps, $SET;

        $q = "select max(ord) from " . TP . "e_videos where context_id=" . $this->context_id;
        $r = $emps->db->query($q);
        $ra = $emps->db->fetch_row($r);
        $ord = $ra[0];

        $SET = $this->v->parse_video_url($_POST['url']);
        $_REQUEST['context_id'] = $this->context_id;
        $_REQUEST['ord'] = $ord + 10;
        $_REQUEST['user_id'] = $emps->auth->USER_ID;

        if (count($SET) > 0) {
            $emps->db->sql_insert("e_videos");

            $item_id = $emps->db->last_insert();

            $this->v->process_video($item_id);
        } else {
            $emps->flash("error", 1);
        }
    }

    public function handle_post()
    {
        if ($_POST['postsave']) {
            $this->handle_save();
        }

        if ($_POST['post_kill'] && !$_POST['postsave']) {
            while (list($n, $v) = each($_POST['sel'])) {
                $this->v->delete_video($n);
            }
        }

        if ($_POST['post_new']) {
            $this->handle_new();
        }
    }

    public function handle_request($context_id)
    {
        global $emps, $ss, $start, $perpage, $smarty;

        $emps->uses_flash();

        $this->context_id = $context_id;

        $emps->loadvars();

        if ($_GET['process']) {
            $this->v->process_video(intval($_GET['process']));
            $emps->redirect_elink();
            exit();
        }

        if ($_GET['delete']) {
            $id = intval($_GET['delete']);
            $this->v->delete_video($id);
            $emps->redirect_elink();
            exit();
        }

        if ($_GET['links']) {
            $emps->no_smarty = true;

            $id = intval($_GET['links']);
            $row = $emps->db->get_row("e_videos", "id=$id");

            $cctx = $emps->p->get_context(DT_VIDEO, 1, $row['id']);

            $row['pic'] = $this->v->p->first_pic($cctx);
            $row['dur'] = $this->v->convert_duration($row['duration']);

            $row = $emps->p->read_properties($row, $cctx);

            $row['vslink'] = "http://www.youtube.com/watch?v=" . $row['youtube_id'];

            $smarty->assign("row", $row);
            $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
            $smarty->display("db:videos/links");
            exit();
        }

        if ($_GET['descr']) {
            $emps->no_smarty = true;

            $id = intval($_GET['descr']);
            $row = $emps->db->get_row("e_videos", "id=$id");

            $smarty->assign("row", $row);

            $smarty->display("db:videos/descr");
            exit();
        }

        if ($this->can_save) {
            if ($_POST['post_descr']) {
                $id = intval($_POST['id']);
                unset($_RESET['id']);
                $emps->db->sql_update("e_videos", "id=" . $id);
                $emps->redirect_elink();
                exit();
            }

            if (isset($_REQUEST['reorder_videos'])) {
                $files = $_REQUEST['p'];
                $ord = 10;
                foreach ($files as $file_id) {
                    $emps->db->query(sprintf("update " . TP . "e_videos set ord=%d where id=%d ",
                        $ord,
                        intval($file_id)));
                    $ord += 10;
                }
                exit;
            }

            if ($_POST) {
                $this->handle_post();
                $emps->redirect_elink();
            }
        }


        $start += 0;
        $perpage = 10;
        $r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from " . TP . "e_videos where context_id=" . $this->context_id . " order by ord asc, id asc limit $start,$perpage");
        $smarty->assign("PrintPages", $emps->print_pages_found());
        $lst = array();
        while ($ra = $emps->db->fetch_named($r)) {
            $cctx = $emps->p->get_context(DT_VIDEO, 1, $ra['id']);

            $ra['pic'] = $this->v->p->first_pic($cctx);
            $ra['dur'] = $this->v->convert_duration($ra['duration']);
            $ra['time'] = $emps->form_time($ra['cdt']);
            $ra = $emps->p->read_properties($ra, $cctx);

            $lst[] = $ra;
        }

        $smarty->assign("lst", $lst);
    }
}
