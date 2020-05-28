<?php
require_once $emps->common_module('videos/videos.class.php');

class EMPS_VideoProcessor
{
    public $context_id = 0;
    public $v;

    public $can_save = true;

    public function __construct()
    {
        $this->v = new EMPS_Videos;
    }

    public function list_videos() {
        global $emps;

        $r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from ".TP."e_videos where 
        context_id = {$this->context_id} order by ord asc");

        $lst = [];

        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->explain_video($ra);

            $lst[] = $ra;
        }

        return $lst;
    }

    public function handle_list() {
        global $emps;

        $response = [];
        $response['code'] = "OK";
        $response['videos'] = $this->list_videos();
        $response['context_id'] = $this->context_id;
        $response['context'] = $emps->p->load_context($this->context_id);
        $emps->json_response($response);
    }

    public function handle_new()
    {
        global $emps;

        $q = "select max(ord) from " . TP . "e_videos where context_id = " . $this->context_id;
        $r = $emps->db->query($q);
        $ra = $emps->db->fetch_row($r);
        $ord = $ra[0];

        $nr = $this->v->parse_video_url($_POST['url']);
        $nr['context_id'] = $this->context_id;
        $nr['ord'] = $ord + 10;
        $nr['user_id'] = $emps->auth->USER_ID;

        $emps->db->sql_insert_row("e_videos", ['SET' => $nr]);

        $item_id = $emps->db->last_insert();

        $this->v->process_video($item_id);
    }

    public function explain_video($ra) {
        global $emps;

        $cctx = $emps->p->get_context(DT_VIDEO, 1, $ra['id']);

        $ra['pic'] = $this->v->p->first_pic($cctx);
        $ra['dur'] = $this->v->convert_duration($ra['duration']);
        $ra['time'] = $emps->form_time($ra['cdt']);
        $ra = $emps->p->read_properties($ra, $cctx);

        if ($ra['youtube_id']) {
            $ra['url'] = "https://youtube.com/watch?v=" . $ra['youtube_id'];
        }

        return $ra;
    }

    public function handle_request()
    {
        global $emps;

        if ($this->can_save) {
            if ($_POST['post_add_video']) {
                $this->handle_new();
                $this->handle_list();
            }

            if ($_REQUEST['process']) {
                $this->v->process_video(intval($_REQUEST['process']));
                $this->handle_list();
            }

            if ($_POST['post_save_description']) {
                $id = intval($_POST['video_id']);
                $nr = ['descr' => $_POST['descr'], 'name' => $_POST['name']];
                $emps->db->sql_update_row("e_videos", ['SET' => $nr], "id = {$id}");
                $this->handle_list();
            }

            error_log("process");
            if ($_GET['delete_video']) {
                $id = $_GET['delete_video'];
                $r = $emps->db->query("select * from ".TP."e_videos 
                            where context_id = {$this->context_id} and id in ({$id})");
                while($ra = $emps->db->fetch_named($r)){
                    $id = intval($ra['id']);
                    $this->v->delete_video($id);
                }
                $this->handle_list();
            }

            if ($_GET['reorder_videos']) {
                $x = explode(",", $_GET['reorder_videos']);
                $ord = 100;
                foreach($x as $id) {
                    $id = intval($id);
                    $nr = [];
                    $nr['ord'] = $ord;
                    $emps->db->sql_update_row("e_videos", ['SET' => $nr], "id = {$id} and context_id = {$this->context_id} ");
//                    error_log("Updated: {$id} to ord = {$ord}");
                    $ord += 100;
                }
                $this->handle_list();
            }

            if ($_GET['list_videos']) {
                $this->handle_list();
            }
        }else{
            $response = [];
            $response['code'] = "Error";
            $response['message'] = "You are not allowed to add or edit videos here.";

            $emps->json_response($response); exit;

        }
    }
}
