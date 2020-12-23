<?php

if ($emps->auth->credentials("admin")) {
    $emps->page_property("adminpage", 1);
    $emps->page_property("css_fw", "bulma");
    if ($_POST['post']) {
        $emps->no_smarty = true;
        header("Content-Type: text/plain; charset=utf-8");

        $data = $_POST;

        $x = explode("-", $data['url_path']);
        $data['file_name'] = array_pop($x);

        $data['dir_path'] = str_replace("-", "/", $data['url_path']);
        $smarty->assign("data", $data);

        $emps->dont_minify = true;

        $path = EMPS_SCRIPT_PATH."/modules/".$data['dir_path'];
        mkdir($path, 0, true);
        $file_name = $path."/".$data['file_name'].".js";
        $accounts = $smarty->fetch("db:_emps/prototype/vted/templates,accounts_js");
        echo $file_name."\r\n";
        echo $accounts;
        file_put_contents($file_name, $accounts);
        $file_name = EMPS_SCRIPT_PATH."/modules/".$data['dir_path']."/common.php";
        $common = $smarty->fetch("db:_emps/prototype/vted/templates,common_php");
        $common = "<?php\r\n\r\n" . $common;
        echo $file_name."\r\n";
        echo $common;
        file_put_contents($file_name, $common);
        $file_name = EMPS_SCRIPT_PATH."/modules/".$data['dir_path']."/".$data['file_name'].".nn.htm";
        $module = $smarty->fetch("db:_emps/prototype/vted/templates,module_htm");
        echo $file_name."\r\n";
        echo $module;
        file_put_contents($file_name, $module);
        $file_name = EMPS_SCRIPT_PATH."/modules/".$data['dir_path']."/".$data['file_name'].".php";
        $module = $smarty->fetch("db:_emps/prototype/vted/templates,module_php");
        $module = "<?php\r\n\r\n" . $module;
        echo $file_name."\r\n";
        echo $module;
        file_put_contents($file_name, $module);
    }
} else {
    $emps->deny_access("AdminNeeded");
}