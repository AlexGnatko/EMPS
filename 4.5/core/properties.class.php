<?php

class EMPS_Properties
{
    public $db;
    private $context_cache = array();

    private $cleanups = array();

    public $default_ctx = false;

    public $no_full = false;
    public $no_idx = false;

    public $wt = false;

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
                    $emps->db->query('update ' . TP . "e_properties set status=1 where context_id=$context_id and code='$code'");
                }
                $lst = "";
                if (!is_array($value)) {
                    $pv = $value;
                    $value = array();
                    $value[] = $pv;
                }
                $idx = 0;
                reset($value);
                $vtaken = "0";
                while (list($nn, $vv) = each($value)) {
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
                $emps->db->query('delete from ' . TP . "e_properties where context_id=$context_id and code='$code' and status=0 and (not (idx in ($vtaken)))");
            }
        }
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

        $context = $emps->db->get_row("e_contexts", "id = " . $context_id);
        return $context;
    }

    public function register_cleanup($call)
    {
        reset($this->cleanups);
        while (list($n, $v) = each($this->cleanups)) {
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
        reset($this->cleanups);
        while (list($n, $v) = each($this->cleanups)) {
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
        if (!isset($kw)) return;
        while (list($n, $v) = each($kw)) {
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
        while (list($n, $v) = each($x)) {
            $va[$v] = 1;
        }

        reset($ra);
        while (list($n, $v) = each($ra)) {
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

