<?php
if ($emps->auth->credentials("admin")):
    $emps->page_property("ited", 1);

    include($emps->common_module('ted/ted.class.php'));

    class EMPS_MsgCache extends EMPS_TableEditor
    {
        public $table_name = 'e_msgcache';
        public $tord = " order by dt desc, id desc ";
    }

    $ted = new EMPS_MsgCache;

    $ted->handle_request();
else:
    $emps->deny_access("AdminNeeded");
endif;

