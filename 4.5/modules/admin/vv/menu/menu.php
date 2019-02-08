<?php

if ($emps->auth->credentials('admin')):

    $emps->page_property("vuejs", 1);

    $context_id = $emps->website_ctx;

    require_once $emps->common_module('vted/vted.class.php');
    require_once $emps->common_module('videos/videos.class.php');


    class EMPS_MenuEditor extends EMPS_VueTableEditor
    {
        public $ref_type = DT_MENU;
        public $ref_sub = CURRENT_LANG;

        public $track_props = P_MENU;

        public $table_name = "e_menu";

        public $credentials = "admin";

        public $form_name = "db:_admin/vv/menu,form";

        public $order = " order by ord asc ";

        public $v;

        public $multilevel = true;
        public $has_ord = true;

        public $pads = ['info', 'props', 'photos'];
        public $pad_names = "db:_admin/vv/menu,pad_names";

        public $debug = true;

        public function __construct()
        {
            parent::__construct();
            $this->v = new EMPS_Videos;
        }

        public function pre_create($nr)
        {
            global $sd;
            $parent_id = intval($sd);
            if ($parent_id) {
                $parent = $this->load_row($parent_id);
                $nr['grp'] = $parent['grp'];
            }
            return $nr;
        }
    }

    $ited = new EMPS_MenuEditor;

    require_once $emps->page_file_name("_admin/vv/menu,common", "controller");

    $perpage = 25;

    $ited->handle_request();

else:

    $emps->deny_access("UserNeeded");

endif;
