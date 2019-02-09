<?php

if ($emps->auth->credentials('admin')):

    $emps->page_property("vuejs", 1);

    $context_id = $emps->website_ctx;

    require_once $emps->common_module('vted/vted.class.php');
    require_once $emps->common_module('videos/videos.class.php');


    class EMPS_MenuEditor extends EMPS_VueTableEditor
    {
        public $ref_type = DT_CONTENT;
        public $ref_sub = CURRENT_LANG;

        public $track_props = P_CONTENT;

        public $table_name = "e_content";

        public $credentials = "admin";

        public $form_name = "db:_admin/vv/content,form";

        public $order = " order by uri asc ";

        public $v;

        public $multilevel = false;

        public $pads = ['info', 'html', 'props', 'photos', 'files', 'videos'];
        public $pad_names = "db:_admin/vv/content,pad_names";

        public $debug = true;

        public function __construct()
        {
            parent::__construct();
            $this->v = new EMPS_Videos;
        }

        public function explain_row($row){
            $row = parent::explain_row($row);
            $row['name'] = $row['uri'];
            return $row;
        }
    }

    $ited = new EMPS_MenuEditor;

    require_once $emps->page_file_name("_admin/vv/content,common", "controller");

    $perpage = 25;

    $ited->handle_request();

else:

    $emps->deny_access("UserNeeded");

endif;