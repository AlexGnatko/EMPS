<?php

if ($_POST['post_message']) {
    $action = $_POST['action'];
    $payload = $_REQUEST['payload'];
    $rc = $_SESSION['last_rc_token_' . $action];
    if (isset($rc) && ($rc['token'] == $payload['token']) && ($rc['action'] == $action)) {

        require_once($emps->common_module("mail/mail.class.php"));

        $mail = new EMPS_Mail;

        $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
        $smarty->assign("row", $payload);
        $body = $smarty->fetch("db:msg/msginfo");

        $nr = $payload;
        $nr['msg'] = $body;

        $emps->db->sql_insert_row("e_messages", ['SET' => $nr]);
        $id = $emps->db->last_insert();

        $row = $emps->db->get_row("e_messages", "id = {$id}");
        $row['time'] = $emps->form_time($row['dt']);
        $row['msg'] = $payload['msg'];

        $smarty->assign("msg", $row);
        $to = $emps->get_setting("order_mailto");
        if(!$to){
            $to = "gnatko@mail.ru";
        }

        $body = $smarty->fetch("db:msg/enquiry");
        $params = $emps_smtp_params;
        $params['Reply-To'] = $row['email'];
        $params['Content-Type'] = "text/html; charset=utf-8";
        $r = $mail->queued_email($to, $mail->encode_string($smarty->fetch("db:msg/enquiryhead"),'utf-8'),
            $body, $params);

        if($r){
            $response = [];
            $response['code'] = "Error";
            $response['message'] = "Сбой отправки сообщения";
            $emps->json_response($response); exit;
        }else{
            $response = [];
            $response['code'] = "OK";
            $emps->json_response($response); exit;

        }

    } else {
        $response = [];
        $response['code'] = "Error";
        $response['message'] = "Не удалось проверить reCAPTCHA";
        //$response['rc'];
        $emps->json_response($response); exit;
    }
}
