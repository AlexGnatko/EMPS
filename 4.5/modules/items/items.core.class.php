<?php

emps_define_constant('DT_WS_ITEM', 3010);
emps_define_constant('DT_WS_STRUCTURE', 3020);

emps_define_constant("P_WS_ITEM", "html:t,search_words:t,meta_descr:t,meta_keywords:t");
emps_define_constant("P_WS_STRUCTURE", "html:t,meta_descr:t,meta_keywords:t");

require_once $emps->common_module('photos/photos.class.php');

class EMPS_Items_Base
{
    public $p;

    public $pages;

    public $total;

    public $pp;

    public $list_order = " order by i.ord asc, i.name asc, i.cdt desc ";
    public $list_join = "";
    public $list_what = "i.*";
    public $list_where = " and i.pub = 10 ";
    public $list_group = "";
    public $list_having = "";

    public $structure_where = "";

    public $default_pp = "catalog";
    public $node_group = "__catalog";

    public $table_name = "ws_items";
    public $structure_table_name = "ws_structure";
    public $link_table_name = "ws_items_structure";

    public $country_code;

    public $explain_list_nodes = false;

    public $dt_item = DT_WS_ITEM;
    public $dt_structure = DT_WS_STRUCTURE;
    public $p_item = P_WS_ITEM;
    public $p_structure = P_WS_STRUCTURE;

    public function __construct()
    {
        $this->p = new EMPS_Photos;
        $this->node_pp = $this->default_pp;
    }

    public function list_node_parents($id)
    {
        $lst = array();
        $row = $this->node_by_url($id);
        if ($row) {
            if ($row['pub'] == 10) {
                $lst[] = $row;
            }
            if ($row['parent'] > 0) {
                $lst = array_merge($this->list_node_parents($row['parent']), $lst);
            }
            return $lst;
        }
        return array();
    }

    public function item_by_url($url)
    {
        $x = explode('-', $url, 2);
        $item_id = $x[0];

        return $this->load_item($item_id);
    }

    public function node_by_url($url)
    {
        global $emps;

        if (intval($url) > 0) {
            $url = intval($url);
            $node = $emps->db->get_row($this->structure_table_name, "id=$url");
            if ($node) {
                $node = $this->explain_structure_node($node);
                return $node;
            }
        } else {

            $url = $emps->db->sql_escape($url);

            $node = $emps->db->get_row($this->structure_table_name, "url='$url'");
            if ($node) {
                $node = $this->explain_structure_node($node);
                return $node;
            }
        }
        return false;
    }

    public function load_item($item_id)
    {
        global $emps;

        $item_id = intval($item_id);
        $item = $emps->db->get_row($this->table_name, "id = {$item_id}");
        if ($item) {
            $item = $this->explain_item($item);
            return $item;
        }
        return false;
    }


    public function load_node($item_id)
    {
        global $emps;

        $item_id = intval($item_id);
        $item = $emps->db->get_row($this->structure_table_name, "id = {$item_id}");
        if ($item) {
            $item = $this->explain_structure_node($item);
            return $item;
        }
        return false;
    }

    public function get_node_top_code($node_id)
    {
        global $emps;
        $node_id = intval($node_id);
        if ($node_id) {
            $node = $emps->db->get_row($this->structure_table_name, "id = {$node_id}");
        }
        if ($node) {
            if (substr($node['url'], 0, 2) == '__') {
                return $node['url'];
            } else {
                return $this->get_node_top_code($node['parent']);
            }
        }
        return false;
    }

