<?php

if ($_GET['make_clone']) {
    $url = $this->clone_row($this->ref_id);

    $emps->redirect_page($url); exit;
}