<?php

class EMPS_Properties
{
    public $db;
    private $context_cache = [];
    private $load_context_cache = [];

    private $cleanups = [];

    public $default_ctx = false;

    public $no_full = false;
    public $no_idx = false;

    public $wt = false;

    public function clear_cache() {
        $this->context_cache = [];
        $this->load_context_cache = [];
    }

    public function save_property($context_id, $code, $datatype, $value, $history, $idx)
    {
        global $emps;

        $SET = array();
        $SET['context_id'] = $context_id;
        $SET['code'] = $code;
        $SET['idx'] = $idx;
        $SET['type'] = $datatype;
        switch ($datatype) {
            case "i":
            case "r":
                $field = "v_int";
                break;
            case "b":
                $field = "v_bool";
                break;
            case "f":
                $field = "v_float";
                break;
            case "c":
                $field = "v_char";
                break;
            case "d":
                $field = "v_data";
                break;
            default:
                $field = "v_text";
        }
        $SET[$field] = $value;
        $SET['dt'] = time();
        $SET['status'] = 0;

        $row = $emps->db->get_row("e_properties", "context_id = ".$context_id." and code = '".$code."' and status = 0 and idx = ".$idx);
        if (!$row) {
            $emps->db->sql_insert_row("e_properties", ['SET' => $SET]);
        } else {
            if (!$history) {
                $emps->db->sql_update_row("e_properties", ['SET' => $SET], "id=" . $row['id']);
                $emps->db->query("delete from ".TP."e_properties where context_id = ".$context_id." and code = '".$code."' and status = 0 and idx = ".$idx." and id <> ".$row['id']);
            } else {
                if ($row[$field] == $value) {
                } else {
                    $S = $SET;
                    $SET = array();
                    $SET['status'] = 1;
                    $emps->db->sql_update_row("e_properties", ['SET' => $SET], "id=" . $row['id']);
                    $SET = $S;
                    $emps->db->sql_insert_row("e_properties", ['SET' => $SET]);
                }
            }
        }
        return $emps->db->last_insert();
    }

    public function clear_property($context_id, $code)
    {
        global $emps;
        $emps->db->query('delete from ' . TP . "e_properties where context_id=$context_id and code='$code'");
    }

    public function remove_empty_idx($ra)
    {
        $rv = array();
        foreach ($ra as $n => $v) {
            if (!$v) {
                continue;
            }
            $rv[] = $v;
        }
        return $rv;
    }

    public function treat_multiline_properties($context_id, $lst)
    {
        $x = explode(",", $lst);
        foreach ($x as $v) {
            $_POST[$v . '_idx'] = $this->remove_empty_idx($_POST[$v . '_idx']);
            $_POST[$v] = $_REQUEST[$v] = $_POST[$v . '_idx'];
            if (!$_REQUEST[$v]) {
                $this->clear_property($context_id, $v);
            }
        }
    }

