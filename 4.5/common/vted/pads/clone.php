<?php

global $vted;

if ($_GET['make_clone']) {
    $url = $vted->clone_row($vted->ref_id);

    $emps->redirect_page($url); exit;
}