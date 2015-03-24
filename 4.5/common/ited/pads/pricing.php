<?php

if($_POST['post_values']){
	while(list($n,$v)=each($_POST['take'])){
		$acc_id=$n+0;
		$acc=$emps->db->get_row("tsc_accounts","id=$acc_id");
		if($acc){
			$tsc->enter_cost($this->context_id,$acc['code'],$_POST['units'][$n],$_POST['amount'][$n]);
		}
	}
	$emps->redirect_elink();exit();
}

$x=explode(',',$accounts);

$lst=array();

while(list($n,$v)=each($x)){
	$acc=trim($v);
	
	$acc=$emps->db->get_row("tsc_accounts","code='$acc'");
	
	if($acc){
		$row=$tsc->read_cost($this->context_id,$reference_id,$acc['code']);
		if($row){
			$acc=array_merge($row,$acc);
		}
		$lst[]=$acc;
	}
}

$smarty->assign("lst",$lst);

?>