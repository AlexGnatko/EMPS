<?php
$emps->no_smarty = true;

if ($key) {
    $file = $emps->db->get_row("e_files", "md5='$key'");

    if ($file) {

        require_once $emps->common_module('uploads/uploads.class.php');
        $up = new EMPS_Uploads;

        $fname = $up->upload_filename($file['id'], DT_FILE);

        $fh = fopen($fname, "rb");

        if ($fh) {
            ob_end_clean();

            $size = filesize($fname);

            if (class_exists('http\Env\Response')) {
                $body = new http\Message\Body($fh);
                $resp = new http\Env\Response;
                $resp->setContentType("application/octet-stream");
                $emps->conditional_content_length($resp, $size);
                $resp->setHeader("Last-Modified", date("r", $file['dt']));
                $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
                $resp->setContentDisposition(["attachment" => ["filename" => $file['file_name']]]);
                $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
                $resp->setBody($body);
                //			$resp->setThrottleRate(50000, 1);
                $resp->send();
            } else {
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . $size);
                header("Last-Modified: ", date("r", $file['dt']));
                header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
                header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
                header("Content-Disposition: attachment; filename=\"" . $file['file_name'] . "\"");

                fpassthru($fh);
            }

            fclose($fh);
        }
    }
}

