<?php

$emps->no_smarty = true;

require_once $emps->common_module('photos/photos.class.php');
$photos = new EMPS_Photos;

$md5 = $photos->get_pic_md5();

$r = $emps->db->query("select * from " . TP . "e_uploads where md5='$md5'");
$ra = $emps->db->fetch_named($r);

if ($ra) {
    $id = $ra['id'];

    $size = $_GET['size'];
    if (!$size) {
        $size = $start;
    }

    $t = $photos->ensure_thumb($ra, $size, $_GET['opts']);

    $fname = $t['fname'];

    $fh = fopen($fname, "rb");

    if ($fh) {
        ob_end_clean();

        $size = filesize($fname);

        $type = $ra['type'];
        if ($ra['new_type']) {
            $type = $ra['new_type'];
        }

        $content_type = "image/jpeg";
        if (strstr($ra['type'], "jpeg")) {
            if ($type == "image/webp") {
                $content_type = $type;
            }
        } elseif (strstr($ra['type'], "png")) {
        } elseif (strstr($ra['type'], "gif")) {
        } else {
            $content_type = $type;
        }

        if (class_exists('http\Env\Response')) {
            $body = new http\Message\Body($fh);
            $resp = new http\Env\Response;

            $resp->setContentType($content_type);
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $ra['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            $resp->setHeader("Pragma", "");

            $resp->setBody($body);
            $resp->send();
        } else {

            header("Content-Type: ".$content_type);
            header("Content-Length: " . $size);
            header("Last-Modified: " . date("r", $ra['dt']));
            header("Expires: " . date("r", time() + 60 * 60 * 24 * 7));
            header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            header("Pragma: ");

            fpassthru($fh);
        }

        fclose($fh);
    }

}

