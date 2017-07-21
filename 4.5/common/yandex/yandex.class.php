<?php

class EMPS_Yandex_Webmaster_API {
    public $url = "https://api.webmaster.yandex.net/v3";
    public $access_token = "";
    public $user_id = "";

    protected function default_http_headers()
    {
        return array(
            'Authorization: OAuth ' . $this->access_token,
            'Accept: application/json',
            'Content-type: application/json'
        );
    }

    public function prepare($token){
        $this->access_token = $token;

        $response = $this->get("/user/");
        if($response['user_id']){
            $this->user_id = $response['user_id'];
            return true;
        }
        return false;
    }

    public function get_api_url($resource)
    {
        $api_url = $this->url;
        return $api_url . $resource;
    }

    function get($resource, $data = [])
    {
        $api_url = $this->get_api_url($resource);
        $headers = $this->default_http_headers();
        $url = $api_url . '?' . $this->data_to_string($data);

        // Шлем запрос в курл
        $ch = curl_init($url);
        // основные опции curl
        $this->curl_opts($ch);
        // передаем заголовки
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (!is_array($response)) {
            return $this->error('Unknown error in get');
        }

        return $response;
    }

    protected function post($resource, $data)
    {
        $url = $this->get_api_url($resource);
        $headers = $this->default_http_headers();
        $data_json = json_encode($data);

        // Шлем запрос в курл
        $ch = curl_init($url);
        // основные опции курл
        $this->curl_opts($ch);
        // передаем заголовки
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (!is_array($response)) {
            return $this->error('Unknown error in post');
        }

        return $response;
    }

    function user_get($resource, $data = [])
    {
        return $this->get("/user/" . $this->user_id . $resource, $data);
    }
    function user_post($resource, $data = [])
    {
        return $this->post("/user/" . $this->user_id . $resource, $data);
    }
    function host_get($resource, $data = [])
    {
        return $this->user_get("/hosts/" . $this->host_id . $resource, $data);
    }
    function host_post($resource, $data = [])
    {
        return $this->user_post("/hosts/" . $this->host_id . $resource, $data);
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

    private function error($message, $json = true)
    {
        $this->last_error = $message;
        if ($json) {
            trigger_error($message, E_USER_ERROR);
            return (object)array('error_code' => 'ERROR', 'error_message' => $message);
        }
        return false;
    }
}