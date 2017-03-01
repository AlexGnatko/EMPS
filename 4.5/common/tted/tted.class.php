<?php

class EMPS_TabulatedTableEditor
{
    public $table_name = "ul_local_fares";
    public $what = "*";
    public $where = "";
    public $group = "";
    public $having = "";
    public $order = "";

    public $track_values = "";

    public $pages, $total;

    public $custom_list = false;

    public function handle_value($field, $id)
    {
        global $emps, $SET;

        unset($_REQUEST[$field]);

        $row = $emps->db->get_row($this->table_name, "id = " . $id);

        if ($row) {
            $value = $_POST[$field][$id];
            if ($row[$field] != $value) {
                $SET = array();
                $SET[$field] = $value;
                $emps->db->sql_update($this->table_name, "id = " . $id);
            }
        }
    }

    public function handle_row($row)
    {
        return $row;
    }

    public function handle_post_values()
    {
        global $emps;

        $x = explode(",", $this->track_values);

        while (list($n, $v) = each($_POST['item'])) {
            $id = $n;
            reset($x);
            foreach ($x as $code) {
                unset($_REQUEST[$code]);
                if ($_POST[$code][$id] != "") {
                    $this->handle_value($code, $id);
                }
            }
        }
    }

    public function handle_request()
    {
        global $smarty, $emps, $key, $ss, $start, $perpage, $total;

        if ($_POST['post_values']) {
            $this->handle_post_values();
            $emps->redirect_elink();
            exit();
        }

        if ($this->custom_list) {
            return;
        }

        if (!$start) {
            $start = 0;
        }
        if (!$perpage) {
            $perpage = 25;
        }

        if (!$this->what) {
            $this->what = "*";
        }
        $q = 'select SQL_CALC_FOUND_ROWS ' . $this->what . ' from ' . TP . $this->table_name . ' ' . $this->join . ' ' . $this->where . ' ' .
            $this->group . ' ' . $this->having . ' ' . $this->order . ' limit ' . $start . ',' . $perpage;

        $r = $emps->db->query($q);
        $lst = array();

        $this->total = $emps->db->found_rows();
        $this->pages = $emps->count_pages($this->total);

        $smarty->assign("pages", $this->pages);

        while ($ra = $emps->db->fetch_named($r)) {
            $ra = $this->handle_row($ra);
            $lst[] = $ra;
        }

        $emps->loadvars();

        $smarty->assign("lst", $lst);
    }
}

