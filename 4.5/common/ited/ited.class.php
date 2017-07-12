<?php

class EMPS_ImprovedTableEditor
{
    public $ref_type = 0;
    public $ref_sub = 0;
    public $ref_id = 0;

    public $context_id = 0;

    public $website_ctx = 1;

    public $track_props = '';
    public $table_name = "e_content";
    public $credentials = "admin";

    public $action_open_ss = "info";

    public $form_name = "db:ited/generic";

    public $pad_templates = array();
    public $ajax_templates = array();

    public $what = "*";
    public $where, $group, $having, $order, $join;

    public $row, $old_row;

    public $tree = false;

    public $immediate_add = false;

    public $multilevel = false;
    public $preview_row = true;

    public $pads = array(
        'info' => 'General'
    );

    public function __construct()
    {
        $this->pad_templates[] = "ited/pads,%s";
    }

    public function current_pad($type)
    {
        global $emps, $ss;
        $emps->loadvars();

        reset($this->pad_templates);
        while (list($n, $v) = each($this->pad_templates)) {
            $uv = sprintf($v, $ss);
            if ($type == 'view') {
                $fn = $emps->page_file_name('_' . $uv, 'view');
            } else {
                $fn = $emps->page_file_name('_' . $uv . '.php', 'inc');
            }

            if (!file_exists($fn)) {
                $v = str_replace(',', '/', $v);
                $uv = sprintf($v, $ss);
                if ($type == 'view') {
                    $fn = $emps->common_module($uv . '.' . $emps->lang . '.htm');
                    if (!file_exists($fn)) {
                        $fn = $emps->common_module($uv . '.nn.htm');
                    }
                } else {
                    $fn = $emps->common_module($uv . '.php');
                }
                if (file_exists($fn)) {
                    return $fn;
                }
            } else {
                return $fn;
            }
        }
    }

    public function add_pad_template($txt)
    {
        array_unshift($this->pad_templates, $txt);
    }

    public function add_ajax_template($txt)
    {
        array_unshift($this->ajax_templates, $txt);
    }


    public function can_save()
    {
        return true;
    }

    public function can_delete()
    {
        return true;
    }

    public function can_view_pad()
    {
        return true;
    }

    public function after_insert($id)
    {
        global $emps;

        $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);

        $emps->p->handle_keywords($context_id, $_REQUEST['keywords_idx']);

