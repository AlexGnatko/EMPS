<?php

$emps->p->no_full = true;
$emps->p->no_idx = true;

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

    public $action_open_ss = "info";

    public $form_name = "db:vted/generic";

    public $what = "*";
    public $where, $group, $having, $order, $join;

    public $pad_templates = [];

    public $new_row_fields = [];

    public $pads = ['info'];

    public $multilevel = false;
    public $has_ord = false;

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

    public function can_delete()
    {
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

    public function explain_row($row){
        global $emps;

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

        $q = 'select SQL_CALC_FOUND_ROWS ' . $this->what . ' from ' . TP . $this->table_name . ' as t ' .
            $this->join . ' ' . $this->where . ' ' . $this->group . ' ' . $this->having . ' ' . $this->order .
            ' limit ' . $start . ',' . $perpage;
        $r = $emps->db->query($q);
        $this->last_sql_query = $q;
        $this->pages = $emps->count_pages($emps->db->found_rows());
        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
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

    public function pre_save($nr) {
        return $nr;
    }

    public function post_save($nr) {
    }

    public function handle_request()
    {
        global $emps, $perpage, $smarty, $key, $sd, $ss;


        $id = intval($key);
        if ($id > 0) {
            $this->context_id = $emps->p->get_context($this->ref_type, $this->ref_sub, $id);
            $this->ref_id = $id;
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
            $nr = $_REQUEST['payload'];
            unset($nr['id']);
            unset($nr['cdt']);
            unset($nr['dt']);

            $nr = $this->pre_save($nr);

            $emps->db->sql_update_row($this->table_name, ['SET' => $nr], "id = {$this->ref_id}");

            if ($this->props_by_ref) {
                $emps->p->save_properties_ref($nr, $this->context_id, $this->track_props);
            } else {
                $emps->p->save_properties($nr, $this->context_id, $this->track_props);
            }

            $nr['id'] = $this->ref_id;
            $this->post_save($nr);

            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }


        if ($_POST['post_new']) {
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

            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;

        }

        if ($_POST['post_delete']) {
            $id = intval($_POST['post_delete']);

            $this->delete_row($id);

            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;
        }

        if ($_GET['load_row']) {
            $this->return_invalid_user();
            $id = intval($_GET['load_row']);
            $row = $this->load_row($id);
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

            $lst = $this->list_rows();

            $response = [];
            $response['code'] = "OK";
            $response['lst'] = $lst;
            $response['pages'] = $this->pages;
            if($this->debug){
                $response['query'] = $this->last_sql_query;
            }
            if($this->multilevel) {
                $response['parents'] = $this->list_parents();
            }

            $emps->json_response($response); exit;
        }

        $pads = $this->list_pads();
        $smarty->assign("pads", $pads);

        $emps->loadvars();
        $fn = $this->current_pad('controller');

        if (file_exists($fn) && $this->can_view_pad()) {
            require_once $fn;
        }

        $emps->loadvars();
        $sd = ""; $ss = ""; $key = "";
        $smarty->assign("ToTopLink", $emps->elink());
        $emps->loadvars();

        $smarty->assign("form_name", $this->form_name);
        $smarty->assign("context_id", $this->context_id);
    }
}