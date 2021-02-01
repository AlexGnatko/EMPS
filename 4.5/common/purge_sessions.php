<?php

$emps->no_smarty = true;

$last_purge = intval($emps->get_setting("_last_session_purge"));

if ($last_purge < (time() - 15 * 60)) {
    $emps->save_setting("_last_session_purge", time());

    $dt = time() - 7 * 24 * 60 * 60;
    $emps->db->query("delete from " . TP . "e_cache where dt < {$dt}");

    $dt = time() - 30 * 24 * 60 * 60;
    $emps->db->query("delete from " . TP . "e_sessions where dt < {$dt}");
    $emps->db->query("delete from " . TP . "e_php_sessions where dt < {$dt}");

    $dt = time() - 60 * 15;
    $emps->db->query("delete from " . TP . "e_php_sessions where (dt = cdt and dt < {$dt}) or sess_id = ''");

    $dt = time() - 60 * 60 * 24;
    $emps->db->query("delete from " . TP . "e_pincode where (dt <= {$dt})");

    $r = $emps->db->query("select s.id as sid, b.id as bid from " . TP . "e_browsers as b left join " .
        TP . "e_php_sessions as s on b.id = s.browser_id having sid is null limit 2000");
    $lst = array();
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[] = $ra['bid'];
    }

    if (count($lst) > 0) {
        $tlst = implode(", ", $lst);
        $emps->db->query("delete from " . TP . "e_browsers where id in (" . $tlst . ")");
    }

    $emps->service_blacklist();

    file_get_contents(EMPS_SCRIPT_WEB . "/sqlsync/");
}

