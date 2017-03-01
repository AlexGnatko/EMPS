<?php

if ($emps->auth->credentials("admin,author,editor,oper")) {

} else {
    $emps->deny_access("AdminNeeded");
}
