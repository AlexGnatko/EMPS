<?php

$emps->p->no_full = true;
$emps->p->no_idx = true;

$emps->db->always_use_wt = true;

$emps->no_autopage = true;

$emps->page_property("toastr", 1);
$emps->page_property("tinymce", 1);
$emps->page_property("tinymce_vue", 1);
$emps->page_property("sortable_vue", 1);

class EMPS_VueTableEditor
{
    public $ref_type = 0;
    public $ref_sub = 0;
    public $ref_id = 0;

    public $context_id = 0;

    public $website_ctx = 1;

    public $track_props = '';
    public $table_name = "e_table";
    public $credentials = "admin";

    public $link_table_name = "e_table";

    public $action_open_ss = "info";

    public $form_name = "db:vted/generic";

    public $what = "t.*";
    public $where, $group, $having, $order, $join;

    public $pad_templates = [];

    public $new_row_fields = [];

    public $pads = ['info'];

    public $multilevel = false;
    public $has_ord = false;

    public $has_tree = false;
    public $is_tree = false;
    public $tree = null;

    public $row;

    public $props_by_ref = false;

    public $debug = false;

    public function __construct()
    {
        $this->pad_templates[] = "vted/pads,%s";
    }

    public function can_save()
    {
        return true;
    }

    public function can_create()
    {
        return true;
    }

    public function can_delete()
    {
        return true;
    }

    public function can_delete_row($id) {
        return true;
    }

    public function can_view_pad()
    {
        return true;
    }

    public function json_row($row){
        unset($row['_full']);
        return $row;
    }

    public function post_clone_row($old_id, $new_id) {

    }

    public function clone_row($id) {
        global $emps, $pp, $key, $ss;

        $row = $this->load_row($id);

        if ($row) {
            unset($row['id']);
            unset($row['cdt']);
            unset($row['dt']);

            $nr = $row;

            $old_id = $id;

            $emps->db->sql_insert_row($this->table_name, ['SET' => $nr]);
            $id = $emps->db->last_insert();
            $this->new_ref_id = $id;
            $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);

            if ($this->props_by_ref) {
                $emps->p->save_properties_ref($nr, $context_id, $this->track_props);
            } else {
                $emps->p->save_properties($nr, $context_id, $this->track_props);
            }

            $this->post_clone_row($old_id, $id);
        }

        $old_pp = $pp;
        $emps->clearvars();
        $pp = $old_pp;
        $ss = "info";
        $key = $id;

        $link = $emps->elink();
        $emps->loadvars();

