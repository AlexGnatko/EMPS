<?php

class EMPS_SMS
{
    public $account_sid;
    public $auth_token;
    public $from;

    public $error_message = "";

    public function __construct()
    {
        $this->account_sid = TWILIO_SID;
        $this->auth_token = TWILIO_TOKEN;
        $this->from = TWILIO_FROM;
    }

    public function enqueue_message($to, $msg)
    {
        global $emps, $SET;

        if (!trim($to)) {
            return false;
        }

        $params = array();
        $params['account_sid'] = $this->account_sid;
        $params['auth_token'] = $this->auth_token;
        $params['from'] = $this->from;

        $SET = array();
        $SET['to'] = $to;
        $SET['message'] = $msg;
        $SET['params'] = json_encode($params);
        $emps->db->sql_insert("e_smscache");
        return true;
    }

    public function send_message($to, $msg)
    {
        $rv = true;
        try {
            $client = new Services_Twilio($this->account_sid, $this->auth_token);
            $client->account->messages->create(array(
                'To' => $to,
                'From' => $this->from,
                'Body' => $msg,
            ));
        } catch (Exception $e) {
            $this->error_message = $e->getMessage();
            error_log("Twilio: " . $this->error_message);
            $rv = false;
        };

        unset($client);

        return $rv;
    }

    public function plain_phone($phone)
    {
        $s = preg_replace('/\D/', '', $phone);
        return $s;
    }
}