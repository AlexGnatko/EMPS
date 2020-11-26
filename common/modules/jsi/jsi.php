<?php
/**
 * Static file sender for JavaScript includes on the include_path
 *
 * Searches the static filesystem (include_path) for the file that matches the requested URL and certain MIME types.
 * JavaScript components can be installed to a server-wide include_path folder and accessed by EMPS websites through this controller.
 */

$emps->no_smarty = true;

$x = explode("?", $_SERVER['REQUEST_URI'], 2);
$uri = $x[0];

$uri = str_replace('../', '/', $uri);

$x = explode("/jsi/", $uri);
$uri = $x[1];

$fname = stream_resolve_include_path($uri);

if (file_exists($fname)) {

    $content_type = \MimeType\MimeType::getType($fname);

    $go = false;
    if (stristr($content_type, "javascript")) {
        $go = true;
    }
    if (stristr($content_type, "image")) {
        $go = true;
    }
    if (stristr($content_type, "css")) {
        $go = true;
    }


    if ($go) {
        ob_end_clean();

        if (file_exists($fname) && !is_dir($fname)) {

            $fh = fopen($fname, "rb");

            if ($fh) {
                $size = filesize($fname);

                if (class_exists('http\Env\Response')) {

                    $body = new http\Message\Body($fh);
                    $resp = new http\Env\Response;

                    $resp->setContentType($content_type);
                    $resp->setHeader("Content-Length", $size);
                    $resp->setHeader("Last-Modified", date("r", filemtime($fname)));
                    $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
                    $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
                    $resp->setBody($body);
                    $resp->send();
                } else {
                    header("Content-Type: " . $content_type);
                    header("Content-Length: " . $size);
                    header("Last-Modified: ", date("r", filemtime($fname)));
                    header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
                    header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));

                    fpassthru($fh);
                }

                fclose($fh);
                exit();
            } else {
                $emps->not_found();
            }
        } else {
            $emps->not_found();
        }
    } else {
        $emps->not_found();
    }
} else {
    $emps->not_found();
}

