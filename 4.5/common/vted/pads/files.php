<?php

global $context_id, $vted, $smarty;

$context_id = $vted->context_id;

$smarty->assign("context_id", $context_id);

require_once $emps->page_file_name("_comp/files", "controller");
