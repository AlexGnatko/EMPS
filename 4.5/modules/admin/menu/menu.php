<?php

if ($emps->get_setting("admin_tools")){
    require_once $emps->page_file_name("_admin/" . $emps->get_setting("admin_tools") . "/menu", "controller");
}else {

    require_once $emps->common_module('ited/ited.class.php');
    require_once $emps->common_module('videos/videos.class.php');

    class EMPS_MenuEditor extends EMPS_ImprovedTableEditor
    {
        public $ref_type = DT_MENU;
        public $ref_sub = CURRENT_LANG;

        public $track_props = P_MENU;

        public $table_name = "e_menu";

        public $credentials = "admin";

        public $form_name = "db:_admin/menu,form";

        public $order = " order by ord asc ";

        public $v;

        public $multilevel = true;

        public $pads = array(
            'info' => 'Общие сведения',
            'props' => 'Свойства',
            'photos' => 'Изображения'
        );


        public function __construct()
        {
            parent::__construct();
            $this->v = new EMPS_Videos;
        }

        public function handle_row($ra)
        {
            global $emps, $ss, $key;

            return parent::handle_row($ra);
        }
    }

    $ited = new EMPS_MenuEditor;

    $ited->ref_id = $key;
    $ited->website_ctx = $emps->website_ctx;

    $perpage = 50;

    $ited->where = " where context_id = " . $emps->website_ctx;
    $ited->pads = $emps->pad_menu("db:_admin/menu,padmenu");

    function import_menu(&$lst, $code, $parent)
    {
        global $emps, $ited;

        foreach ($lst as $v) {
            $v['name'] = $v['dname'];
            $v['uri'] = $v['link'];
            $v['enabled'] = 1;
            unset($v['parent']);
            unset($v['id']);

            $v['parent'] = $parent;

            $uri = $emps->db->sql_escape($v['link']);
            $row = $emps->db->get_row("e_menu", "uri = '" . $uri . "' and context_id = " . $emps->website_ctx);
            $update = array();
            $update['SET'] = $v;
            if ($_REQUEST['grp']) {
                $update['SET']['grp'] = $_REQUEST['grp'];
            }
            $update['SET']['context_id'] = $emps->website_ctx;
            if ($row) {
                $emps->db->sql_update_row("e_menu", $update, "id = " . $row['id']);
                $id = $row['id'];
            } else {
                $emps->db->sql_insert_row("e_menu", $update);
                $id = $emps->db->last_insert();
            }
            $context_id = $emps->p->get_context($ited->ref_type, 1, $id);
            $emps->p->save_properties($v, $context_id, $ited->track_props);
            if (count($v['sub']) > 0) {
                $sls = $v['sub'];
                import_menu($sls, $code, $id);
            }
        }

    }

    if ($_POST['post_import']) {
        $lst = json_decode($_POST['json'], true);
        import_menu($lst, false, 0);
        $emps->redirect_elink();
        exit();
    }

    if ($_GET['export_menu']) {
        $parent = intval($sd);
        if ($sk == '00') {
            $sk = '';
        }
        $code = $sk;
        if ($code) {
            $menu = $emps->section_menu($code, $parent);
            $data = json_encode($menu);
            $smarty->assign("json_data", $data);
        }
    }

    $emps->loadvars();

    $cur_grp = $sk;

    $cur_parent = $sd;

    $r = $emps->db->query('select grp from ' . TP . "e_menu where context_id=" . $emps->website_ctx . " group by grp order by grp desc");
    $grp = array();
    $emps->clearvars();

    $pp = "admin-menu";

    while ($ra = $emps->db->fetch_named($r)) {
        $a = array();
        $a['name'] = $ra['grp'];
        $sk = $ra['grp'];
        if (!$sk) {
            $sk = '00';
        }
        $sd = '';
        $a['link'] = $emps->elink();
        if (($cur_grp == $ra['grp']) && $cur_grp != '') {
            $a['sel'] = true;
        }
        if (($ra['grp'] == "") && $cur_grp == '00') {
            $a['sel'] = true;
        }
        if (!$a['name']) {
            $a['name'] = "_nocode";
        }

        $grp[] = $a;
    }

    $a = array();
    $a['name'] = "_all";
    $emps->loadvars();
    $sk = '';
    $sd = '';
    $a['link'] = $emps->elink();
    if ($cur_grp == '') {
        $a['sel'] = true;
    }
    $grp[] = $a;

    $smarty->assign("grp", $grp);
    $emps->loadvars();


    if ($sk) {
        if ($sk == '00') {
            $sk = '';
        }
        $ited->where .= " and grp = '" . $emps->db->sql_escape($sk) . "' ";
    }

    if ($sd) {
        $ited->where .= " and parent = " . $sd;

        $parent = $ited->get_row($sd);

        $smarty->assign("parent", $parent);
    } else {
        $ited->where .= " and parent = 0 ";
    }

    $sd = "";
    $smarty->assign("totop", $emps->elink());
    $emps->loadvars();

    $r = $emps->db->query("select max(ord)+100 from " . TP . "e_menu " . $ited->where);
    $ra = $emps->db->fetch_row($r);
    $next_ord = $ra[0];
    if (!$next_ord) {
        $next_ord = 100;
    }
    $smarty->assign("next_ord", $next_ord);

    if ($_POST) {
        if (!$_POST['ord']) {
            $_REQUEST['ord'] = $next_ord;
        }
    }


    $emps->loadvars();
    if ($sk == '00') {
        $sk = '';
    }

    if ($sk) {
        $_REQUEST['grp'] = $sk;
    }

    if ($sd) {
        if ($_POST['action_add']) {
            $_REQUEST['parent'] = $sd;
        }
        $mi = $emps->db->get_row("e_menu", "id = " . intval($sd));
        if ($mi) {
            $_REQUEST['grp'] = $mi['grp'];
        }
    }

    $ited->add_pad_template("admin/menu/pads,%s");

    $ited->handle_request();

}