<?php

global $SET;

$node=$_REQUEST['node']+0;

if($_POST['post_save']){
	require_once($emps->common_module('photos/uploader.class.php'));

	$emps->all_post_required();	
	$smarty->assign("SinkMode",1);
	$emps->db->sql_update($this->structure_table_name,"id=".$node);

	$ctx=$emps->p->get_context($this->ref_structure_type, 1, $node);
	
	$emps->p->save_properties($_POST,$ctx, $this->track_structure_props);

	if(isset($_FILES)){
		$small=$_FILES['file_0'];
		if(isset($small)){
			if(!$small['error']){
			
				$photos = new EMPS_PhotosUploader;
						

				
				$photos->p->delete_photos_context($ctx);
				
				$photos->photo_size="1200x1200|100x100|inner";
				$photos->no_post_redirect = true;
				$od=$_REQUEST['descr'];
				$_REQUEST['descr'] = "main";
				unset($_REQUEST['type']);
				unset($SET);				
			
				$photos->handle_request($ctx);
				$_REQUEST['descr']=$od;	


			}
		}
	}	

}



$node=$emps->db->get_row($this->structure_table_name,"id=$node");
if($node){
	$smarty->assign("elink",$emps->clink('node='.$node['id']));
	
	$node = $this->items->explain_structure_node($node);
	$smarty->assign("row",$node);
	$smarty->display($this->ajax_template('detail-node','view'));
}else{
	echo "Error! Node not found by ID.";
}