        return $link;
    }

    public function explain_row($row){
        global $emps;

        if ($this->is_tree) {
            $row['active'] = false;
            unset($row['full_id']);
        }

        $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $row['id']);
        if ($this->props_by_ref) {
            $row = $emps->p->read_properties_ref($row, $context_id);
        } else {
            $row = $emps->p->read_properties($row, $context_id);
        }

        return $row;
    }

    public function load_row($id){
        global $emps;

        $id = intval($id);

        $row = $emps->db->get_row($this->table_name, "id = {$id}");
        if($row) {
            $row = $this->explain_row($row);
            $row = $this->json_row($row);
            return $row;
        }
        return false;
    }

    public function unset_zero_values(&$row, $keys) {
        $x = explode(",", $keys);
        foreach ($x as $v) {
            if (!$row[$v]) {
                unset($row[$v]);
            }
        }
    }

    public function process_post_filter($filter) {
        return $filter;
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
                $this->delete_row($ra[0]);
            }
        }
    }

    public function delete_row($id) {
        global $emps;

        $this->pre_kill($id);
        $emps->db->query("delete from " . TP . $this->table_name . " where id={$id}");
        $this->after_kill($id);

    }

    public function count_children($id)
    {
        global $emps;

        $r = $emps->db->query("select count(*) from " . TP . $this->table_name . " where parent = $id");
        $ra = $emps->db->fetch_row($r);

        return $ra[0];
    }

    public function get_next_ord($id)
    {
        global $emps;

        if ($this->multilevel) {
            $r = $emps->db->query("select max(ord) from ".TP.$this->table_name." where parent = {$id}");
        } else {
            $r = $emps->db->query("select max(ord) from ".TP.$this->table_name);
        }

        $ra = $emps->db->fetch_row($r);
        $max = $ra[0];
        return $max + 100;
    }

    public function get_parents($id)
    {
        if ($id == 0) {
            return false;
        }
        $rv = [];
        $row = $this->load_row($id);
        $parents = $this->get_parents($row['parent']);
        if ($parents) {
            foreach ($parents as $parent) {
                $rv[] = $parent;
            }
        }

        $rv[] = $row;

        return $rv;
    }

    public function list_parents(){
        global $emps, $sd;

        $id = intval($sd);
        $lst = $this->get_parents($id);

        $rlst = [];
        $emps->loadvars();
        foreach($lst as $v){
            $sd = $v['id'];
            $v['link'] = $emps->elink();
            $rlst[] = $v;
        }
        $emps->loadvars();

        return $rlst;
    }

    public function list_rows(){
        global $emps, $start, $perpage, $ss, $key, $sd;

        $start = intval($start);
        $perpage = intval($perpage);

        if ($this->is_tree) {
            $this->order = " order by ord asc, id asc ";
        }

        $q = 'select SQL_CALC_FOUND_ROWS ' . $this->what . ' from ' . TP . $this->table_name . ' as t ' .
            $this->join . ' ' . $this->where . ' ' . $this->group . ' ' . $this->having . ' ' . $this->order .
            ' limit ' . $start . ',' . $perpage;
        $r = $emps->db->query($q);
        $this->last_sql_query = $q;
        $this->pages = $emps->count_pages($emps->db->found_rows());
        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
            $ra = $emps->db->row_types($this->table_name, $ra);
            $ra = $this->explain_row($ra);
            $ra = $this->json_row($ra);
            if ($this->multilevel) {
                $ra['children'] = $this->count_children($ra['id']);
            }

            $emps->loadvars();
            $ss = "info";
            $key = $ra['id'];
            $ra['ilink'] = $emps->elink();

            if ($this->multilevel) {
                $ss = "";
                $key = "";
                $sd = $ra['id'];
                $ra['children_link'] = $emps->elink();
            }

            if ($this->is_tree) {
                $this->where = " where parent = {$ra['id']} ";
                $ra['subs'] = $this->list_rows();
                unset($ra['full_id']);
            }

            $lst[] = $ra;
        }
        $emps->loadvars();

        return $lst;
    }

    public function return_invalid_user(){
        global $emps;

        $valid_user = false;
        if ($emps->auth->credentials($this->credentials)) {
            $valid_user = true;
        }

        if (!$valid_user) {
            $response = [];
            $response['code'] = "Error";
            $response['message'] = "Please log in with the appropriate credentials";
            $emps->json_response($response); exit;
        }
    }

    public function add_pad_template($txt)
    {
        array_unshift($this->pad_templates, $txt);
    }

    public function select_pad($code, $type)
    {
        global $emps;
        $emps->loadvars();

        foreach ($this->pad_templates as $v) {
            $uv = sprintf($v, $code);
            if ($type == 'view') {
                $fn = $emps->page_file_name('_' . $uv, 'view');
            } else {
                $fn = $emps->page_file_name('_' . $uv . '.php', 'inc');
            }

            if (!file_exists($fn)) {
                $v = str_replace(',', '/', $v);
                $uv = sprintf($v, $code);
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

    public function current_pad($type) {
        global $ss;
        $pad = $this->select_pad($ss, $type);
        return $pad;
    }


    public function list_pads() {
        global $emps, $smarty;

        $smarty->assign("lang", $emps->lang);
        $names = $smarty->fetch("db:vted/pad_names");
        $na = $emps->parse_array($names);

        if ($this->pad_names) {
            $nnames = $smarty->fetch($this->pad_names);
            $nna = $emps->parse_array($nnames);

            $na = array_merge($na, $nna);
        }

        $pads = [];
        foreach ($this->pads as $pad_code) {
            $pad = [];
            $pad['code'] = $pad_code;
            $pad['title'] = $na[$pad_code];
            $pad['view'] = $this->select_pad($pad_code, "view");
            $pads[] = $pad;
        }

        return $pads;
    }

    public function pre_create($nr) {
        return $nr;
    }

    public function post_create($id) {
    }

    public function pre_save($nr) {
        return $nr;
    }

    public function post_save($nr) {
    }

    public function pre_send_response($row) {
        return $row;
    }

    public function load_filter() {
        $this->filter = $_SESSION['vted_filter_' . $this->table_name];
        return $this->filter;
    }

    public function handle_request()
    {
        global $emps, $perpage, $smarty, $key, $sd, $ss, $vted, $start;

        $emps->loadvars();
        $x = explode("-", $key);
        $struct_mode = false;
        if ($x[0] == "struct") {
            $id = intval($x[1]);
            $struct_mode = true;
        } else {
            $id = intval($key);
        }

        if ($id > 0) {
            if ($struct_mode) {
                $this->tree->context_id = $emps->p->get_context($this->tree->ref_type, $this->tree->ref_sub, $id);
                $this->tree->ref_id = $id;
            } else {
                $this->context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);
                $this->ref_id = $id;
            }
        }

        if ($this->multilevel) {
            $smarty->assign("Multilevel", 1);
            $parent = intval($sd);
            if (!$this->where) {
                $this->where = " where 1=1 ";
            }
            $this->where .= " and t.parent = {$parent} ";
        }

        if ($_POST['post_save']) {
            $vted = $this;
            if ($struct_mode) {
                $vted = $this->tree;
            }
            if ($vted->can_save()) {
                $nr = $_REQUEST['payload'];
                unset($nr['id']);
                unset($nr['cdt']);
                unset($nr['dt']);

                $nr = $vted->pre_save($nr);

                $emps->db->sql_update_row($vted->table_name, ['SET' => $nr], "id = {$vted->ref_id}");

                if ($vted->props_by_ref) {
                    $emps->p->save_properties_ref($nr, $vted->context_id, $vted->track_props);
                } else {
                    $emps->p->save_properties($nr, $vted->context_id, $vted->track_props);
                }

                $nr['id'] = $vted->ref_id;
                $vted->post_save($nr);

                $response = [];
                $response['code'] = "OK";
                $emps->json_response($response); exit;

            } else {
                $response = [];
                $response['code'] = "Error";
                $response['message'] = "Сохранение запрещено!";
                $emps->json_response($response); exit;
            }
        }

        if ($_POST['post_search']) {
            $s_name = $this->table_name . "_search";
            $_SESSION[$s_name] = $_POST['search_text'];
            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_POST['post_filter']) {
            $filter = $_REQUEST['payload'];

            $filter = $this->process_post_filter($filter);

            $_SESSION['vted_filter_' . $this->table_name] = $filter;
            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_GET['set_filter']) {
            $filter = $_GET;
            unset($filter['set_filter']);
            $_SESSION['vted_filter_' . $this->table_name] = $filter;
            $emps->redirect_elink(); exit;
        }

        if ($_POST['post_clear_filter']) {
            unset($_SESSION['vted_filter_' . $this->table_name]);
            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_POST['post_new']) {
            if ($this->can_create()) {
                $nr = $_REQUEST['payload'];

                $emps->loadvars();

                $parent_id = intval($sd);
                $nr['parent'] = $parent_id;
                if ($this->has_ord) {
                    $nr['ord'] = $this->get_next_ord($parent_id);
                }

                $nr = $this->pre_create($nr);

                $nr = array_merge($nr, $this->new_row_fields);

                $emps->db->sql_insert_row($this->table_name, ['SET' => $nr]);
                $id = $emps->db->last_insert();
                $context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);

                if ($this->props_by_ref) {
                    $emps->p->save_properties_ref($nr, $context_id, $this->track_props);
                } else {
                    $emps->p->save_properties($nr, $context_id, $this->track_props);
                }

                $this->post_create($id);

                $response = [];
                $response['code'] = "OK";
                $emps->json_response($response); exit;

            } else {
                $response = [];
                $response['code'] = "Error";
                $response['message'] = "Создание новых записей запрещено!";
                $emps->json_response($response); exit;
            }
        }

        if ($_POST['post_create_folder']) {
            if ($this->can_create() && $this->has_tree) {
                $parent_id = intval($_REQUEST['parent_id']);

                $nr = [];
                $nr['parent'] = $parent_id;
                if ($this->tree->has_ord) {
                    $nr['ord'] = $this->tree->get_next_ord($parent_id);
                }

                $nr = $this->tree->pre_create($nr);

                $nr = array_merge($nr, $this->tree->new_row_fields);

                $emps->db->sql_insert_row($this->tree->table_name, ['SET' => $nr]);
                $id = $emps->db->last_insert();
                $context_id = $emps->p->get_context($this->tree->ref_type, $this->tree->ref_sub, $id);

                if ($this->tree->props_by_ref) {
                    $emps->p->save_properties_ref($nr, $context_id, $this->tree->track_props);
                } else {
                    $emps->p->save_properties($nr, $context_id, $this->tree->track_props);
                }

                $ord = $emps->db->query("select max(ord) from ".TP.$this->tree->table_name.
                    " where parent = {$parent_id}");
                $ord += 100;
                $nr = [];
                $nr['name'] = "Подраздел №" . $id;
                $nr['ord'] = $ord;
                $emps->db->sql_update_row($this->tree->table_name, ['SET' => $nr], "id = {$id}");
                $nr = [];
                $nr['full_id'] = $emps->get_full_id($id, $this->tree->table_name,'parent','ord');
                $emps->db->sql_update_row($this->tree->table_name, ['SET' => $nr], "id = {$id}");

                $response = [];
                $response['code'] = "OK";
                $newrow = $this->tree->load_row($id);
                unset($newrow['full_id']);
                $response['row'] = $newrow;
                $emps->json_response($response); exit;

            } else {
                $response = [];
                $response['code'] = "Error";
                $response['message'] = "Создание новых записей запрещено!";
                $emps->json_response($response); exit;
            }
        }

        if ($_POST['post_delete']) {
            if ($this->can_delete()) {

                $id = intval($_POST['post_delete']);
                if ($this->can_delete_row($id)) {
                    $this->delete_row($id);

                    $response = [];
                    $response['code'] = "OK";
                    $emps->json_response($response); exit;
                } else {
                    $response = [];
                    $response['code'] = "Error";
                    $response['message'] = "Удаление элемента запрещено!";
                    $emps->json_response($response); exit;
                }
            } else {
                $response = [];
                $response['code'] = "Error";
                $response['message'] = "Удаление запрещено!";
                $emps->json_response($response); exit;
            }
        }

        if ($_POST['post_delete_folder']) {
            if ($this->can_delete()) {
                $id = intval($_POST['id']);

                $this->tree->delete_row($id);

                $response = [];
                $response['code'] = "OK";
                $emps->json_response($response); exit;
            } else {
                $response = [];
                $response['code'] = "Error";
                $response['message'] = "Удаление запрещено!";
                $emps->json_response($response); exit;
            }
        }

        if ($_POST['post_clipboard']) {
            $mode = $_POST['post_clipboard'];
            $clipboard = $_SESSION[$this->table_name."_clipboard"];
            if (!$clipboard) {
                $clipboard = [];
            }
            $slst = $_POST['slst'];
            foreach ($slst as $v) {
                foreach ($clipboard['copy'] as $n => $e) {
                    if ($e['id'] == $v['id']) {
                        unset($clipboard['copy'][$n]);
                    }
                }
                foreach ($clipboard['cut'] as $n => $e) {
                    if ($e['id'] == $v['id']) {
                        unset($clipboard['cut'][$n]);
                    }
                }
            }
            if (!isset($clipboard[$mode])) {
                $clipboard[$mode] = [];
            }
            $clipboard[$mode] = array_merge($clipboard[$mode], $slst);
            $_SESSION[$this->table_name."_clipboard"] = $clipboard;
            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_POST['post_paste']) {

            $clipboard = $_SESSION[$this->table_name."_clipboard"];
            $node_id = intval($sd);
            if ($node_id > 0 && $this->cats != null) {
                foreach ($clipboard['copy'] as $item) {
                    $this->cats->ensure_item_in_node($item['item_id'], $node_id);
                }
                foreach ($clipboard['cut'] as $item) {
                    $this->cats->remove_item_from_node($item['item_id'], $item['struct_id']);
                    $this->cats->ensure_item_in_node($item['item_id'], $node_id);
                }
            }
            $clipboard['copy'] = [];
            $clipboard['cut'] = [];
            $_SESSION[$this->table_name."_clipboard"] = $clipboard;

            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_GET['load_row']) {
            $this->return_invalid_user();
            $x = explode("-", $_GET['load_row']);
            $struct_mode = false;
            if ($x[0] == "struct") {
                $id = intval($x[1]);
                $struct_mode = true;
            } else {
                $id = intval($_GET['load_row']);
            }

            if ($struct_mode) {
                $row = $this->tree->load_row($id);
            } else {
                $row = $this->load_row($id);
            }

            $response = [];
            $response['code'] = "OK";
            if ($row) {
                $response['row'] = $row;
            } else {
                $response['code'] = "Error";
                $response['message'] = "Row #{$id} could not be loaded.";
            }
            $emps->json_response($response); exit;
        }

        if ($_GET['load_list']) {
            $this->return_invalid_user();

            if (!$perpage) {
                $perpage = 50;
            }

            if ($this->has_tree) {
                $emps->loadvars();
                if (!$this->where) {
                    $this->where = " where 1=1 ";
                }
                $link_table = $this->tree->link_table_name;
                if ($sd) {
                    if ($sd == 'all') {
                        // no modifications, display all items
                    } else {
                        $struct_id = intval($sd);
                        $this->join  .= " join ".TP.$link_table." as lt on lt.item_id = t.id and lt.struct_id = {$struct_id} ";
                    }
                } else {
                    $this->join  .= " left join ".TP.$link_table." as lt on lt.item_id = t.id ";
                    if (!$this->having) {
                        $this->having = " having 1=1 ";
                    }
                    $this->what = "t.*, lt.struct_id";
                    $this->having = " and lt.struct_id is null ";
                }
            }
            $lst = $this->list_rows();

            $response = [];
            $response['code'] = "OK";
            $response['lst'] = $lst;
            $response['pages'] = $this->pages;

            $clipboard = $_SESSION[$this->table_name."_clipboard"];
            $response['clipboard'] = $clipboard;
            if ($clipboard) {
                $response['clipboard'] = $clipboard;
            }
            $s_name = $this->table_name . "_search";
            $response['search_text'] = $_SESSION[$s_name];
            if($this->debug){
                $response['query'] = $this->last_sql_query;
            }
            if($this->multilevel) {
                $response['parents'] = $this->list_parents();
            }
            $this->load_filter();
            if ($this->filter) {
                $response['filter'] = $this->filter;
            }

            $response = $this->pre_send_response($response);

            $emps->json_response($response); exit;
        }

        if ($_GET['load_tree']) {
            $parent_id = intval($_GET['parent_id']);
            $start = 0;
            $perpage = 10000;
            $emps->savevars();
            $this->tree->where = " where parent = {$parent_id} ";
            $lst = $this->tree->list_rows();
            $response = [];
            $response['code'] = "OK";
            $response['tree'] = $lst;
            $emps->json_response($response); exit;
        }

        if ($this->tree) {
            $pads = $this->tree->list_pads();
            $smarty->assign("struct_pads", $pads);
        }

        $pads = $this->list_pads();
        $smarty->assign("pads", $pads);

        $emps->loadvars();

        if ($struct_mode) {
            $fn = $this->tree->current_pad('controller');

            $vted = $this->tree;
            if (file_exists($fn) && $this->tree->can_view_pad()) {
                require_once $fn;
            }

        } else {
            $fn = $this->current_pad('controller');

            $vted = $this;
            if (file_exists($fn) && $this->can_view_pad()) {
                require_once $fn;
            }

        }

        $emps->loadvars();
        $sd = ""; $ss = ""; $key = "";
        $smarty->assign("ToTopLink", $emps->elink());
        $emps->loadvars();

        $smarty->assign("form_name", $vted->form_name);
        $smarty->assign("context_id", $vted->context_id);

    }
}