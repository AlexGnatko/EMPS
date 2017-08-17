<?php

if($emps->auth->credentials("admin")){

    $r = $emps->db->query("select * from ".TP."e_sessions order by dt desc");
    $lst = [];
    while($ra = $emps->db->fetch_named($r)){
        $ra['user'] = $emps->auth->load_user($ra['user_id']);
        $ra['browser'] = $emps->db->get_row("e_browsers", "id = ".$ra['browser_id']);
        $lst[] = $ra;
    }

    $smarty->assign("lst", $lst);
}else{
    $emps->deny_access("AdminNeeded");
}