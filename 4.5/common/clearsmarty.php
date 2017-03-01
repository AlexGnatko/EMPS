<?php
$emps->no_smarty = true;

if ($emps->auth->credentials("admin")) {
    $smarty->clearCompiledTemplate();
    echo "Smarty compiled templates cleared.";
}
