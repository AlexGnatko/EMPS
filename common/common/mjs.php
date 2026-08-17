<?php

$emps->no_smarty = true;

$last_modified = date("r", time() - 60 * 60 * 24 * 7);
$expires = date("r", time() + 60 * 60 * 24 * 7);

header("Last-Modified: " . $last_modified);
header("Expires: " . $expires);
header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
header_remove("Pragma");

/*
 * The only URL shape this module serves is:
 *
 *     /mjs/<module-path>/<file-name>
 *
 * <module-path> is a module folder where hyphens stand for slashes ("comp-mixins" => "comp/mixins"),
 * <file-name> is a plain file name inside that folder - never a path.
 *
 * Both values come straight from the URL and are concatenated into a filesystem path below, so they
 * are validated here against a strict whitelist rather than filtered: no slashes, no dots in the
 * module path, no leading dot and no ".." in the file name. Anything else is a 404.
 */
$mjs_key = strval($key);
$mjs_file = strval($start);

if (!preg_match('/^[A-Za-z0-9_-]+$/', $mjs_key)) {
    $emps->not_found();
    exit;
}

if (!preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]*$/', $mjs_file) || strstr($mjs_file, "..")) {
    $emps->not_found();
    exit;
}

$part = str_replace("-", "/", $mjs_key);
$file = $mjs_file;

$x = explode(".", $file);
$ext = "";
if (count($x) > 1) {
    $ext = mb_strtolower(array_pop($x));
}

/*
 * Extensions are whitelisted, not blacklisted: a project that needs to publish another file type
 * through /mjs/ declares it in EMPS_MJS_EXTENSIONS in its local.php.
 */
$mjs_extensions = "js,css,vue";
if (defined("EMPS_MJS_EXTENSIONS")) {
    $mjs_extensions = EMPS_MJS_EXTENSIONS;
}

if (!$emps->in_list($ext, $mjs_extensions)) {
    $emps->not_found();
    exit;
}

if ($ext == "css") {
    header("Content-Type: text/css");
}

if ($ext == "js") {
    header("Content-Type: application/javascript; charset=utf-8");
}

if ($ext == "vue") {
    header("Content-Type: text/html; charset=utf-8");
}

$page = "_{$part},{$file}";

$file_name = $emps->page_file_name($page, "inc");
if(!$file_name){
    $emps->not_found();
    exit;
}

if ($ext == "vue") {
    $emps->pre_display();
    $page = "_{$part},!{$file}";
    $smarty->display("db:{$page}");
} else {
    $fh = fopen($file_name, "rb");
    if($fh){
        fpassthru($fh);
        fclose($fh);
    }
}

