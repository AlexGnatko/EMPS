<?php

if($emps->auth->credentials("users")){
    $session_token = $_SESSION['OAUTH_ACCESS_TOKEN'];
    if(isset($session_token['https://oauth.yandex.ru/token'])){
        $smarty->assign("yandex_token", $session_token);
    }
}else{
    $emps->deny_access("UserNeeded");
}