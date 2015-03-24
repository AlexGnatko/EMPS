<?php
// General initialization settings not to be customized by individual projects

mb_internal_encoding('utf-8');
date_default_timezone_set(EMPS_TZ);

ini_set("session.cookie_lifetime", EMPS_SESSION_COOKIE_LIFETIME);
ini_set("session.cookie_path", "/");
ini_set("session.use_cookies", "1");
ini_set("session.use_only_cookies", "1");
ini_set("magic_quotes_runtime", "0");

$emps_bots = array(
'YandexBot',
'SputnikBot',
'YandexMetrika',
'Yahoo! Slurp',
'bingbot',
'StackRambler',
'Googlebot',
);

?>