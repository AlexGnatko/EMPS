<?php

$emps->no_smarty = true;

require_once $emps->common_module('photos/photos.class.php');
$photos = new EMPS_Photos;

$md5 = $photos->get_pic_md5();
$r = $emps->db->query("select * from " . TP . "e_uploads where md5='$md5'");
$ra = $emps->db->fetch_named($r);
if ($ra) {
    $id = $ra['id'];

    if ($ra['wmark']) {
        $fname = $photos->up->upload_filename($id, DT_IMAGEWM);
    } else {
        $fname = $photos->up->upload_filename($id, DT_IMAGE);
    }

    $fh = fopen($fname, "rb");

    if ($fh) {
        ob_end_clean();

        $size = filesize($fname);

        $type = $ra['type'];
        if ($ra['new_type']) {
            $type = $ra['new_type'];
        }

        $filename = $file['filename'];
        if ($type == "image/webp") {
            $x = explode(".", $filename);
            array_pop($x);
            $x[] = "webp";
            $filename = implode(".", $x);
        }

        if (class_exists('http\Env\Response')) {

            $body = new http\Message\Body($fh);
            $resp = new http\Env\Response;

            $resp->setContentType($type);
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $ra['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
            $resp->setHeader("Pragma", "");
            if ($_GET['download']) {
                $resp->setContentDisposition(["attachment" => ["filename" => $filename]]);
            }
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            //$resp->setThrottleRate(1024 * 512, 0);

            $resp->setBody($body);
            $resp->send();
        }else{
            header("Content-Type: ".$type);
            header("Content-Length: " . $size);
            header("Last-Modified: " . date("r", $ra['dt']));
            header("Expires: " . date("r", time() + 60 * 60 * 24 * 7));
            if ($_GET['download']) {
                header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
            }
            header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            header("Pragma: ");

            fpassthru($fh);
        }

        fclose($fh);
    }

}
