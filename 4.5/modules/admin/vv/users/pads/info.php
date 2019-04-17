<?php

if($_GET['login_as']){
    $this->row = $this->load_row($this->ref_id);
    $emps->auth->create_session($this->row['username'], '', true);
    $emps->redirect_page('/my/'); exit;
}
