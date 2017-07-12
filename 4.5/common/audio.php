<?php
$emps->no_smarty = true;

if ($key) {
    $file = $emps->db->get_row("e_files", "md5 = '$key'");


    require_once($emps->common_module('uploads/uploads.class.php'));
    $up = new EMPS_Uploads;

    $fname = $up->upload_filename($file['id'], DT_FILE);

    $fh = fopen($fname, "rb");

    if ($fh) {
        ob_end_clean();

        $size = filesize($fname);

        if (class_exists('http\Env\Response')) {

            $body = new http\Message\Body($fh);
            $resp = new http\Env\Response;

            $resp->setContentType("audio/mpeg");
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $ra['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
            $resp->setHeader("Pragma", "");
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            //$resp->setThrottleRate(1024 * 512, 0);

            $resp->setBody($body);
            $resp->send();
        }else{
            header("Content-Type: audio/mpeg");
            header("Content-Length: " . $size);
            header("Last-Modified: ", date("r", $ra['dt']));
            header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
            header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));

            fpassthru($fh);
        }

        fclose($fh);
    }

}

