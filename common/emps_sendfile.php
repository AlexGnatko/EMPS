<?php
/**
 * Static file sender
 *
 * Searches the static filesystem for the file that matches the requested URL.
 * Will first try EMPS_SCRIPT_PATH, then EMPS_PATH_PREFIX (EMPS version folder), then EMPS_COMMON_PATH_PREFIX (EMPS all-versions folder).
 */

$x = explode("?", $_SERVER['REQUEST_URI'], 2);
$uri = $x[0];

$dir = EMPS_SCRIPT_PATH;

/*
 * The URI is turned into a filesystem path below, so it is checked here rather than cleaned up: a
 * "." or ".." path component, a backslash or a NUL byte means the request is trying to walk out of
 * the document root. Filtering those out instead of refusing them is famously easy to get wrong -
 * str_replace('../', '/') leaves "....//" intact, which resolves to a parent directory all the same.
 */
$emps_sf_deny = false;

if (strpos($uri, "\0") !== false || strstr($uri, "\\") !== false) {
    $emps_sf_deny = true;
}

$emps_sf_parts = explode("/", $uri);
foreach ($emps_sf_parts as $emps_sf_part) {
    if ($emps_sf_part == '.' || $emps_sf_part == '..') {
        $emps_sf_deny = true;
    }
}

$fname = $dir . $uri;

if (!$emps_sf_deny && !strstr($uri, ".php") && !strstr($uri, ".sql") && !strstr($uri, "/modules/") && !strstr($uri, "/templates/") && !strstr($uri, "/local/")) {
    $go = false;

    if (file_exists($fname)) {
        if (!is_dir($fname)) {
            $go = true;
        }
    } else {
        $fname = EMPS_PATH_PREFIX . $uri;
        $fname = stream_resolve_include_path($fname);

        if ($fname != false) {
            $go = true;
        } else {
            $fname = EMPS_COMMON_PATH_PREFIX . $uri;
            $fname = stream_resolve_include_path($fname);
            if ($fname != false) {
                $go = true;
            }
        }
    }

    if ($go) {
        $content_type = \MimeType\MimeType::getType($fname);

        ob_end_clean();

        if (file_exists($fname) && !is_dir($fname)) {

            $fh = fopen($fname, "rb");

            if ($fh) {
                $size = filesize($fname);

                if (class_exists('http\Env\Response')) {

                    $body = new http\Message\Body($fh);
                    $resp = new http\Env\Response;

                    $resp->setContentType("" . $content_type);
                    $resp->setHeader("Content-Length", $size);
                    $resp->setHeader("Access-Control-Allow-Origin", "*");
                    $resp->setHeader("Last-Modified", date("r", filemtime($fname)));
                    $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * EMPS_CACHE_AGE));
                    $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * EMPS_CACHE_AGE));
                    $resp->setBody($body);
                    $resp->send();
                } else {
                    header("Content-Type: " . $content_type);
                    header("Content-Length: " . $size);
                    header("Access-Control-Allow-Origin: *");
                    header("Last-Modified: ", date("r", filemtime($fname)));
                    header("Expires: ", date("r", time() + 60 * 60 * 24 * EMPS_CACHE_AGE));
                    header("Cache-Control: max-age=" . (60 * 60 * 24 * EMPS_CACHE_AGE));

                    fpassthru($fh);
                }

                fclose($fh);
            }
        }

        exit();
    }
}

