<?php
$emps->no_smarty = true;

$key = strval($key);

/*
 * The hash in the URL is what stands in for a password on an uploaded file, and it always comes
 * from md5(). Nothing else is looked up: a value of another shape is a scanner, not a download.
 */
if (preg_match('/^[0-9a-fA-F]{32}$/', $key)) {
    $file = $emps->db->get_row("e_files", "md5 = " . $emps->db->sql_quote($key));

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
            $resp->setContentType($file['content_type']);
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $file['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * EMPS_CACHE_AGE));
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * EMPS_CACHE_AGE));
            $resp->setHeader("Pragma", "");
            $resp->setBody($body);
            //			$resp->setThrottleRate(50000, 1);
            $resp->send();
        } else {
            header("Content-Type: " . $file['content_type']);
            header("Content-Length: " . $size);
            header("Last-Modified: " . date("r", $file['dt']));
            header("Expires: " . date("r", time() + 60 * 60 * 24 * EMPS_CACHE_AGE));
            header("Cache-Control: max-age=" . (60 * 60 * 24 * EMPS_CACHE_AGE));
            header("Pragma: ");

            fpassthru($fh);
        }
        fclose($fh);
    }
}

