<?php

class EMPS_TelegramBot {
    private $key = "";
    private $post_mode = "application/x-www-form-urlencoded";
    private $url = "https://api.telegram.org/";

    public function set_key($key) {
        $this->key = $key;
    }

    public function get_api_url($resource)
    {
        $api_url = $this->url;
        return $api_url . "bot" . $this->key . "/" . $resource;
    }

    protected function default_http_headers()
    {
        return [
            'Accept: application/json',
            'Content-Type: ' . $this->post_mode
        ];
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

    function get($resource, $data = [])
    {
        $api_url = $this->get_api_url($resource);
        $headers = $this->default_http_headers();
        $url = $api_url;
        if (count($data) > 0) {
            $url .= '?' . $this->data_to_string($data);
        }

        $ch = curl_init($url);
        $this->curl_opts($ch);
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

    function post($resource, $data)
    {
        $this->post_mode = "application/x-www-form-urlencoded";
        $url = $this->get_api_url($resource);
        $headers = $this->default_http_headers();
        $data_json = $this->data_to_string($data);

        $ch = curl_init($url);
        $this->curl_opts($ch);
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

    function post_json($resource, $data)
    {
        $this->post_mode = "application/json";
        $url = $this->get_api_url($resource);
        $headers = $this->default_http_headers();
        $data_json = json_encode($data);

        $ch = curl_init($url);
        $this->curl_opts($ch);
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

    public function error($message) {
        error_log($message);
    }
}