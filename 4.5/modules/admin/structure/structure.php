<?php
$emps->page_property("autoarray",1);

if($emps->auth->credentials("admin")):

require_once $emps->common_module('ited/ited.class.php') ;
require_once $emps->page_file_name('_items,items.class','controller');

$emps->page_property("adminpage",1);

class EMPS_ItemsEditor extends EMPS_ImprovedTableEditor {
	public $ref_type = DT_WS_STRUCTURE;
	public $ref_sub = 1;

	public $track_props = P_WS_STRUCTURE;

    public $items_table_name = "ws_items";
    public $table_name = "ws_structure";
    public $link_table_name = "ws_items_structure";
	
	public $credentials = "admin";
	
	public $form_name = "db:_admin/structure,form";
	public $pad_template = "admin/structure/pads,%s";
	
	public $order = " order by ord desc, name asc ";

	public $items_editor_pp = "admin-items-detailed";

	public $filt_varname = "structure_filt";
    public $search_varname = "structure_search";

	public $default_link_pp = "toner";
	
	public $pads = array(
		'info'=>'Общие сведения',
		'html'=>'Подробный текст',
		'objects'=>'Объекты',
		'photos'=>'Фотографии',
		);
		
	public $p;
		
	public function __construct(){
        global $items;
        if(!isset($items)){
            $items = new EMPS_Items();
        }
        $this->items = $items;
        $this->items->pp = $this->default_link_pp;
        $this->p = new EMPS_Photos;

        $this->set_tables();
        parent::__construct();
	}

    public function set_tables(){
        if(isset($this->items->table_name)){
            $this->items_table_name = $this->items->table_name;
        }
        if(isset($this->items->structure_table_name)){
            $this->table_name = $this->items->structure_table_name;
        }
        if(isset($this->items->link_table_name)){
            $this->link_table_name = $this->items->link_table_name;
        }
        if(isset($this->items->dt_item)){
            $this->ref_item_type = $this->items->dt_item;
        }
        if(isset($this->items->dt_structure)){
            $this->ref_type = $this->items->dt_structure;
        }
        if(isset($this->items->p_item)){
            $this->track_item_props = $this->items->p_item;
        }
        if(isset($this->items->p_structure)){
            $this->track_props = $this->items->p_structure;
        }
    }

    public function pre_handler(){
	    global $emps, $smarty, $key, $perpage;

        $this->what = " d.* ";
        $this->join = " as d ";
        $this->where = " where 1=1 ";

        if($_GET['clear_search']){
            unset($_SESSION[$this->search_varname]);
            $emps->redirect_elink(); exit;
        }

        if($_POST['post_filt']){
            $_SESSION[$this->filt_varname] = $_POST['filt'];
            $emps->redirect_elink(); exit;
        }

        if($_POST['post_search']){
            $_SESSION[$this->search_varname] = $_POST['search'];
            $emps->redirect_elink(); exit;
        }

        if($_SESSION[$this->search_varname]){
            $search = $_SESSION[$this->search_varname];
            $search = trim($search);

            $smarty->assign("search", $search);

            $txt = $search;

            if($txt){
                $ptxt = "%".$emps->db->sql_escape("".str_replace(" ", "%", str_replace("-", "%", $txt))."")."%";
                $rtxt = $emps->db->sql_escape("+".str_replace(" ", " +", $txt)."");

                $this->what = " d.*, sum((match(d.name) against ('$rtxt' in boolean mode))) as name_rel, sum((match(p.v_char,p.v_text,p.v_data) against ('$rtxt' in boolean mode))) as rel, sum((d.name like ('$ptxt'))) as namel, ctx.ref_type as ref_type, ctx.ref_id as ref_id ";
                $this->join = " as d
     left join ".TP."e_contexts as ctx
     on (ctx.ref_id  = d.id and ctx.ref_type = ".$this->ref_type.")
     left join ".TP."e_properties as p
     on p.context_id = ctx.id ";

                $this->group = " group by d.id ";

                $this->having = " having name_rel>0 or rel>0 or namel>0 ";

                $this->order = " order by name_rel desc, rel desc, namel desc ";
            }
        }

        if($_SESSION[$this->filt_varname]){
            $filt = $_SESSION[$this->filt_varname];
            $smarty->assign("filt", $filt);
        }

        if($key){
            unset($_SESSION[$this->search_varname]);
        }

        if($filt['category']){
            $id = intval($filt['category']);
            $this->where .= " and d.parent = {$id} ";
        }


        $emps->uses_flash();

        $this->ref_id = $key;

        $this->add_pad_template($this->pad_template);

        $emps->page_property('calendar',1);
        $emps->page_property('lightbox',1);

        if($_POST['time']){
            $_REQUEST['cdt'] = $emps->parse_time($_REQUEST['time']);
        }

        $smarty->assign("structure_table", $this->table_name);
        $smarty->assign("item_table", $this->items_table_name);


        $perpage = 50;

    }

    public function handle_row($ra){
		global $items;
		
		$ra = $items->explain_structure_node($ra);
		
		return parent::handle_row($ra);
	}
};



if(!$no_handler) {
    $ited = new EMPS_ItemsEditor;

    $ited->pre_handler();
    $ited->handle_request();
};

else:
	$emps->deny_access("AdminNeeded");
endif;
