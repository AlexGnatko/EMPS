<?php

class EMPS_DB
{
    public $db;
    public $operational = false;
    public $sql_errors = array();
    public $sql_timing = array();
    public $sql_time = 0;
    public $columns_cache = array();
    public $row_cache = array();

    public $sql_take = array();
    public $sql_value = array();
    public $sql_reset = array();
    public $sql_null = array();

    public $no_dt_update = false;

    public $no_caching = false;

    public $always_use_wt = false;

    public $was_inserted = false;

    public function connect()
    {
        global $emps_db_config;
        $this->db = mysqli_connect($emps_db_config['host'], $emps_db_config['user'], $emps_db_config['password'], $emps_db_config['database']);

        if (!$this->db) {
            error_log("mysqli error: " . mysqli_connect_error());
            return;
        }

        $this->operational = true;

        $this->query('set names "' . $emps_db_config['charset'] . '"');
        $this->query('set autocommit = 1');

        unset($emps_db_config);
    }

    public function disconnect()
    {
        mysqli_close($this->db);
    }

    public function __construct()
    {
        $this->connect();
    }

    public function clear_cache() {
        $this->columns_cache = [];
        $this->row_cache = [];
    }

    public function query($query)
    {
        global $emps;

        if (!$this->db) {
            return false;
        }

        if (EMPS_TIMING) {
            $s = emps_microtime_float(microtime());
        }
        try {
            $r = $this->db->query($query);
        } catch (\Exception $e) {
            $r = false;
        }

        if (EMPS_TIMING) {
            $e = emps_microtime_float(microtime());
            $l = ($e - $s) * 1000;
        }

        $error_text = $this->sql_error();

        $log = array();
        $log['query'] = $query;
        if (!$r) {
            $log['error'] = $error_text;
            error_log($error_text . " in query: " . $query);
            $spacer = "       ";
            $btxt = "Backtrace:" . $spacer;

            $bt = debug_backtrace();
            array_shift($bt);
            foreach ($bt as $v) {
                $class = "";
                if ($v['class']) {
                    $class = $v['class'] . $v['type'];
                }
                $btxt .= $class . $v['function'] . ", line " . $v['line'] . $spacer;
            }
            $this->sql_errors[] = $log;
            error_log($btxt);
        }

        if (EMPS_TIMING && !$this->no_caching) {
            $log['ms'] = $l;
            $log['ams'] = ($e - $emps->start_time) * 1000;
            $this->sql_timing[] = $log;
            $this->sql_time += $l;
        }
        return $r;
    }

    public function last_insert()
    {
/*        $r = $this->query("select last_insert_id()");
        $ra = $this->fetch_row($r);
        $this->free($r);
        return $ra[0];*/
        return mysqli_insert_id($this->db);
    }

    private function table_columns($table)
    {
        if (isset($this->columns_cache[$table])) {
            return $this->columns_cache[$table];
        }
        $q = "show columns from $table";
        $r = $this->query($q);
        $lst = array();

        while ($ra = $this->fetch_row($r)) {
            $lst[] = $ra;
        }
        $this->columns_cache[$table] = $lst;
        return $lst;
    }