    public function save_properties($ra, $context_id, $props)
    {
        global $emps;

        $x = explode(",", $props);
        foreach ($x as $n => $v) {
            $v = trim($v);
            $xv = explode(":", $v);
            $xv[0] = trim($xv[0]);

            if (isset($ra[$xv[0]])) {
                $value = $ra[$xv[0]];
                if ($xv[2] == 'h') $history = true; else $history = false;
                if ($xv[3] == 'idx') $explicit_idx = true; else $explicit_idx = false;
                $code = $xv[0];
                if ($history) {
                    $emps->db->query('update ' . TP . "e_properties set status=1 where 
                    context_id = {$context_id} and code = '{$code}'");
                }

                if (!is_array($value)) {
                    $pv = $value;
                    $value = array();
                    $value[] = $pv;
                }
                $idx = 0;
                $vtaken = "0";
                foreach ($value as $nn => $vv) {
                    $take = $idx;
                    if ($explicit_idx) {
                        $take = $nn + 0;
                    }
                    if ($vtaken != "") $vtaken .= ",";
                    $vtaken .= $take;

                    if (strcmp($vv, 'on') == 0) {
                        $vv = 1;
                        if ($explicit_idx) {
                            $vv = $take;
                        }
                    }

                    $this->save_property($context_id, $xv[0], $xv[1], $vv, $history, $take);
                    $idx++;
                }
                $emps->db->query('delete from ' . TP . "e_properties where 
                    context_id = {$context_id} and code = '{$code}' and status = 0 and (not (idx in ({$vtaken})))");
            }
        }
    }

    public function save_property_ref($code, $prefix, $value, $general_context_id, $type) {
        global $emps;

        if ($value) {
            if (is_array($value)) {
                $ids = [];
                foreach ($value as $n => $v) {
                    $ret_ids = $this->save_property_ref($n, $prefix . "." . $code, $value[$n], $general_context_id);
                    $ids = array_merge($ids, $ret_ids);
                }
                return $ids;
            } else {
                if ($prefix != "") {
                    $full_code = $prefix . "." . $code;
                } else {
                    $full_code = $code;
                }

                if (!isset($type)) {
                    if (isset($this->prop_types[$full_code])) {
                        $type = $this->prop_types[$full_code];
                    }
                }

                if (!isset($type)) {
                    if (is_numeric($value)) {
                        $type = "i";
                    }
                    if (is_bool($value)) {
                        $type = "b";
                    }
                    if (is_float($value)) {
                        $type = "f";
                    }
                    if (is_string($value)) {
                        $type = "c";
                        if (strlen($value) > 255) {
                            $type = "t";
                        }
                    }
                }

                $field = "v_text";
                switch($type) {
                    case "i":
                    case "r":
                        $field = "v_int";
                        break;
                    case "f":
                        $field = "v_float";
                        break;
                    case "c":
                        $field = "v_char";
                        break;
                    case "d":
                        $field = "v_data";
                        break;
                    case "b":
                        $field = "v_bool";
                        break;
                }

                $nr = [];
                $nr['context_id'] = $general_context_id;
                $nr['code'] = $full_code;
                $nr['type'] = $type;
                $nr[$field] = $value;

                $prop = $emps->db->sql_ensure_row("e_properties", $nr);
                if ($prop) {
                    return [$prop['id']];
                }
            }
        }

        return [];

    }

    public function save_properties_ref($ra, $context_id, $props) {
        global $emps;

        $context = $this->load_context($context_id);
        $ref_type = $context['ref_type'];
        $ref_sub = $context['ref_sub'];
        $general_context_id = $this->get_context($ref_type, $ref_sub, -1);

        $x = explode(",", $props);
        foreach ($x as $v) {
            $v = trim($v);
            $xv = explode(":", $v);
            $code = trim($xv[0]);
            $type = trim($xv[1]);

            if (isset($ra[$code])) {
                $ids = $this->save_property_ref($code, "", $ra[$code], $general_context_id, $type);

                $pr_ids = [];
                foreach ($ids as $id) {
                    $nr = [];
                    $nr['context_id'] = $context_id;
                    $nr['property_id'] = $id;
                    $pr = $emps->db->sql_ensure_row("e_property_references", $nr);
                    if ($pr) {
                        $pr_ids[] = $pr['id'];
                    }
                }
                if (count($pr_ids) > 0) {
/*                    $pr_ids_txt = implode(",", $pr_ids);
                    $emps->db->query("delete from ".TP."e_property_references where context_id = {$context_id}
                    and code = '{$code}'
                    and id not in ({$pr_ids_txt})
                    ");*/
                }

            }
        }
    }

    public function read_properties_ref($row, $context_id) {
        global $emps;

        $r = $emps->db->query("select * from ".TP."e_property_references where context_id = {$context_id} order by id asc");
        while($ra = $emps->db->fetch_named($r)){
            $prop = $emps->db->get_row("e_properties", "id = {$ra['property_id']}");
            switch ($prop['type']) {
                case "i":
                case "r":
                    $value = $prop['v_int'];
                    $value = intval($value);
                    break;
                case "f":
                    $value = $prop['v_float'];
                    $value = floatval($value);
                    break;
                case "c":
                    $value = $prop['v_char'];
                    break;
                case "d":
                    $value = $prop['v_data'];
                    break;
                case "b":
                    $value = $prop['v_bool'];
                    break;
                default:
                    $value = $prop['v_text'];
            }
            $x = explode(".", $prop['code']);
            $subarray = &$row;
            $last_v = array_pop($x);
            foreach($x as $v){
                $subarray[$v] = [];
                $subarray = &$subarray[$v];
            }
            $subarray[$last_v] = $value;
        }
        $emps->db->free($r);
        return $row;
    }

    public function read_properties($row, $context_id)
    {
        global $emps;
        $r = $emps->db->query('select * from ' . TP . "e_properties where context_id=$context_id and status=0 order by idx asc");
        while ($ra = $emps->db->fetch_named($r)) {
            switch ($ra['type']) {
                case "i":
                case "r":
                    $value = $ra['v_int'];
                    if($this->wt){
                        $value = intval($value);
                    }
                    break;
                case "f":
                    $value = $ra['v_float'];
                    if($this->wt){
                        $value = floatval($value);
                    }
                    break;
                case "c":
                    $value = $ra['v_char'];
                    break;
                case "d":
                    $value = $ra['v_data'];
                    break;
                case "b":
                    $value = $ra['v_bool'];
                    break;
                default:
                    $value = $ra['v_text'];
            }
            $row[$ra['code']] = $value;
            if(!$this->no_idx){
                $row[$ra['code'] . '_idx'][$ra['idx']] = $value;
                if (!$row[$ra['code'] . '_count']) $row[$ra['code'] . '_count'] = 0;
                $row[$ra['code'] . '_count']++;
            }
            if(!$this->no_full){
                $row['_full'][$ra['code']] = $ra;
            }
        }
        $emps->db->free($r);
        return $row;
    }

    public function copy_properties($source_context_id, $target_context_id)
    {
        global $emps, $SET;

        $r = $emps->db->query("select * from " . TP . "e_properties where context_id = " . $source_context_id . " order by idx asc, dt asc");

        while ($ra = $emps->db->fetch_named($r)) {
            $SET = $ra;
            unset($SET['id']);
            $SET['context_id'] = $target_context_id;
            $code = $ra['code'];
            $idx = $ra['idx'];
            $row = $emps->db->get_row("e_properties", "context_id=$target_context_id and code='$code' and status=0 and idx=$idx");

            if (!$row) {
                $emps->db->sql_insert("e_properties");
            } else {
                $emps->db->sql_update("e_properties", "id = " . $row['id']);
            }
        }
    }

    public function read_history($property, $context_id)
    {
        global $emps;
        $r = $emps->db->query('select * from ' . TP . "e_properties where context_id=$context_id and code='$property' order by status asc, dt desc, id desc");
        $lst = array();
        while ($ra = $emps->db->fetch_named($r)) {
            switch ($ra['type']) {
                case "i":
                case "r":
                    $value = $ra['v_int'];
                    break;
                case "f":
                    $value = $ra['v_float'];
                    break;
                case "c":
                    $value = $ra['v_char'];
                    break;
                case "d":
                    $value = $ra['v_data'];
                    break;
                case "b":
                    $value = $ra['v_bool'];
                    break;
                default:
                    $value = $ra['v_text'];
            }
            $ra['value'] = $value;
            $ra['time'] = $emps->form_time($ra['dt']);
            $lst[] = $ra;
        }
        return $lst;
    }

    public function get_context($type, $sub, $ref_id)
    {
        global $emps;
        $type = intval($type);
        $sub = intval($sub);
        $ref_id = intval($ref_id);
        if (!$type || !$sub) {
            return 0;
        }
        if (($type != 1) && !$ref_id) {
            return 0;
        }

        if (isset($this->context_cache[$type][$sub][$ref_id])) {
            return $this->context_cache[$type][$sub][$ref_id];
        }
        $row = $emps->db->get_row("e_contexts", "ref_type = {$type} and ref_sub = {$sub} and ref_id = {$ref_id}");
        if (!$row) {
            if (!$this->default_ctx) {
                if (!(($type == 1) && ($sub == 1) && ($ref_id == 0))) {
                    $this->default_ctx = $this->get_context(1, 1, 0);
                }
            }
            $nr = [];
            $nr['id'] = '';
            $nr['ref_type'] = $type;
            $nr['ref_sub'] = $sub;
            $nr['ref_id'] = $ref_id;
            $emps->db->sql_insert_row('e_contexts', ['SET' => $nr]);
            $id = $emps->db->last_insert();
            $row = $emps->db->get_row('e_contexts', 'id = ' . $id);
        }
        $this->context_cache[$type][$sub][$ref_id] = $row['id'];
        return $row['id'];
    }

    public function load_context($context_id)
    {
        global $emps;

        if (isset($this->load_context_cache[$context_id])) {
            return $this->load_context_cache[$context_id];
        }

        $context = $emps->db->get_row("e_contexts", "id = {$context_id}");
        $this->load_context_cache[$context_id] = $context;
        return $context;
    }

    public function register_cleanup($call)
    {
        foreach ($this->cleanups as $v) {
            if (get_class($v[0]) == get_class($call[0])) {
                return false;
            }
        }
        $this->cleanups[] = $call;
        return true;
    }

    public function delete_context($context_id)
    {
        global $emps;
        $emps->db->query('delete from ' . TP . "e_properties where context_id=$context_id");
        $emps->db->query('delete from ' . TP . "e_posts_topics where context_id=$context_id");
        foreach ($this->cleanups as $v) {
            $callme = "";
            if (is_callable($v, false, $callme)) {
                $obj = $v[0];
                $method = $v[1];
                $obj->$method($context_id);
            }
        }
        $emps->db->query('delete from ' . TP . "e_contexts where id=$context_id");
    }

    public function handle_keywords($context_id, $kw)
    {
        global $emps;
        $ex = array();
        if (!isset($kw)) {
            return;
        }
        foreach ($kw as $n => $v) {
            $ptid = $this->ensure_post_topic_text($context_id, $v, $n);
            if ($ptid) {
                $pt = $emps->db->get_row("e_posts_topics", "id=$ptid");
                $ex[$pt['topic_id']] = 1;
            }
        }

        $r = $emps->db->query("select * from " . TP . "e_posts_topics where context_id=$context_id order by ord asc");
        while ($ra = $emps->db->fetch_named($r)) {
            if (!$ex[$ra['topic_id']]) {
                $emps->db->query("delete from " . TP . "e_posts_topics where context_id=$context_id and topic_id=" . $ra['topic_id']);
            }
        }
    }

    public function list_keywords($context_id)
    {
        global $emps;
        $lst = array();
        $r = $emps->db->query("select t1.*,t2.name from " . TP . "e_posts_topics as t1
			join " . TP . "e_topics as t2
			on t2.id=t1.topic_id
			where t1.context_id=$context_id order by t1.ord asc");
        while ($ra = $emps->db->fetch_named($r)) {
            $lst[] = $ra['name'];
        }
        return $lst;
    }

    public function list_keywords_ids($context_id)
    {
        global $emps;
        $lst = array();
        $ilst = array();
        $r = $emps->db->query("select t1.*,t2.name from " . TP . "e_posts_topics as t1
			join " . TP . "e_topics as t2
			on t2.id=t1.topic_id
			where t1.context_id=$context_id order by t1.ord asc");
        while ($ra = $emps->db->fetch_named($r)) {
            $lst[] = $ra['name'];
            $ilst[] = $ra['topic_id'];
        }
        return array('lst' => $lst, 'ilst' => $ilst);
    }

    public function delete_keywords($context_id)
    {
        global $emps;
        $emps->db->query("delete from " . TP . "e_posts_topics where context_id=$context_id");
    }

    public function ensure_topic($name)
    {
        global $SET, $emps;

        $name = trim($name);
        $topic = $emps->db->get_row("e_topics", "name='$name'");
        if (!$topic) {
            $SET = array();
            $SET['name'] = $name;
            $SET['user_id'] = $emps->auth->USER_ID;
            $emps->db->sql_insert("e_topics");
            $id = $emps->db->last_insert();
            $topic = $emps->db->get_row("e_topics", "id=$id");
        }
        return $topic;
    }

    public function ensure_post_topic_text($context_id, $text, $ord)
    {
        global $SET, $emps;
        if (!$context_id || !$text) return false;

        $topic = $this->ensure_topic($text);
        $topic_id = $topic['id'];

        $row = $emps->db->get_row("e_posts_topics", "context_id=$context_id and topic_id=$topic_id");
        if (!$row) {
            $SET = array();
            $_REQUEST = array();
            $_REQUEST['context_id'] = $context_id;
            $_REQUEST['topic_id'] = $topic_id;
            $_REQUEST['ord'] = $ord;
            $emps->db->sql_insert("e_posts_topics");
            return $emps->db->last_insert();
        } else {
            return $row['id'];
        }
    }

    public function prepare_idx_array($ra, $vars)
    {
        $x = explode(",", $vars);
        $va = array();
        foreach ($x as $v) {
            $va[$v] = 1;
        }

        foreach ($ra as $n => $v) {
            if ($va[$n]) {
                if (is_array($v)) {
                    $ra[$n . '_idx'] = $v;
                }
            }
        }

        return $ra;
    }

    public function save_cache($context_id, $code, $data)
    {
        global $emps, $SET;

        $SET = array();
        $SET['data'] = serialize($data);
        $SET['dt'] = time();
        $ex = $emps->db->get_row("e_cache", "context_id = $context_id and code = '$code'");
        if ($ex) {
            $emps->db->sql_update("e_cache", "id = " . $ex['id']);
        } else {
            $SET['code'] = $code;
            $SET['context_id'] = $context_id;
            $emps->db->sql_insert("e_cache");
        }
    }

    public function read_cache($context_id, $code)
    {
        global $emps;

        $ex = $emps->db->get_row("e_cache", "context_id = $context_id and code = '$code'");
        if ($ex) {
            $ex['data'] = unserialize($ex['data']);
            return $ex;
        }
        return false;
    }

}

