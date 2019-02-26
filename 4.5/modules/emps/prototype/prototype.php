<?php

if ($emps->auth->credentials("admin")) {

} else {
    $emps->deny_access("AdminNeeded");
}