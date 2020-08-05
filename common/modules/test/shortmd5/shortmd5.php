<?php

if ($emps->auth->credentials("admin")) {
    $emps->no_smarty = true;
    $emps->plaintext_response();

    $count = 10000;

    for ($i = 0; $i < $count; $i++) {
        $md5 = md5(uniqid(microtime(true) + 1));
        $short = $emps->short_md5($md5);
        $long = $emps->long_md5($short);
        $cmp = strcmp($md5, $long);
        echo "Source MD5: {$md5}, short: {$short}, long: {$long}, cmp: {$cmp}\r\n";
    }
} else {
    $emps->deny_access("AdminNeeded");
}

