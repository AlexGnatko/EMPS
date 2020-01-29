<?php

if ($emps->auth->credentials("root")) {
    $emps->page_property("vuejs", 1);

    require_once $emps->common_module('vted/vted.class.php');
    require_once $emps->common_module('videos/videos.class.php');

    class EMPS_UsersEditor extends EMPS_VueTableEditor
    {
        public $ref_type = DT_USER;
        public $ref_sub = 1;

        public $track_props = P_USER;

        public $table_name = "e_users";

        public $credentials = "root";

        public $order = " order by id desc ";

        public $form_name = "db:_admin/vv/users,form";

        public $v;

        public $multilevel = false;

        public $pads = ['info', 'photos'];

        public $debug = false;

        public $props_by_ref = false;

        public function __construct()
        {
            parent::__construct();
        }

        public function explain_row($row){
            global $emps;

            $row = parent::explain_row($row);
            $row['password'] = "";

            $glst = [];
            $id = $row['id'];
            $rr = $emps->db->query("select * from " . TP . "e_users_groups where user_id = {$id} and context_id = {$emps->website_ctx}");
            while ($rra = $emps->db->fetch_named($rr)) {
                $glst[] = $rra['group_id'];
            }
            $row['grp'] = implode(", ", $glst);

            return $row;
        }

        public function pre_save($nr) {
            global $emps;

            if ($this->ref_id == 1) {
                if ($emps->auth->USER_ID != 1) {
                    // only root can change root password
                    unset($nr['password']);
                }
            }
            if ($nr['password'] == "") {
                unset($nr['password']);
            } else {
                $nr['password'] = md5($nr['password']);
            }

            return $nr;
        }

        public function post_save($nr) {
            global $emps;

            $grp = $nr['grp'];
            $x = explode(",", $grp);

            $id = $nr['id'];

            $emps->db->query("delete from " . TP . "e_users_groups where user_id = {$id} and context_id = {$emps->website_ctx}");
            foreach ($x as $v) {
                $v = trim($v);
                $emps->auth->add_to_group($id, $v);
            }

            $emps->auth->ensure_fullname(['id' => $id]);
        }
    }

    $ited = new EMPS_UsersEditor;

    require_once $emps->page_file_name("_admin/vv/users,common", "controller");

    $perpage = 25;

    $ited->handle_request();

} else {
    $emps->deny_access("AdminNeeded");
}
