<?php
$emps->no_smarty = true;

require_once $emps->core_module("service.class");

$srv = new EMPS_Service;
$srv->common_mode = true;

sleep(mt_rand(1,3));

$srv->init("last_sendmail", 30);

require_once $emps->common_module("mail/mail.class.php");

$mail = new EMPS_Mail();

if($srv->is_runnable()) {
    $tn = TP . "e_msgcache";

    $dt = time() - 24 * 60 * 60;
    $emps->db->query("delete from {$tn} where status >= 50 and sdt < {$dt} and sdt > 0");

    for ($i = 0; $i < 20; $i++) {
        $r = $emps->db->query("select * from $tn where status < 50 order by status asc, sdt asc limit 1");
        $dt = time();

        while ($ra = $emps->db->fetch_named($r)) {
            if ($ra['sdt'] > (time() - 60)) {
                continue;
            }
            $to = $ra['to'];
            $msg_id = $ra['id'];
            $status = $ra['status'];
            $emps->db->query("update {$tn} set status = status + 1, sdt = {$dt} where id = {$msg_id}");
            $params = unserialize($ra['params']);
            $smtpdata = unserialize($ra['smtpdata']);
            if (!$smtpdata) {
                $smtpdata = $emps_smtp_data;
            }
            $xr = $mail->mail_smtp($ra['to'], $ra['title'], $ra['message'], $smtpdata, $params);
            if ($xr) {
                $emps->db->query("update {$tn} set `status` = 50, `sdt` = {$dt} where id = {$msg_id}");
                echo "Sent: {$to} ({$msg_id})\r\n";
                error_log("Sent e-mail to {$to} (#{$msg_id})");
            } else {
                echo "Delayed: {$to} ({$msg_id}), status " . ($status + 1) . "\r\n";
            }
        }

    }
}