    public function explain_item_base($ra){
        global $emps, $pp, $key, $ss, $start;

        $use_key = false;

        $ctx = $emps->p->get_context($this->dt_item, 1, $ra['id']);
        $ra['ctx'] = $ctx;
        $ra = $emps->p->read_properties($ra, $ctx);
        $pics = $this->p->list_pics($ctx, 1000);
        $ra['pic'] = $pics[0];
        $ra['pics'] = $pics;

        $ra['time'] = $emps->form_time($ra['cdt']);

        $emps->loadvars();

        $nodes = $this->list_nodes($ra['id']);

        foreach($nodes as $node){
            $top_code = $this->get_node_top_code($node['id']);
            if($top_code == $this->node_group){
                $node = $this->explain_structure_node($node);

                if($node['url']){
                    $url = '-'.$node['url'];
                }else{
                    $url = '-'.$emps->transliterate_url($node['name']);
                }
                $use_key = $node['id'].$url;
                $ra['node_id'] = $node['id'];
            }
        }

        $ra['nodes'] = $nodes;

        $emps->loadvars();

        if($use_key){
            $key = $use_key;
        }

        if($ra['url']){
            $url = '-'.$ra['url'];
        }else{
            $url = '-'.$emps->transliterate_url($ra['name']);
        }
        $ss = $ra['id'].$url;
        if($this->pp){
            $pp = $this->pp;
        }else{
            $pp = $this->default_pp;
        }
        $ra['use_ss'] = $ss;

        $start = "";
        $ra['elink'] = $emps->elink();
        $ra['xelink'] = $ra['elink'];

        $emps->loadvars();

        $ra['kw'] = $emps->p->list_keywords($ra['ctx']);

        return $ra;
    }

    public function explain_item($ra){
        return $this->explain_item_base($ra);
    }

    public function ensure_item_in_node($item_id, $node_id){
        global $emps;

        if(!$node_id){
            return false;
        }
        $item_id = intval($item_id);
        $node_id = intval($node_id);

        $str = $emps->db->get_row($this->structure_table_name, "id = {$node_id}");
        if($str){
            $row = $emps->db->get_row($this->link_table_name, "item_id = {$item_id} and structure_id = {$node_id}");
            if(!$row){
                $update = ['SET' => ['item_id' => $item_id, 'structure_id' => $node_id]];
                $emps->db->sql_insert_row($this->link_table_name, $update);
            }
        }
    }

    public function list_nodes($item_id){
        global $emps;


        $r = $emps->db->query("select node.* from ".TP.$this->link_table_name." as itst
					join ".TP.$this->structure_table_name." as node
					on node.id = itst.structure_id
					and itst.item_id = {$item_id}
					order by node.full_id desc");
        $lst = [];

        while($ra = $emps->db->fetch_named($r)){
            $ra['level'] = (strlen($ra['full_id']) / 4) - 1;
            if($this->explain_list_nodes){
                $ra = $this->explain_structure_node($ra);
            }
            $lst[] = $ra;
        }

        $emps->db->free($r);

        return $lst;
    }

    public function list_nodes_ex($item_id, $all){
        return $this->list_nodes($item_id);
    }

    public function remove_item_from_node($item_id, $node_id){
        global $emps;

        $emps->db->query("delete from ".TP.$this->link_table_name." where (item_id = {$item_id} 
            and structure_id = {$node_id}) or structure_id = 0");

    }

    public function update_nodes($item_id,$nodes){
        $lst = $this->list_nodes($item_id);
        foreach($lst as $v){
            if($nodes[$v['id']]){
                unset($nodes[$v['id']]);
            }else{
                $this->remove_item_from_node($item_id, $v['id']);
            }
        }

        foreach($nodes as $n => $v){
            $this->ensure_item_in_node($item_id, $n);
        }
    }

    public function list_items($limit){
        global $emps, $start, $perpage;

        $perpage = $limit;
        $order = "";
        if($this->list_order){
            $order = $this->list_order;
        }

        $join = "";
        if($this->list_join){
            $join = $this->list_join;
        }

        $where = "";
        if($this->list_where){
            $where = $this->list_where;
        }

        $group = "";
        if($this->list_group){
            $group = $this->list_group;
        }

        $having = "";
        if($this->list_having){
            $having = $this->list_having;
        }

        $lst = [];
        $start = intval($start);

        $query="select SQL_CALC_FOUND_ROWS ".$this->list_what." from ".TP.$this->table_name." 
            as i {$join} where 1=1 {$where} {$group} {$having} {$order} limit {$start}, {$limit}";

        $r = $emps->db->query($query);

        $this->total = $emps->db->found_rows();
        $this->pages = $emps->count_pages($this->total);

        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->explain_item($ra);
            $lst[] = $ra;
        }

        $emps->db->free($r);
        return $lst;
    }

    function encode_id($id){
        $hash = array(6,121,117,7,23,51,29,919);
        $xid = sprintf("%04d",$id);
        $id = strval($id);
        $l = strlen($id);
        $s = 0;
        for($i = 0; $i < $l; $i++){
            $c = $id{$i};
            $v = $hash[$i]*ord($c);
            $s += $v;
        }

        $s = $s % 97;
        $id = sprintf("%02d",$s) . "-" . $xid;
        return $id;
    }

