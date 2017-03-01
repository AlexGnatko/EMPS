<?php
$emps->no_smarty = true;

$type = $key;

$x = explode('|', $type, 2);
$type = $x[0];
$extra = $x[1];

require_once $emps->common_module('tables/tables.class.php');

$tables = new EMPS_Tables;

$text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['text']));
$id = 0;
if ($text) {
    $matches = array();
    preg_match_all("/<([^>]+)>/", $text, $matches);
    $id = $matches[1][count($matches[1]) - 1];
}

$default_text = $emps->db->sql_escape($emps->utf8_urldecode($_GET['default_text']));
if ($text == $default_text) {
    $text = "";
}

require_once $emps->common_module('objsel/objsel.class.php');

$objsel = new EMPS_ObjectSelector();

if ($id) {
    $r = $emps->db->query("select * from " . TP . "$type where id = $id");
    $id_filter_out = " and id <> $id ";
}

$perpage = 10;
$start = intval($start);

//echo "S: ".$_GET['start'];

if ($type == 'e_users') {
    $sql = "select SQL_CALC_FOUND_ROWS * from " . TP . "$type	where (username like '%$text%' or fullname like '%$text%') $id_filter_out limit $start, $perpage";

    if ($extra) {
        $x = explode('|', $extra);
        $el = array();
        while (list($n, $v) = each($x)) {
            $xx = explode('=', $v);
            $el[$xx[0]] = $xx[1];
        }
        if ($el['group']) {
            $sql = "select SQL_CALC_FOUND_ROWS u.* from " . TP . "$type as u 
				join " . TP . "e_users_groups as ug on
				ug.user_id=u.id
				and ug.group_id='" . $el['group'] . "'
				where (u.username like '%$text%' or u.fullname like '%$text%') limit $start, $perpage";
        }
    }
} else {
    $and = $objsel->make_and($extra);
    $sql = "select SQL_CALC_FOUND_ROWS * from " . TP . "$type	where name like '%$text%' $and $id_filter_out limit $start, $perpage";
}

$fn = $emps->page_file_name('_pick/list,query_modifier', 'controller');
if (file_exists($fn)) {
    require_once $fn;
}

$r2 = $emps->db->query($sql);

$total = $emps->db->found_rows();

$pages = $emps->count_pages($total);

$smarty->assign("next", $pages['next']);
$smarty->assign("prev", $pages['prev']);
$smarty->assign("cur", $pages['cur']);

$rlst = array();
while ($ra = $emps->db->fetch_named($r)) {
    $rlst[] = $ra;
}
while ($ra = $emps->db->fetch_named($r2)) {
    $rlst[] = $ra;
}

while (list($n, $ra) = each($rlst)) {
    if ($type == 'e_users') {
        $ra['name'] = '<b>' . $ra['username'] . '</b>' . ' - ' . $ra['fullname'];
    }
    if ($ra['parent']) {
        $ra['parents'] = $tables->text_parents($type, $ra['parent']);
    }
    if (function_exists('emps_pick_list_explain')) {
        $ra = emps_pick_list_explain($ra);
    }
    $lst[] = $ra;
}

$smarty->assign("lst", $lst);

$smarty->assign("lang", $emps->lang);

$smarty->display("db:_pick/list");
