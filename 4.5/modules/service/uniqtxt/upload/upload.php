<?php

//require_once "webmaster.api/src/webmasterApi.php";

//use yandex\webmaster\api\webmasterApi;

$emps->no_smarty = true;

header("Content-Type: text/plain; charset=utf-8");

$id = intval($key);


require_once $emps->common_module('uniqtxt/uniqtxt.class.php');
$utxt = new EMPS_UniqueTexts;

require_once $emps->common_module('yandex/yandex.class.php');
$wmAPI = new EMPS_Yandex_Webmaster_API;



$urow = $emps->db->get_row("e_unique_texts", "id = ".$id);
if($urow){

    echo "Выгрузка на Яндекс: ".$emps->form_time(time())."\r\n";

    $fail = true;
    $yandex_token = $emps->get_setting("yandex_token");
    if($yandex_token) {
        $yt = json_decode($yandex_token, true);
        if(!$yt){
            echo "Косячный токен доступа Яндекс!\r\n";
        }else{
            $fail = false;
        }
    }else{
        echo "Нет токена доступа Яндекса!\r\n";
    }


    if(!$fail){
        echo "Токен есть, шлём данные...\r\n";

        if($wmAPI->prepare($yt['value'])){

            $wmAPI->host_id = $emps->get_setting("yandex_host_id");

            echo "/original-texts/\r\n";
            $response = $wmAPI->host_post("/original-texts/", ['content' => $urow['unique_text']]);
            echo "Ответ Яндекса:\r\n";
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }


    }else{
        echo "Ничего не вышло...\r\n";
    }

    $output = ob_get_flush();

    $update = [];
    $update['SET'] = ['upload_log' => $output];
    $emps->db->sql_update_row("e_unique_texts", $update, "id = ".$id);
}else{
    echo "Нет такого текста!";
}

