<?php

if($emps->auth->credentials("users")){
    $session_token = $_SESSION['OAUTH_ACCESS_TOKEN'];
    if(isset($session_token['https://oauth.yandex.ru/token'])){
        $yandex_token = $session_token['https://oauth.yandex.ru/token'];
        $smarty->assign("yandex_token", $yandex_token);

        if($_GET['capture_token']){
            $emps->save_setting("yandex_token", json_encode($yandex_token));
            $emps->redirect_elink(); exit;
        }
    }
    $stored_yandex_token = $emps->get_setting("yandex_token");
    if($stored_yandex_token){
        $stored_yandex_token = json_decode($stored_yandex_token, true);
        $smarty->assign("stored_yandex_token", $stored_yandex_token);
    }
}else{
    $emps->deny_access("UserNeeded");
}