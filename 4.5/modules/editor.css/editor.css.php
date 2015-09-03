<?php

header("Content-Type: text/css; charset=utf-8");

$emps->no_smarty = true;

echo '/* fonts.css */'."\r\n";
echo file_get_contents($emps->plain_file("/fonts/fonts.css"));
echo '/* bootstrap.min.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/bootstrap.min.css"));
echo '/* default.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/default.css"));
echo '/* editor.css */'."\r\n";
echo file_get_contents($emps->plain_file("/css/editor.css"));

?>