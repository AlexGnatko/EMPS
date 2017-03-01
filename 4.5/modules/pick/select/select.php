<?php
$emps->no_smarty = true;

$type = $_GET['selector_type'];
$smarty->assign("object_id", $_GET['id']);
$smarty->assign("value", $emps->utf8_urldecode($_GET['value']));
$smarty->assign("what", $emps->utf8_urldecode($_GET['selector_name']));
$smarty->assign("lister", "/pick-list/$type/");

$smarty->assign("lang", $emps->lang);

$smarty->display("db:_pick/select,selector");
