<?php
if ($emps->auth->credentials("admin")):
    include($emps->common_module('ted/ted.class.php'));


    class EMPS_Shadows extends EMPS_TableEditor
    {
        public $table_name = 'e_shadows';

        public function post_save($id)
        {
            global $emps;
            $context_id = $emps->p->get_context(DT_SHADOW, 1, $id);

            $emps->p->save_properties($_REQUEST, $context_id, P_SHADOW);

            parent::post_save($id);
        }

        public function handle_kill($id)
        {
        }

        public function handle_row($ra)
        {
            global $emps;

            if ($ra['user_id']) {
                $user = $emps->auth->load_user($ra['user_id']);
                $ra['user'] = $user;
            }
            $ra['time'] = $emps->form_time($ra['cdt']);

            $context_id = $emps->p->get_context(DT_SHADOW, 1, $ra['id']);
            $ra = $emps->p->read_properties($ra, $context_id);
            return $ra;
        }

        public function handle_input($ra)
        {
            $ra = parent::handle_input($ra);
            return $ra;
        }

        public function handle_post()
        {

            parent::handle_post();
        }
    }

    $ted = new EMPS_Shadows;

    if ($_POST['sadd']) {
        $_REQUEST['user_id'] = $emps->auth->USER_ID;
        $_REQUEST['website_ctx'] = $emps->website_ctx;
    }

    $where = " and website_ctx = " . $emps->website_ctx;

    $ted->addfilt = $where;

    $ted->handle_request();
else:
    $emps->deny_access("AdminNeeded");
endif;
