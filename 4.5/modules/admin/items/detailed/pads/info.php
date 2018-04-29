<?php

$this->handle_view_row();

$this->handle_post();

$item_id = $this->ref_id;

if($_POST){
	$savenodes=array();
	while(list($n,$v)=each($_POST['savenode'])){
		if($v){
			$savenodes[$v]=true;
		}
	}
	while(list($n,$v)=each($_POST['newnode'])){
		if($v){
			$savenodes[$v]=true;
		}
	}		
	
	$this->items->update_nodes($item_id,$savenodes);	
}

