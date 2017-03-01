<?php

require 'twilio-php-master/Services/Twilio.php';

$emps->no_smarty = true;

$tn = TP . "e_smscache";

$r = $emps->db->query("show tables like '" . $tn . "'");
$ra = $emps->db->fetch_row($r);
if (!$ra) {
    echo "No SMS table!";
    exit();
}

$dt = time() - 7 * 24 * 60 * 60;

$emps->db->query("delete from $tn where status=50 and sdt<$dt and sdt>0");

$r = $emps->db->query("select * from $tn where status = 0 order by sdt asc, dt asc limit 20");
$dt = time();

require_once $emps->common_module("sms/sms.class.php");

$sms = new EMPS_SMS;

while ($ra = $emps->db->fetch_named($r)) {
    $to = $ra['to'];
    $msg_id = $ra['id'];
    $status = $ra['status'];
    $params = json_decode($ra['params'], true);

    $sms->account_sid = $params['account_sid'];
    $sms->auth_token = $params['auth_token'];
    $sms->from = $params['from'];

    $rv = $sms->send_message($to, $ra['message']);
    $dt = time();

    if ($rv) {
        $emps->db->query("update $tn set status = 50, sdt = $dt where id = $msg_id");
        echo "Sent: " . $to . "<br/>";
    } else {
        $emps->db->query("update $tn set status = 10, sdt = $dt where id = $msg_id");
        echo "ERROR: " . $to . "<br/>";
    }
}