        $emps->p->save_properties($_REQUEST, $context_id, $this->track_props);
    }

    public function after_save($id)
    {
        global $emps;
        $emps->p->handle_keywords($this->context_id, $_REQUEST['keywords_idx']);
    }

    public function pre_kill($id)
    {
    }

    public function after_kill($id)
    {
        global $emps;

        $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);

        $emps->p->delete_context($context_id);

        if ($this->multilevel) {
            $r = $emps->db->query("select id from " . TP . $this->table_name . " where parent = " . $id);
            while ($ra = $emps->db->fetch_row($r)) {
                $emps->db->query('delete from ' . TP . $this->table_name . ' where id=' . $ra[0]);
                $this->after_kill($ra[0]);
            }
        }
    }

    public function handle_redirect()
    {
        global $emps;
        $emps->redirect_elink();
    }

    public function get_row($id)
    {
        global $emps;

        $row = $emps->db->get_row($this->table_name, "id = " . $id);
        if ($row) {
            return $this->handle_row($row);
        }
        return false;
    }

    public function count_children($id)
    {
        global $emps;

        $r = $emps->db->query("select count(*) from " . TP . $this->table_name . " where parent = $id");
        $ra = $emps->db->fetch_row($r);

        return $ra[0];
    }

    public function handle_row($row)
    {
        global $emps, $ss, $key, $sd;
        $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $row['id']);
        $row = $emps->p->read_properties($row, $context_id);
        $row['keywords_idx'] = $emps->p->list_keywords($this->context_id);
        $kw = array();
        while (list($n, $v) = each($row['keywords_idx'])) {
            $kw[$v] = 1;
        }
        $row['kw'] = $kw;

        $emps->loadvars();
        $key = $row['id'];
        $ss = $this->action_open_ss;
        $row['nlink'] = $emps->elink();
        $ss = "html";
        $row['ilink'] = $emps->elink();
        $ss = "photos";
        $row['hlink'] = $emps->elink();
        $ss = "props";
        $row['plink'] = $emps->elink();
        $emps->loadvars();

        $ss = "";
        $key = $row['id'];
        $row['elink'] = $emps->clink("part=edit");
        $row['klink'] = $emps->clink("part=kill");
        $emps->loadvars();

        $sd = $row['id'];
        $ss = "";
        $key = "";
        $row['clink'] = $emps->elink();
        $emps->loadvars();

        if ($row['parent'] != 0) {
            $row['parent_data'] = $this->get_row($row['parent']);
        }

        if (isset($row['parent'])) {
            $row['children'] = $this->count_children($row['id']);
        }

        return $row;
    }

    public function handle_display($row)
    {
        $row = $this->handle_row($row);
        return $row;
    }

    public function handle_view_row()
    {
        global $smarty;
        $this->row = $this->handle_display($this->row);

        $smarty->assign('row', $this->row);
    }

    public function handle_orig()
    {
        global $emps, $smarty;

        require_once $emps->common_module('diff/diff.class.php');

        $diff = new EMPS_Diff;

        $orig = $_POST['orig'];

        $row = $this->row;

        $rv = false;
        foreach ($orig as $var => $value) {
            if ($this->row[$var] != $value) {
                $row[$var] = $value;
                $row['new'][$var] = $_POST[$var];
                $row['other'][$var] = $this->row[$var];

                $result = $diff->diff_result($this->row[$var], $_POST[$var]);
                $row['new_cur'][$var] = $result;
                $result = $diff->diff_result($value, $this->row[$var]);
                $row['cur_old'][$var] = $result;
//				dump($result);exit();
                $rv = true;
            }
        }

        if ($rv) {
            $smarty->assign("Differences", 1);
//			exit();
        }

        $smarty->assign("row", $row);

        return $rv;
    }

    public function handle_post()
    {
        global $emps, $smarty;

        $smarty->assign("PostEnabled", 1);

        if ($_POST['post_save']) {
            if ($this->can_save()) {
                if ($this->preview_row) {
                    $this->handle_view_row();
                    $this->old_row = $this->row;
                }

                $_REQUEST['name'] = trim($_REQUEST['name']);
                if ($_POST['ptime']) {
                    $_POST['pdt'] = $emps->parse_time($_POST['ptime']);
                    $_REQUEST['pdt'] = $_POST['pdt'];
                }
                if ($_POST['ctime']) {
                    $_POST['dt'] = $emps->parse_time($_POST['ctime']);
                    $_REQUEST['dt'] = $_POST['dt'];
                }

                $rv = false;
                if (isset($_POST['orig']) && $GLOBALS['emps_html_orig']) {
                    $rv = $this->handle_orig();
                }

                if (!$rv) {
                    $this->row = $emps->db->get_row($this->table_name, 'id=' . $this->ref_id);
                    $this->row = $this->handle_display($this->row);
                    $emps->db->sql_update($this->table_name, 'id=' . $this->ref_id);
                    $nr = $_POST;
                    $nr = array_merge($nr, $_REQUEST);
                    $emps->p->save_properties($nr, $this->context_id, $this->track_props);

                    $this->after_save($this->ref_id);

                    $this->handle_redirect();
                }
            }
        }
    }

    public function prepare_menu()
    {
        global $emps, $smarty;
        $menu = $emps->prepare_pad_menu($this->pads, 'ss');
        $smarty->assign('smenu', $menu);
    }

    public function handle_detail_mode()
    {
        global $emps, $smarty, $ss, $key;

        if ($this->ref_id) {
            $ss = '';
            $smarty->assign('def_edit', $emps->clink('part=edit'));
            $smarty->assign('def_kill', $emps->clink('part=kill'));
        }
        $emps->loadvars();

        if ($_POST['action_kill']) {
            if ($this->can_delete()) {
                $this->pre_kill($this->ref_id);
                $emps->db->query('delete from ' . TP . $this->table_name . ' where id=' . $this->ref_id);
                $this->after_kill($this->ref_id);

                $key = "";
                $ss = "";
                $emps->redirect_elink();
                exit();
            }
        }

        $ss = '';
        $key = '';
        $smarty->assign('BackITEDLink', $emps->elink());
        $emps->loadvars();

        $this->row = $emps->db->get_row($this->table_name, 'id=' . $this->ref_id);
        if (!$this->row) {
            $key = "";
            $ss = "";
            $emps->redirect_elink();
            exit();
        }
        if (!$this->keep_data) {
            $this->row['data'] = unserialize($this->row['data']);
        }
        $smarty->assign('row', $this->row);

        $smarty->assign("CanSave", $this->can_save());

        $smarty->assign('Zoom', 1);

        $this->prepare_menu();


        $fn = $this->current_pad('controller');

        if (file_exists($fn) && $this->can_view_pad()) {
            $smarty->assign('subpage', $this->current_pad('view'));
            require_once $fn;
        }

        $smarty->assign('context_id', $this->context_id);

    }

    public function handle_list_mode()
    {
        global $smarty, $emps, $key, $ss, $start, $perpage, $total;

        $smarty->assign("lang", $emps->lang);

        $emps->loadvars();
        $this->ref_id = $key + 0;

        if ($_GET['part']) {
            $emps->no_smarty = true;
            $emps->text_headers();
            $smarty->assign("itedpart", $_GET['part']);
            if ($_GET['part'] != "add") {
                $row = $emps->db->get_row($this->table_name, 'id=' . $this->ref_id);
                $row = $this->handle_display($row);
                $smarty->assign("row", $row);
            }
        }
        if ($_GET['part'] == "edit") {
            $smarty->assign("Mode", "edit");
            $smarty->display("db:ited/iactpart");
        } elseif ($_GET['part'] == "kill") {
            $smarty->assign("Mode", "kill");
            $smarty->display("db:ited/iactpart");
        } elseif ($_GET['part'] == "add") {
            $smarty->assign("Mode", "add");
            if ($_REQUEST['target']) {
                $smarty->assign("add_target", $_REQUEST['target']);
            }
            $smarty->assign("autolink", $_REQUEST['autolink']);
            $smarty->assign("return_to", $_REQUEST['return_to']);
            $smarty->display("db:ited/iactpart");
        } else {
            if ($_POST['action_open']) {
                $ss = $this->action_open_ss;
                $key = intval($_POST['id']);
                $emps->redirect_elink();
                exit();
            }
            if ($_POST['action_add']) {
                $_REQUEST['context_id'] = $this->website_ctx;
                $emps->db->sql_insert($this->table_name);
                $this->after_insert($emps->db->last_insert());
                $emps->redirect_elink();
                exit();
            }
            if ($_POST['action_save']) {
                if ($this->can_save()) {
                    $emps->db->sql_update($this->table_name, 'id=' . $this->ref_id);
                    $this->after_save($this->ref_id);
                }
                $emps->redirect_elink();
                exit();
            }
            if ($_POST['action_kill']) {
                if ($this->can_delete()) {
                    $this->pre_kill($this->ref_id);
                    $emps->db->query('delete from ' . TP . $this->table_name . ' where id=' . $this->ref_id);
                    $this->after_kill($this->ref_id);

                    $key = "";
                    $ss = "";
                    $emps->redirect_elink();
                    exit();
                }
            }

            if ($this->ref_id) {
                $smarty->assign("def_edit", $emps->clink("part=edit"));
                $smarty->assign("def_kill", $emps->clink("part=kill"));
            }

            if (!$start) $start = 0;
            if (!$perpage) {
                $perpage = 25;
            }
            if (!$this->what) $this->what = "*";
            $q = 'select SQL_CALC_FOUND_ROWS ' . $this->what . ' from ' . TP . $this->table_name . ' ' . $this->join . ' ' . $this->where . ' ' .
                $this->group . ' ' . $this->having . ' ' . $this->order . ' limit ' . $start . ',' . $perpage;
//				echo $q;
            $r = $emps->db->query($q);
            $this->last_sql_query = $q;
//			echo $emps->db->sql_error();
            $lst = array();
            $smarty->assign("pages", $emps->count_pages($emps->db->found_rows()));
            while ($ra = $emps->db->fetch_named($r)) {
                $ss = "";
                $key = $ra['id'];

                $ra['ctime'] = $emps->form_time($ra['dt']);

                $ra = $this->handle_row($ra);
                $lst[] = $ra;
            }

            $emps->loadvars();

            $smarty->assign("lst", $lst);
        }
    }

    public function handle_request()
    {
        // the main entry point for request processing
        global $emps, $smarty, $ss, $key;

        $emps->page_property('ited', true);
        $this->context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $this->ref_id);

        if ($key == 'ajax') {
            return $this->handle_ajax();
        }

        $smarty->assign("form", $this->form_name);

        if ($this->immediate_add) {
            $emps->loadvars();
            $smarty->assign("fastadd", 1);
            $key = "";
            $ss = "";
            $smarty->assign("def_addfast", $emps->clink("part=add"));
            $emps->loadvars();
        }

        if ($emps->auth->credentials($this->credentials) || $this->override_credentials) {
            if ($ss && !isset($_REQUEST['action_kill'])) {
                $this->handle_detail_mode();
            } else {
                $this->handle_list_mode();
            }
        } else {
            $emps->deny_access("AdminNeeded");
        }
    }


    public function ajax_template($name, $type)
    {
        global $smarty, $key, $start, $ss, $sd, $sk, $emps;
        $emps->loadvars();

        reset($this->ajax_templates);
        while (list($n, $v) = each($this->ajax_templates)) {
            $vn = sprintf($v, $name);
            $vn = str_replace("-", "_", $vn);

            if ($type == 'view') {
                $fn = $emps->page_file_name('_' . $vn, 'view');
            } else {
                $fn = $emps->page_file_name('_' . $vn . '.php', 'inc');
            }


            if (!($fn)) {
                $v = str_replace(',', '/', $vn);
                if ($type == 'view') {
                    $fn = $emps->common_module($vn);
                } else {
                    $fn = $emps->common_module($vn . '.php');
                }
                if (file_exists($fn)) {
                    return $fn;
                }
            } else {
                return $fn;
            }
        }

        return false;
    }

    public function handle_ajax()
    {
        global $smarty, $key, $start, $ss, $sd, $sk, $emps;

        $emps->no_smarty = true;
        $smarty->assign("df_format", EMPS_DT_FORMAT);

        if ($emps->auth->credentials($this->credentials)) {
            $emps->loadvars();

            $file = $this->ajax_template($start, 'controller');

            if ($file) {
                require_once $file;
            }
        } else {
            echo "ACCESS DENIED. Please log in again.";
        }
    }

    public function handle_post_times($list)
    {
        global $emps;
        $x = explode(',', $list);

        while (list($n, $v) = each($x)) {
            if ($_REQUEST[$v . 'time']) {
                $_REQUEST[$v . 'dt'] = $emps->parse_time($_REQUEST[$v . 'time']);
            }
        }
    }

    public function handle_view_times($list, $row)
    {
        global $emps;
        $x = explode(',', $list);
        while (list($n, $v) = each($x)) {
            if ($row[$v . 'dt']) {
                $row[$v . 'time'] = $emps->form_time($row[$v . 'dt']);
            }
        }
        return $row;
    }

    public function delete_all()
    {
        global $emps;

        $q = 'select ' . $this->what . ' from ' . TP . $this->table_name . ' ' . $this->join . ' ' . $this->where . ' ' .
            $this->group . ' ' . $this->having;
        $r = $emps->db->query($q);

        while ($ra = $emps->db->fetch_named($r)) {
            $emps->db->query('delete from ' . TP . $this->table_name . ' where id=' . $ra['id']);
            $this->after_kill($ra['id']);
        }
    }

}
