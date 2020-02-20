<?php

class EMPS_NG_PickList
{
    public $id;
    public $table_name;
    public $filter;

    public $perpage = 10;

    public $what = "t.*";
    public $join = "";
    public $where = "";
    public $orderby = " order by t.id asc ";

    public function parse_request()
    {
        global $emps, $key;
        $x = explode("|", $key, 2);
        $this->table_name = $emps->db->sql_escape($x[0]);
        $this->filter = $x[1];
    }

    public function handle_row($ra)
    {
        $ra['display_name'] = $ra['name'];
        unset($ra['full_id']);
        return $ra;
    }

    public function make_and($extra)
    {
        global $emps;
        $and = "";
        if ($extra) {
            $x = explode("|", $extra);
            foreach ($x as $v) {
                $xx = explode("=", $v, 2);
                if ($xx[0] == 'group') {
                    continue;
                }
                if (count($xx) == 2) {
                    $and .= " and ";
                    $and .= $emps->db->sql_escape($xx[0]) . " = '" . $emps->db->sql_escape($xx[1]) . "'";
                } else {
                    $xx = explode("<>", $v, 2);
                    if (count($xx) == 2) {
                        $and .= " and ";
                        $and .= $emps->db->sql_escape($xx[0]) . " <> '" . $emps->db->sql_escape($xx[1]) . "'";
                    } else {
                        $xx = explode("_in_", $v, 2);
                        if (count($xx) == 2) {
                            $and .= " and ";
                            $and .= $emps->db->sql_escape($xx[0]) . " in (" . $emps->db->sql_escape($xx[1]) . ")";
                        }
                    }
                }
            }
        }
        return $and;
    }

    public function handle_request()
    {
        global $emps, $start, $perpage;

        $this->parse_request();

        if ($this->table_name == "e_users") {
            $text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['text']));

            $default_text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['default_text']));
            if ($text == $default_text) {
                $text = "";
            }

            if ($this->filter) {
                $x = explode('|', $this->filter);
                $el = array();
                foreach ($x as $v) {
                    $xx = explode('=', $v);
                    $el[$xx[0]] = $xx[1];
                }
            }

            $and = $this->make_and($this->filter);

            $perpage = $this->perpage;
            $start = intval($start);

            $sql = "select SQL_CALC_FOUND_ROWS * from " . TP .  $this->table_name . "
                where (username like '%{$text}%' or fullname like '%{$text}%') {$and} limit {$start}, {$perpage}";
            if ($el['group']) {
                $sql = "select SQL_CALC_FOUND_ROWS t.* from " . TP . $this->table_name . " as t 
                join " . TP . "e_users_groups as ug on
                ug.user_id = t.id
                and ug.group_id = '" . $el['group'] . "' 
                where (t.username like '%{$text}%' or t.fullname like '%{$text}%') {$and} limit {$start}, {$perpage}";

            }

            $r = $emps->db->query($sql);

            $pages = $emps->count_pages($emps->db->found_rows());

            $lst = array();
            while ($ra = $emps->db->fetch_named($r)) {
                $ra = $emps->db->row_types($this->table_name, $ra);
                unset($ra['password']);
                $ra['display_name'] = $ra['username'];
                $ra['extra_info'] = $ra['fullname'];
                $lst[] = $ra;
            }

        } else {
            $text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['text']));
            $id = 0;
            if ($text) {
                $id = intval($text);
            }

            $default_text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['default_text']));
            if ($text == $default_text) {
                $text = "";
            }

            $and = $this->make_and($this->filter);

            $perpage = $this->perpage;
            $start = intval($start);

            $r = $emps->db->query("select SQL_CALC_FOUND_ROWS " . $this->what . " from " . TP .
                $this->table_name . " as t " . $this->join . " where ((t.name like '%$text%') or (t.id = {$id})) " . $and . " " .
                $this->where . $this->orderby . " limit {$start}, {$perpage}");

            $pages = $emps->count_pages($emps->db->found_rows());

            $lst = array();
            while ($ra = $emps->db->fetch_named($r)) {
                $ra = $emps->db->row_types($this->table_name, $ra);
                $ra = $this->handle_row($ra);
                $lst[] = $ra;
            }
        }

        $response = [];
        $response['code'] = "OK";
        $response['list'] = $lst;
        $response['pages'] = $pages;

        echo json_encode($response);
    }
}

header("Content-Type: application/json; charset=utf-8");

$fn = $emps->page_file_name('_pick/ng/list,project', 'controller');
if (file_exists($fn)) {
    require_once $fn;
}

if (!isset($pick)) {
    $pick = new EMPS_NG_PickList;
}

$emps->no_smarty = true;
$emps->no_autopage = true;
$pick->handle_request();

