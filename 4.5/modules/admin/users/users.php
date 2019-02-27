<?php
if ($emps->get_setting("admin_tools")) {
    require_once $emps->page_file_name("_admin/" . $emps->get_setting("admin_tools") . "/users", "controller");
} else {

    if ($emps->auth->credentials("root")):
        include($emps->common_module('ted/ted.class.php'));

        class EMPS_Users extends EMPS_TableEditor
        {
            public $table_name = 'e_users';

            public function handle_row($ra)
            {
                global $emps;

                $ra = parent::handle_row($ra);

                $context_id = $emps->p->get_context(DT_USER, 1, $ra['id']);
                $ra = $emps->p->read_properties($ra, $context_id);

                return $ra;
            }

            public function post_save($id)
            {
                global $emps;
                parent::post_save($id);

                $context_id = $emps->p->get_context(DT_USER, 1, $id);
                $emps->p->save_properties($_REQUEST, $context_id, P_USER);

                $grp = $_REQUEST['grp'];
                $x = explode(",", $grp);

                $emps->db->query("delete from " . TP . "e_users_groups where user_id=$id and context_id=" . $emps->website_ctx);
                while (list($n, $v) = each($x)) {
                    $v = trim($v);
                    $emps->auth->add_to_group($id, $v);
                }

                $emps->auth->ensure_fullname(['id' => $id]);
            }

            public function handle_kill($id)
            {
                global $emps;
                $id += 0;
                $emps->db->query("delete from " . TP . "e_users_groups where user_id=$id");
                $emps->p->delete_context($emps->p->get_context(DT_USER, 1, $id));
            }

            public function handle_input($ra)
            {
                global $emps;
                $ra = parent::handle_input($ra);
                $grp = "";
                $id = $ra['id'];
                $rr = $emps->db->query("select * from " . TP . "e_users_groups where user_id=$id and context_id = " . $emps->website_ctx);
                while ($rra = $emps->db->fetch_named($rr)) {
                    if ($grp) $grp .= ",";
                    $grp .= $rra['group_id'];
                }
                $ra['grp'] = $grp;
                return $ra;
            }

            public function handle_post()
            {
                $_REQUEST['status'] = 1;

                if ($_REQUEST['password']) {
                    $_REQUEST['password'] = md5($_REQUEST['password']);
                }
                parent::handle_post();
            }
        }

        $ted = new EMPS_Users;

        $ted->handle_request();
    else:
        $emps->deny_access("AdminNeeded");
    endif;

}