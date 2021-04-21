<?php

class EMPS_SMS
{
    public $account_sid;
    public $auth_token;
    public $from;

    public $mode = "twilio";

    public $error_message = "";

    private $post_mode = "application/x-www-form-urlencoded";
    private $url = "https://smsc.ru/sys/send.php";

    public function __construct()
    {
        $this->account_sid = TWILIO_SID;
        $this->auth_token = TWILIO_TOKEN;
        $this->from = TWILIO_FROM;
        $this->login = SMSC_LOGIN;
        $this->password = SMSC_PASSWORD;
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

    public function send_twilio_message($to, $msg)
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

    protected function curl_opts(&$ch)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return true;
    }

    protected function default_http_headers()
    {
        return [
            'Content-Type: ' . $this->post_mode
        ];
    }

    private function data_to_string($data)
    {
        $queryString = array();
        foreach ($data as $param => $value) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $queryString[] = urlencode($param) . '=' . urlencode($value);
            } elseif (is_array($value)) {
                foreach ($value as $valueItem) {
                    $queryString[] = urlencode($param) . '=' . urlencode($valueItem);
                }
            } else {
                continue;
            }
        }
        return implode('&', $queryString);
    }

    function post($data)
    {
        $this->post_mode = "application/x-www-form-urlencoded";
        $url = $this->url;
        $headers = $this->default_http_headers();
        $data['login'] = $this->login;
        $data['psw'] = $this->password;
        $data['fmt'] = 3;
        $data_string = $this->data_to_string($data);

        $ch = curl_init($url);
        $this->curl_opts($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (!is_array($response)) {
            return $this->error('Unknown error in post');
        }

        return $response;
    }

    public function send_smsc_message($to, $msg) {
        $rv = $this->post(['phones' => $to, 'mes' => $msg]);
        error_log("SMSC rv: {$rv}");
        $rv = json_decode($rv, true);
        if (isset($rv['error'])) {
            $this->error_message = $rv['error'];
            return false;
        }
        return true;
    }

    public function send_message($to, $msg)
    {
        if ($this->mode == "twilio") {
            return $this->send_twilio_message($to, $msg);
        }
        if ($this->mode == "smsc") {
            return $this->send_smsc_message($to, $msg);
        }
    }

    public function plain_phone($phone)
    {
        $s = preg_replace('/\D/', '', $phone);
        return $s;
    }
}