<?php

$emps->uses_antibot();

$emps->json_response(['sid' => $_SESSION['antibot_pin']['sid']]);