    private function sql_findcols($table)
    {
        global $sql_reset;
        $columns = $this->table_columns($table);
        foreach($columns as $v) {
            $name = $v[0];
            if (isset($GLOBALS['SET'][$name])) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "'" . $this->sql_escape($GLOBALS['SET'][$name]) . "'";
            } elseif ($_REQUEST[$name] != '') {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "'" . $this->sql_escape($_REQUEST[$name]) . "'";
            } elseif ($sql_reset[$name]) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "''";
            } elseif ($this->sql_null[$name]) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "null";
            }
        }
    }

    public function set_sql_reset(){
        global $sql_reset;

        $sql_reset = [];
        foreach($_REQUEST as $n => $v){
            if($v == ""){
                $sql_reset[$n] = true;
            }
        }
    }

    private function sql_findcols_row($table, $row)
    {
        $columns = $this->table_columns($table);
        foreach($columns as $v) {
            $name = $v[0];
            if (isset($row['SET'][$name])) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "'" . $this->sql_escape($row['SET'][$name]) . "'";
            } elseif (isset($row['RESET'][$name])) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "''";
            } elseif (isset($row['NULL'][$name])) {
                $this->sql_take[$name] = "`" . $name . "`";
                $this->sql_value[$name] = "null";
            }
        }
    }

    public function and_clause($lst) {
        $parts = [];
        foreach ($lst as $v) {
            $part = $this->where_clause($v);
            $parts[] = $part;
        }

        if (count($parts) == 0) {
            return "";
        }
        return "(" . implode(" and ", $parts) . ")";
    }

    public function or_clause($lst) {
        $parts = [];
        foreach ($lst as $v) {
            $part = $this->where_clause($v);
            $parts[] = $part;
        }

        if (count($parts) == 0) {
            return "";
        }
        return "(" . implode(" or ", $parts) . ")";
    }

    public function where_clause($query){
        $parts = [];
        foreach($query as $n => $v){
            if ($n == '$and') {
                $part = $this->and_clause($v);
            } elseif ($n == '$or') {
                $part = $this->or_clause($v);
            } else {
                $part = "`{$n}`";
                if(is_numeric($v) || is_float($v)) {
                    $part .= " = " . $v;
                }elseif(is_array($v)){
                    if (isset($v['$gt'])) {
                        $value = $v['$gt'];
                        $part .= " > {$value}";
                    } elseif (isset($v['$gte'])) {
                        $value = $v['$gte'];
                        $part .= " >= {$value}";
                    } elseif (isset($v['$lt'])) {
                        $value = $v['$lt'];
                        $part .= " < {$value}";
                    } elseif (isset($v['$lte'])) {
                        $value = $v['$lte'];
                        $part .= " <= {$value}";
                    } elseif (isset($v['$like'])) {
                        $value = $v['$like'];
                        $part .= " like ('$value') ";
                    } elseif (isset($v['$in'])) {
                        $value = $v['$in'];
                        $part .= " in ($value) ";
                    } elseif (isset($v['$not'])) {
                        $value = $v['$not'];
                        $part .= " <> $value ";
                    } else {
                        $a = [];
                        foreach ($v as $item) {
                            if (is_string($item)) {
                                $item = $this->sql_escape($item);
                                $item = "'{$item}'";
                            }
                            $a[] = $item;
                        }
                        $str = implode(", ", $a);
                        $part .= " in ({$str})";
                    }
                }else{
                    $v = $this->sql_escape($v);
                    $part .= " = '{$v}'";
                }
            }
            $parts[] = $part;
        }
        return implode(" and ", $parts);
    }

    public function sql_insert($table)
    {
        global $SET;
        if (!isset($_REQUEST['cdt']) && !isset($SET['cdt'])) {
            $SET['cdt'] = time();
        }
        if (!isset($_REQUEST['dt']) && !isset($SET['dt'])) {
            $SET['dt'] = time();
        }
        $this->sql_take = array();
        $this->sql_value = array();
        $this->sql_findcols(TP . $table);
        if (count($this->sql_take) == 0 || count($this->sql_value) == 0) {
            return 0;
        }
        $t = implode(",", $this->sql_take);
        $v = implode(",", $this->sql_value);
        $q = "insert into " . TP . "$table ($t) values ($v)";
        $r = $this->query($q);
        return $r;
    }

    public function sql_insert_row($table, $row)
    {
        if (!isset($row['SET']['cdt'])) {
            $row['SET']['cdt'] = time();
        }
        if (!isset($row['SET']['dt'])) {
            $row['SET']['dt'] = time();
        }
        $this->sql_take = array();
        $this->sql_value = array();
        $this->sql_findcols_row(TP . $table, $row);
        if (count($this->sql_take) == 0 || count($this->sql_value) == 0) {
            return 0;
        }
        $t = implode(",", $this->sql_take);
        $v = implode(",", $this->sql_value);
        $q = "insert into " . TP . "$table ($t) values ($v)";
//        error_log(json_encode($row));
//        error_log($q);
        $r = $this->query($q);
        return $r;
    }

    public function sql_update($table, $cond)
    {
        global $SET;

        if (!isset($_REQUEST['dt']) && !isset($SET['dt']) && !$this->no_dt_update) {
            $SET['dt'] = time();
        }
        $this->sql_take = array();
        $this->sql_value = array();
        $this->sql_findcols(TP . $table);
        if (count($this->sql_take) == 0 || count($this->sql_value) == 0) return 0;

        $t = 'update ' . TP . $table;
        $st = 0;
        foreach ($this->sql_take as $n => $v) {
            if ($st) $t .= ","; else $t .= " set";
            $t .= " $v=" . $this->sql_value[$n];
            $st = 1;
        }
        $t .= " where " . $cond;
        $r = $this->query($t);
        return $r;
    }

    public function sql_update_row($table, $row, $cond)
    {
        if (!isset($row['SET']['dt']) && !$this->no_dt_update && !$row['nodt']) {
            $row['SET']['dt'] = time();
        }

        $this->sql_take = array();
        $this->sql_value = array();
        $this->sql_findcols_row(TP . $table, $row);
        if (count($this->sql_take) == 0 || count($this->sql_value) == 0) return 0;

        $t = 'update ' . TP . $table;
        $st = 0;
        foreach ($this->sql_take as $n => $v) {
            if ($st) $t .= ","; else $t .= " set";
            $t .= " $v=" . $this->sql_value[$n];
            $st = 1;
        }
        $t .= " where " . $cond;
        $r = $this->query($t);
        return $r;
    }

    public function sql_ensure_row($table, $row, $single = false){
        $where = $this->where_clause($row);
        //error_log($table." > ".$where);
        $existing_row = $this->get_row($table, $where);
        if($existing_row){
            if ($single) {
                $this->query("delete from ".TP.$table." where " . $where . " and id <> {$existing_row['id']}");
            }
            return $existing_row;
        }
        $update = ['SET' => $row];
        $this->sql_insert_row($table, $update);
        $id = $this->last_insert();
        $this->was_inserted = true;
        $row = $this->get_row($table, "id = {$id}");
        return $row;
    }

    public function get_max($table, $field, $where){
        $addwhere = "";
        if($where){
            $addwhere = " where {$where} ";
        }
        $r = $this->query("select max(`{$field}`) from ".TP.$table.$addwhere);
        $ra = $r->fetch_row($r);
        return $ra[0];
    }

    public function found_rows()
    {
        $r = $this->query("select found_rows()");
        $ra = $this->fetch_row($r);
        return $ra[0];
    }

    public function get_row_plain($table, $where)
    {
        $r = $this->query('select * from ' . $table . ' where ' . $where);
        if ($r) {
            $row = $this->fetch_named($r);
            if (!$this->no_caching) {
                $this->row_cache[$table][$where] = $row;
            }
            $this->free($r);
            return $row;
        } else {
            return array();
        }
    }


    public function get_row_wt_plain($table, $where)
    {
        $row = $this->get_row_plain($table, $where);

        if($row){
            $row = $this->row_types_plain($table, $row);
        }

        return $row;
    }

    public function row_types_plain($table, $row){
        $columns = $this->table_columns($table);
        foreach($columns as $v) {
            $name = $v[0];
            $type = mb_strtolower($v[1]);
            $v = $row[$name];
            if(strpos($type, "int") !== false){
                $v = intval($v);
            }
            if(strpos($type, "decimal") !== false){
                $v = floatval($v);
            }
            if(strpos($type, "double") !== false){
                $v = doubleval($v);
            }
            if(strpos($type, "float") !== false){
                $v = floatval($v);
            }
            $row[$name] = $v;
        }
        return $row;
    }

    public function row_types($table, $row){
        return $this->row_types_plain(TP . $table, $row);
    }

    public function get_row_plain_cache($table, $where)
    {
        if (isset($this->row_cache[$table][$where])) {
            return $this->row_cache[$table][$where];
        }
        $row = $this->get_row_plain($table, $where);
        $row_cache[$table][$where] = $row;
        return $row;
    }

    public function get_row($table, $where)
    {
        if($this->always_use_wt){
            return $this->get_row_wt_plain(TP . $table, $where);
        }
        return $this->get_row_plain(TP . $table, $where);
    }

    public function get_row_wt($table, $where)
    {
        return $this->get_row_wt_plain(TP . $table, $where);
    }

    public function get_row_cache($table, $where)
    {
        return $this->get_row_plain_cache(TP . $table, $where);
    }

    public function fetch_named($r)
    {
        if ($r) {
            return mysqli_fetch_assoc($r);
        } else {
            return array();
        }
    }

    public function fetch_row($r)
    {
        if ($r) {
            return mysqli_fetch_row($r);
        } else {
            return false;
        }
    }

    public function sql_error()
    {
        return mysqli_error($this->db);
    }

    public function sql_escape($txt)
    {
        return mysqli_real_escape_string($this->db, $txt);
    }

    public function sql_rewind($r){
        return mysqli_data_seek($r, 0);
    }

    public function free($r)
    {
        if ($r === false) {
            return;
        }
        return mysqli_free_result($r);
    }
}

