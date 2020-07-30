<?php
global $emps;

// With composer, we no longer need this
//require_once "Smarty3/libs/Smarty.class.php";

$smarty = new Smarty;

$smarty->left_delimiter = '{{';
$smarty->right_delimiter = '}}';
if (defined('EMPS_WEBSITE_SCRIPT_PATH')) {
    $emps_smarty_compile_dir = EMPS_WEBSITE_SCRIPT_PATH . "/local/temp_c/" . $emps->lang . "/";

} else {
    $emps_smarty_compile_dir = EMPS_SCRIPT_PATH . "/local/temp_c/" . $emps->lang . "/";
}

if (!is_dir($emps_smarty_compile_dir)) {
    mkdir($emps_smarty_compile_dir);
    chmod($emps_smarty_compile_dir, 0777);
}

$smarty->setCompileDir($emps_smarty_compile_dir);

$smarty->cache_lifetime = 1800;
$smarty->compile_check = true;
$smarty->caching = false;

class Smarty_Resource_EMPS_DB extends Smarty_Resource_Custom
{
    protected function fetch($name, &$source, &$mtime)
    {
        global $emps;

        $r = $emps->get_setting($name);

        if (!$r) {
            $fn = $emps->page_file_name($name, 'view');
            if (file_exists($fn)) {
                $source = file_get_contents($fn);
                $mtime = filemtime($fn);
            } else {
                $fn = $emps->common_module_html($name);

                if (file_exists($fn)) {
                    $source = file_get_contents($fn);
                    $mtime = filemtime($fn);
                } else {
                    $source = "";
                    $mtime = time() - 60;
                }
            }
        } else {
            $source = $r;
            $mtime = $emps->get_setting_time($name);
        }
        return true;
    }

    protected function fetchTimestamp($name)
    {
        global $emps;

        $r = $emps->get_setting_time($name);

        if ($r == -1 || !$r) {
            $fn = $emps->page_file_name($name, 'view');
            if (!file_exists($fn)) {
                $fn = $emps->common_module_html($name);
                if (!file_exists($fn)) {
                    return time() - 60;
                } else {
                    $r = filemtime($fn);
                }
            } else {
                $r = filemtime($fn);
            }
        }
        return $r;
    }
}

;

class Smarty_Resource_EMPS_Page extends Smarty_Resource_Custom
{
    protected function fetch($name, &$source, &$mtime)
    {
        global $emps;

        $ra = $emps->get_db_content_item($name);
        if ($ra) {
            $data = $emps->get_content_data($ra);
            if ($data['html']) {
                $source = $data['html'];
                $mtime = $ra['dt'];
            }
        } else {
            $source = "";
            $mtime = time() - 60;
        }
        return true;


    }

    protected function fetchTimestamp($name)
    {
        global $emps;

        $ra = $emps->get_db_content_item($name);
        if ($ra) {
            return $ra['dt'];
        } else {
            return (time() - 60);
        }
    }
}

;

$smarty->registerResource('db', new Smarty_Resource_EMPS_DB());
$smarty->registerResource('page', new Smarty_Resource_EMPS_Page());


function smarty_emps($params, Smarty_Internal_Template $template)
{
    global $emps;
    if ($params['method']) {
        if (method_exists($emps, $params['method'])) {
            $method_name = $params['method'];
            return $emps->$method_name();
        }
    }

    if ($params['plugin']) {
        $function = $params['plugin'];
        $fname = 'smarty_plugin_' . $function;

        if (function_exists($fname)) {
            return $fname($params, $template);
        }
    }

    return "";
}

function smarty_AJ($params, Smarty_Internal_Template $template)
{
    if (isset($params['v'])) {
        return '{{ ' . $params['v'] . ' }}';
    } else {
        return '{{';
    }
}

function smarty_JA($params, Smarty_Internal_Template $template)
{
    return '}}';
}

function smarty_syn($params, Smarty_Internal_Template $template)
{
    global $emps;

    if(isset($params['v'])) {
        $x = explode("|", $params['v']);
        $max = count($x) - 1;
        $oid = $emps->prand(0, $max);
        $id = round($oid);
        if ($id > $max) {
            $id = $max;
        }
        return $x[$id];
    }
    if(isset($params['seed'])){
        $emps->prand_md5_seed($params['seed']);
    }
    if(isset($params['urlseed'])){
        $emps->prand_md5_seed(md5($emps->URI));
    }
}

function smarty_function_var($params, Smarty_Internal_Template $template)
{
    foreach ($params as $n => $v) {
        $template->assignGlobal($n, $v);
    }
}

function smarty_function_script($params, Smarty_Internal_Template $template)
{
    global $emps;

    $defer = "";
    if (isset($params['defer'])) {
        $defer = " defer";
    }
    $reset = $emps->get_setting("css_reset");
    $val = sprintf("<script type=\"application/javascript\" src=\"%s%s\"%s></script>", $params['src'], $reset, $defer);;
    return $val;
}


$smarty->registerPlugin("function", "emps", "smarty_emps");

// Angular JS markup helpers
$smarty->registerPlugin("function", "AJ", "smarty_AJ");
$smarty->registerPlugin("function", "JA", "smarty_JA");
$smarty->registerPlugin("function", "syn", "smarty_syn");
$smarty->registerPlugin("function", "var", "smarty_function_var");
$smarty->registerPlugin("function", "script", "smarty_function_script");

function smarty_modifier_hyp($v)
{
    if ($v == 0) {
        return '-';
    } else {
        return $v;
    }
}

// Call an arbitrary EMPS method from within a Smarty template (as modifier).
// E.g. {{$dt|emps:form_date}} to transform a Unix timestamp into a formatted date
function smarty_modifier_emps($arg1, $m, $arg2 = "", $arg3 = "")
{
    global $emps;

    return call_user_func_array(array($emps, $m), array($arg1, $arg2, $arg3));
}


function smarty_modifier_js($v)
{
    return "{{" . $v . "}}";
}

function smarty_modifier_syn($v)
{
    global $emps;

    if(isset($v)) {
        $x = explode("|", $v);
        $max = count($x) - 1;
        $oid = $emps->prand(0, $max);
        $id = round($oid);
        if ($id > $max) {
            $id = $max;
        }
//        echo $id . " ({$oid}, {$max}) // ";
        return $x[$id];
    }
    return "";
}


$smarty->registerPlugin("modifier", "hyp", "smarty_modifier_hyp");
$smarty->registerPlugin("modifier", "emps", "smarty_modifier_emps");
$smarty->registerPlugin("modifier", "js", "smarty_modifier_js");
$smarty->registerPlugin("modifier", "syn", "smarty_modifier_syn");

if (defined('EMPS_PRE_MINIFY')) {
    if (EMPS_PRE_MINIFY) {
        /* Minify the html */
        function smarty_pre_minify($tpl_source, Smarty_Internal_Template $template)
        {
            global $emps;

            if($emps->dont_minify){
                return $tpl_source;
            }

            return preg_replace('/[\r\n]+/s', "\r\n", preg_replace('/[ \t]+/s', ' ', $tpl_source));
        }

        $smarty->registerFilter('pre', 'smarty_pre_minify');
    }
}

$fn = $emps->common_module('config/smarty/modifiers.php');
if (file_exists($fn)) {
    require_once $fn;
}
