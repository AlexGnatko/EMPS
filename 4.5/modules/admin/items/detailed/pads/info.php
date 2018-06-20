<?php

$this->handle_view_row();

$this->handle_post();

$item_id = $this->ref_id;

if($_POST){
	$savenodes = array();
	foreach($_POST['savenode'] as $n => $v){
		if($v){
			$savenodes[$v]=true;
		}
	}
    foreach($_POST['newnode'] as $n => $v){
		if($v){
			$savenodes[$v]=true;
		}
	}		
	
	$this->items->update_nodes($item_id, $savenodes);
}

