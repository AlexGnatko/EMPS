<?php

if($emps->auth->credentials("admin")){
	$perpage = 25;
	$start = intval($start);
	
	$r = $emps->db->query("select SQL_CALC_FOUND_ROWS * from ".TP."e_track_events order by id desc limit $start, $perpage");
	
	$smarty->assign("pages", $emps->count_pages($emps->db->found_rows()));
	
	$lst = [];
	
	while($ra = $emps->db->fetch_named($r)){
		$ra['user'] = $emps->auth->load_user($ra['user_id']);
		$ra['time'] = $emps->form_time($ra['cdt']);
		$lst[] = $ra;
	}
	
	$smarty->assign("lst", $lst);
	
}else{
	$emps->deny_access("AdminNeeded", 1);
}

