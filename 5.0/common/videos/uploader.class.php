<?php
require_once $emps->common_module('videos/videos.class.php');

class EMPS_VideoUploader
{
    public $context_id = 0;
    public $v;

    public $can_save = true;

    public $table_name = "emps_videos";

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
        global $emps;

        $ord = 10;

        $params = [];
        $params['query'] = ['context_id' => $emps->db->oid($this->context_id)];
        $params['options'] = ['limit' => 1, 'sort' => ['ord' => -1]];
        $cursor = $emps->db->find($this->table_name, $params);

        foreach($cursor as $ra){
            $ord = $ra['ord'] + 10;
        }

        $SET = $this->v->parse_video_url($_POST['url']);

        if (count($SET) > 0) {
            $SET['context_id'] = $emps->db->oid($this->context_id);
            $SET['ord'] = $ord;
            $SET['user_id'] = $emps->auth->USER_ID;

            $emps->copy_values($SET, $_POST, "url,name,descr");

            $params = array();
            $params['doc'] = $SET;
            $emps->db->insert($this->table_name, $params);

            $item_id = $emps->db->last_id;
            $this->v->process_video($emps->db->oid($item_id));
        }else{
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
            $this->v->process_video($emps->db->oid($_GET['process']));
            $emps->redirect_elink();
            exit();
        }

        if ($_GET['delete']) {
            $id = $_GET['delete'];
            $this->v->delete_video($id);
            $emps->redirect_elink();
            exit();
        }

        if ($_GET['links']) {
            $emps->no_smarty = true;

            $id = $_GET['links'];

            $params = [];
            $params['query'] = ['_id' => $emps->db->oid($id), 'context_id' => $emps->db->oid($this->context_id)];
            $row = $emps->db->get_row($this->table_name, $params);

            $cctx = $emps->p->get_context(DT_VIDEO, 1, $row['_id']);

            $row['pic'] = $this->v->p->first_pic($cctx);
            $row['dur'] = $this->v->convert_duration($row['duration']);

            $row['vslink'] = "http://www.youtube.com/watch?v=" . $row['youtube_id'];

            $smarty->assign("row", $row);
            $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
            $smarty->display("db:videos/links");
            exit();
        }

        if ($_GET['descr']) {
            $emps->no_smarty = true;

            $id = $_GET['descr'];

            $params = [];
            $params['query'] = ['_id' => $emps->db->oid($id), 'context_id' => $emps->db->oid($this->context_id)];
            $row = $emps->db->get_row($this->table_name, $params);

            $smarty->assign("row", $row);

            $smarty->display("db:videos/descr");
            exit();
        }

        if ($this->can_save) {
            if ($_POST['post_descr']) {
                $id = $_POST['id'];

                $nr = [];
                $emps->copy_values($nr, $_POST, "name,description");
                $params = array();
                $params['query'] = array("_id" => $emps->db->oid($id), "context_id" => $emps->db->oid($this->context_id));
                $params['update'] = array('$set' => $nr);
                $emps->db->update_one($this->table_name, $params);

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


        $start = intval($start);
        $perpage = 10;
        $params = [];
        $params['query'] = ['context_id' => $emps->db->oid($this->context_id)];
        $params['options'] = array('limit' => $perpage, 'skip' => $start);
        //echo json_encode($params, JSON_PRETTY_PRINT);
        $cursor = $emps->db->find($this->table_name, $params);

        $this->total = $emps->db->found_rows;
        $this->pages = $emps->count_pages($this->total);

        $smarty->assign("pages", $this->pages);

        $lst = array();

        foreach($cursor as $ra){
            $ra['dur'] = $this->v->convert_duration($ra['duration']);
            $ctx = $emps->p->get_context(DT_VIDEO, 1, $ra['_id']);
            $ra['pic'] = $this->v->p->first_pic($ctx);
            //dump($ra['pic']);
            $ra['time'] = $emps->form_time($ra['cdt']);
            $ra['vslink'] = $ra['url'];
            $lst[] = $ra;
        }

        $smarty->assign("lst", $lst);
    }
}