<?php

class EMPS_Categories {
    public $table_items = "ws_items";
    public $table_struct = "ws_categories";
    public $table_link = "ws_items_categories";

    public $dt_item = 0;
    public $dt_structure = 0;
    public $p_item = "";
    public $p_structure = "";

    public $explain_list_nodes = false;
    public $tag_list_nodes = false;

    public $and_struct = "";
    public $and_item = "";

    public function ensure_item_in_node($item_id, $node_id)
    {
        global $emps;

        if(!$node_id){
            return false;
        }
        $item_id = intval($item_id);
        $node_id = intval($node_id);

        $str = $emps->db->get_row($this->table_struct, "id = {$node_id}");
        if($str){
            $row = $emps->db->get_row($this->table_link, "item_id = {$item_id} and struct_id = {$node_id}");
            if(!$row){
                $update = ['SET' => ['item_id' => $item_id, 'struct_id' => $node_id]];
                $emps->db->sql_insert_row($this->table_link, $update);
                return $emps->db->last_insert();
            } else {
                return $row['id'];
            }
        }
    }

    public function explain_structure_node($ra) {
        unset($ra['full_id']);

        return $ra;
    }

    public function list_structure($parent_id) {
        global $emps;

        $r = $emps->db->query("select * from ".TP.$this->table_struct." where pub = 10
                                and parent = {$parent_id} order by ord asc, name asc, id asc");

        $lst = [];

        while ($ra = $emps->db->fetch_named($r)) {
            $ra = $this->explain_structure_node($ra);
            $lst[] = $ra;
        }

        return $lst;
    }

    public function tag_structure_node($ra) {
        if ($ra['parent'] != 0) {
            $parent = $this->load_node($ra['parent']);
        }

        $ra['tags'] = [];
        if (trim($ra['code']) != "") {
            $ra['tags'][$ra['code']] = true;
        }
        if ($parent['tags']) {
            $ra['tags'] = array_merge($ra['tags'], $parent['tags']);
        }

        return $ra;
    }


    public function list_nodes($item_id)
    {
        global $emps;

        $r = $emps->db->query("select node.*, itst.id as link_id from ".TP.$this->table_link." as itst
					join ".TP.$this->table_struct." as node
					on node.id = itst.struct_id
					and itst.item_id = {$item_id}
					order by node.full_id desc");
        $lst = [];

        while($ra = $emps->db->fetch_named($r)){
            $ra['level'] = (strlen($ra['full_id']) / 4) - 1;
            if($this->explain_list_nodes){
                $ra = $this->explain_structure_node($ra);
            }
            if($this->tag_list_nodes) {
                $ra = $this->tag_structure_node($ra);
            }

            unset($ra['full_id']);
            $lst[] = $ra;
        }

        $emps->db->free($r);

        return $lst;
    }


    public function remove_item_from_node($item_id, $node_id){
        global $emps;

        $emps->db->query("delete from ".TP.$this->table_link." where (item_id = {$item_id} 
            and struct_id = {$node_id}) or struct_id = 0");

    }

    public function list_child_nodes_self($q){
        global $emps;

        $lst = $q['parent'];

        $where = $emps->db->where_clause($q);

        $r = $emps->db->query("select * from ".TP.$this->table_struct." where {$where}");
        while($ra = $emps->db->fetch_named($r)){
            $sq = $q;
            $sq['parent'] = $ra['id'];
            $lst .= ','.$this->list_child_nodes_self($sq);
        }
        return $lst;
    }

    public function list_child_nodes($node_id){
        global $emps;

        $lst = [];
        $r = $emps->db->query("select * from ".TP.$this->table_struct." where parent = {$node_id} and pub > 0 order by ord asc");
        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->explain_structure_node($ra);
            $lst[] = $ra;
        }
        return $lst;
    }

    public function load_node($id) {
        global $emps;

        $node = $emps->db->get_row($this->table_struct, "id = {$id}");
        if ($node) {
            $node = $this->explain_structure_node($node);
//            $node = $this->tag_structure_node($node);
            return $node;
        }
        return false;
    }

    public function load_item($id) {
        global $emps;

        $item = $emps->db->get_row($this->table_items, "id = {$id}");
        if ($item) {
            $item = $this->explain_item($item);
            return $item;
        }
        return false;
    }

    public function node_by_code($code) {
        global $emps;

        $code = $emps->db->sql_escape($code);

        $node = $emps->db->get_row($this->table_struct, "code = '{$code}'");
        if ($node) {
            $node = $this->explain_structure_node($node);
            return $node;
        }
    }

    public function count_items_in_node($node_id) {
        global $emps;

        $lst = $this->list_child_nodes_self(['parent' => $node_id]);

        $q = "select count(distinct l.item_id) 
              from ".TP.$this->table_link." as l
              join ".TP.$this->table_struct." as s
              on l.struct_id in ({$lst})
              and s.id = l.struct_id
              {$this->and_struct}
              join ".TP.$this->table_items." as i
              on i.id = l.item_id
              {$this->and_item}
                ";
        $this->last_count_query = $q;

        $r = $emps->db->query($q);
        $ra = $emps->db->fetch_row($r);

        return $ra[0];
    }

    public function list_parents($row) {
        if ($row['parent']) {
            $parent = $this->load_node($row['parent']);
            $parents = $this->list_parents($parent);
            if (!$parents) {
                return [$parent];
            } else {
                array_unshift($parents, $parent);
                return $parents;
            }
        } else {
            return false;
        }

    }

    public function update_nodes($item_id, $nodes){
        $lst = $this->list_nodes($item_id);
        foreach ($lst as $v) {
            if($nodes[$v['id']]){
                unset($nodes[$v['id']]);
            }else{
                $this->remove_item_from_node($item_id, $v['id']);
            }
        }

        foreach ($nodes as $n => $v) {
            $this->ensure_item_in_node($item_id,$n);
        }
    }

    public function get_node_top_code_ex($node_id, $level)
    {
        global $emps;
        if ($level > 10) {
            return false;
        }
        $node_id = intval($node_id);
        if ($node_id) {
            $node = $emps->db->get_row($this->table_struct, "id = " . $node_id);
        }
        if ($node) {
            if (substr($node['code'], 0, 2) == '__') {
                return $node['code'];
            } else {
                return $this->get_node_top_code_ex($node['parent'], $level + 1);
            }
        }
        return false;
    }

    public function get_node_top_code($node_id)
    {
        return $this->get_node_top_code_ex($node_id, 0);
    }


}