    function update_item($item_id){
        global $emps;
        $emps->db->sql_update($this->table_name, "id = {$item_id}");
        $ctx = $emps->p->get_context($this->dt_item, 1, $item_id);
        $emps->p->save_properties($_REQUEST, $ctx, $this->p_item);
    }

    function delete_item($item_id){
        global $emps;
        $ctx = $emps->p->get_context($this->dt_item, 1, $item_id);
        $emps->p->delete_context($ctx);
        $emps->db->query("delete from ".TP.$this->table_name." where id = {$item_id}");
        $emps->db->query("delete from ".TP.$this->link_table_name." where item_id = {$item_id}");
    }

    public function explain_structure_node($ra){
        global $emps, $pp, $key, $ss;

        $context_id = $emps->p->get_context($this->dt_structure, 1, $ra['id']);
        $ra['ctx'] = $context_id;

        $ra = $emps->p->read_properties($ra, $context_id);

        $ra['pics'] = $this->p->list_pics($context_id, 1000);
        if(count($ra['pics']) > 0){
            $ra['pic'] = $ra['pics'][0];
        }

        $emps->clearvars();

        $pp = $this->node_pp;

        if($ra['url']){
            $text = $emps->transliterate_url($ra['url']);
            $url = $ra['id']."-".$text;
        }else{
            $url = $ra['id']."-".$emps->transliterate_url($ra['name']);
        }
        $key = $url;
        $ss = "";
        $ra['key_url'] = $url;

        $ra['link'] = $emps->elink();
        $emps->loadvars();

        return $ra;
    }

    public function list_structure($parent, $sel){
        global $emps;

        $parent = intval($parent);
        $r = $emps->db->query("select * from ".TP.$this->structure_table_name." where pub=10 
            and parent = {$parent} {$this->structure_where} order by ord asc, name asc");

        $lst = [];
        while($ra = $emps->db->fetch_named($r)){
            unset($ra['full_id']);
            $ra = $this->explain_structure_node($ra);
            if($ra['id'] == $sel){
                $ra['sel'] = true;
            }
            if(!$this->no_subs){
                $ra['subs'] = $this->list_structure($ra['id'], $sel);
            }

            foreach($ra['subs'] as $v){
                if($v['sel']){
                    $ra['sel'] = true;
                }
            }
            $lst[] = $ra;
        }
        return $lst;
    }

    public function list_child_nodes_self($node_id){
        global $emps;

        $lst = $node_id;
        $r = $emps->db->query("select * from ".TP.$this->structure_table_name." where parent = {$node_id}");
        while($ra = $emps->db->fetch_named($r)){
            $lst .= ','.$this->list_child_nodes_self($ra['id']);
        }
        return $lst;
    }

    public function list_child_nodes($node_id){
        global $emps;

        $lst = [];
        $r = $emps->db->query("select * from ".TP.$this->structure_table_name." where parent = {$node_id} and pub > 0");
        while($ra = $emps->db->fetch_named($r)){
            $ra = $this->explain_structure_node($ra);
            $lst[] = $ra;
        }
        return $lst;
    }

    public function recount_items_in_list($lst){
        global $emps, $start;

        foreach($lst as $v){
            if($v['subs']){
                $this->recount_items_in_list($v['subs']);
            }
            $nodes = $this->list_child_nodes_self($v['id']);
            $this->list_join = " join ".TP.$this->link_table_name." as wis on wis.item_id=i.id
			 join ".TP.$this->structure_table_name." as str
			 on str.id=wis.structure_id and str.id in ({$nodes})";
            $this->list_order = " order by str.ord asc, i.cdt asc";

            $start = intval($start);
            $this->list_items(9);
            $count = $this->total;

            $emps->db->query("update ".TP.$this->structure_table_name." set qty = {$count} where id = {$v['id']}");
        }
    }

    public function recount_items(){
        global $emps;

        $node = $emps->db->get_row($this->structure_table_name, "url = '{$this->node_group}'");
        if(!$node){
            return false;
        }

        $shop_node_id = intval($node['id']);

        $node_id = 0;

        $this->list_where = " and i.pub=10 ";

        $nlst = $this->list_structure($shop_node_id,$node_id);
        $this->recount_items_in_list($nlst);
    }

}