<?php
if ($emps->auth->credentials("admin")):
    $emps->page_property("ited", 1);

    require_once($emps->common_module("mail/mail.class.php"));

    $mail = new EMPS_Mail();

    include($emps->common_module('ted/ted.class.php'));

    class EMPS_MsgCache extends EMPS_TableEditor
    {
        public $table_name = 'e_msgcache';
        public $tord = " order by dt desc, id desc ";

        public function handle_row($row) {
            global $mail;
            $dec = $mail->decode_string($row['title']);
            $row['subject'] = $dec[0]->text;
            return $row;
        }
    }

    $ted = new EMPS_MsgCache;

    if ($_GET['info']) {
        $id = intval($_GET['info']);
        $row = $emps->db->get_row("e_msgcache", "id = {$id}");
        $row = $ted->handle_row($row);
        $response = [];
        $response['code'] = "OK";
        $response['msg_id'] = $id;
        $response['subject'] = $row['subject'];
        $response['body'] = $row['message'];
        $params = unserialize($row['params']);
        $response['params'] = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $smtpdata = unserialize($row['smtpdata']);
        $response['smtpdata'] = json_encode($smtpdata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $emps->json_response($response); exit;
    }

    $ted->handle_request();
else:
    $emps->deny_access("AdminNeeded");
endif;

