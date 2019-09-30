<?php

$emps->no_smarty = true;

$token = $_REQUEST['token'];

$action = $_REQUEST['action'];

$url = "https://www.google.com/recaptcha/api/siteverify";

$curl = curl_init($url);


curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$params = [
    'secret' => RECAPTCHA_PRIVATE,
    'response' => $token
];

$parts = [];

foreach ($params as $n => $v) {
    $parts[]= urlencode($n) . "=" . urlencode($v);
}

$data_form = implode("&", $parts);

$headers = [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
];
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_form);

$result = curl_exec($curl);
curl_close($curl);

$json = json_decode($result, true);

$response = [];
$response['code'] = "OK";
//$response['raw'] = $json;
//$response['raw_text'] = $result;
//$response['token'] = $token;
$response['action'] = $action;
//$response['key'] = RECAPTCHA_PRIVATE;
if ($json['success'] && $json['action'] == $action) {
    $response['result'] = 1;
} else {
    $response['result'] = -1;
    if ($json['action'] == $action) {
        $response['code'] = "Error";
        $response['message'] = "reCAPTCHA verification: wrong action!";
    }
}
$response['challenge_ts'] = $json['challenge_ts'];

$_SESSION['last_rc_token_' . $action] = ['token' => $token, 'action' => $action, 'result' => $response['result']];

$emps->json_response($response); exit;