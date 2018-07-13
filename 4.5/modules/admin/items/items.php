<?php
$emps->page_property("tinymce",1);
$emps->page_property("calendar",1);
$emps->page_property("treeview",1);
$emps->page_property("ipbox",1);

require_once $emps->common_module('ited/ited.class.php');
require_once $emps->page_file_name('_items,items.class','controller');

class EMPS_ItemsEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_WS_ITEM;
	public $ref_structure_type = DT_WS_STRUCTURE;
	public $ref_sub = 1;

	public $track_props = P_WS_ITEM;
    public $track_structure_props = P_WS_STRUCTURE;

	public $table_name = "ws_items";
	public $structure_table_name = "ws_structure";
	public $link_table_name = "ws_items_structure";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/items,form";
	public $ajax_template = "admin/items/ajax,%s";
	public $item_form = "db:_items,item_form";
	
	public $order = " order by cdt asc, id asc ";
	
	public $immediate_add = true;
	
	public $items;

	public $new_item = "Новый товар №";
	public $photo_size = "1920x1920|100x100|inner";

	public $full_structure_pp = "admin-structure";
    public $full_items_pp = "admin-items-detailed";

	public $p;
		
	public function __construct(){
	    global $items;
	    if(!isset($items)){
            $items = new EMPS_Items();
        }
        $this->items = $items;
		$this->p = new EMPS_Photos;

		$this->set_tables();
		parent::__construct();
	}

	public function set_tables(){
        if(isset($this->items->table_name)){
            $this->table_name = $this->items->table_name;
        }
        if(isset($this->items->structure_table_name)){
            $this->structure_table_name = $this->items->structure_table_name;
        }
        if(isset($this->items->link_table_name)){
            $this->link_table_name = $this->items->link_table_name;
        }
        if(isset($this->items->dt_item)){
            $this->ref_type = $this->items->dt_item;
        }
        if(isset($this->items->dt_structure)){
            $this->ref_structure_type = $this->items->dt_structure;
        }
        if(isset($this->items->p_item)){
            $this->track_props = $this->items->p_item;
        }
        if(isset($this->items->p_structure)){
            $this->track_structure_props = $this->items->p_structure;
        }
    }

    public function with_items($items){
        $instance = new self();
        $instance->items = $items;
        $instance->set_tables();
        return $instance;
    }
		
	public function handle_row($ra){
		global $emps,$ss,$key;
		
		$emps->clearvars();
		$ss="info";
	
		$emps->loadvars();
		$key=$ra['id'];
		$ss="info";
		$ra['nlink']=$emps->elink();	
		$emps->loadvars();	
		
		$ra=$this->items->explain_item($ra);
		
		return parent::handle_row($ra);
	}
	
	public function handle_filter($code){
		global $emps,$smarty,$pp,$key,$ss;
		$x=explode(',',$code);
		foreach($x as $v){
			$xx = explode('-',$v);
			$this->filter[$xx[0]]=$xx[1];
		}
		if($this->filter['node']){
			$node_id=$this->filter['node']+0;
			$node=$emps->db->get_row($this->structure_table_name, "id = {$node_id}");
			if($node){
				$emps->clearvars();
				$pp = $this->structure_table_name; $key = $node['id']; $ss = "info";
				$node['ilink']=$emps->elink();
				$emps->loadvars();
				$smarty->assign("node",$node);
				$_REQUEST['node_id']=$node_id;
				$this->where.=' and node_id='.$node_id;
			}
		}
	}
	
	public function handle_request(){
		global $smarty, $sk, $emps;

		$smarty->assign("structure_table", $this->structure_table_name);
        $smarty->assign("item_table", $this->table_name);
		
		$this->where=' where 1=1 ';
		
		$emps->loadvars();
		
		if($sk){
			$this->handle_filter($sk);
		}
		
		if($_GET['search']){
			$smarty->assign("search",$_GET['search']);
			$this->where.=" and name like ('%".$_GET['search']."%') ";
		}		

		parent::handle_request();
	}
	
	public function handle_redirect(){
		global $ss;
		if($_POST['post_save_return']){
			$ss="";
			
		}
		
		parent::handle_redirect();
	}

	public function pre_handler(){
	    global $smarty, $emps, $perpage, $pp;

        $this->ref_id = intval($_REQUEST['item']);

        $node_id = intval($_GET['node']);
        $smarty->assign("node_id", $node_id);

        $item_id = intval($_GET['item']);
        $smarty->assign("item_id", $item_id);

        $nlst = array();
        while(true){
            $node = $emps->db->get_row($this->structure_table_name, "id = {$node_id}");
            $nlst[] = $node;
            if(!$node['parent']){
                break;
            }
            $node_id = $node['parent'];
        }

        $nlst = array_reverse($nlst);
        $smarty->assign("nlst", $nlst);

        $this->add_ajax_template($this->ajax_template);

        if($_POST['time']){
            $_REQUEST['cdt'] = $emps->parse_time($_REQUEST['time']);
        }

        $smarty->assign("default_pp", $pp);
        $smarty->assign("full_structure_pp", $this->full_structure_pp);
        $smarty->assign("full_items_pp", $this->full_items_pp);
        $smarty->assign("item_form", $this->item_form);
        $perpage = 50;

    }
}

if(!$no_handler){
    $ited = new EMPS_ItemsEditor;
    $ited->pre_handler();
    $ited->handle_request();
}
