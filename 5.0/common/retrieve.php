<?php
$emps->no_smarty = true;

if ($key) {

    require_once $emps->common_module('uploads/uploads.class.php');
    $up = new EMPS_Uploads;

    $lst = $up->list_files_ex(['filename' => $key, 'ut' => 'f'], ['limit' => 1, 'sort' => ['_id' => -1]]);
    $file = false;
    if(count($lst) > 0) {
        $file = $lst[0];
    }

    if ($file) {
        $fh = $up->bucket->openDownloadStream($emps->db->oid($file['_id']));

        if ($fh) {
            ob_end_clean();

            $size = $file['length'];

            if (class_exists('http\Env\Response')) {
                $body = new http\Message\Body($fh);
                $resp = new http\Env\Response;
                $resp->setContentType("application/octet-stream");
                $resp->setHeader("Content-Length", $size);
                $resp->setHeader("Last-Modified", date("r", $file['dt']));
                $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
                $resp->setContentDisposition(["attachment" => ["filename" => $file['orig_filename']]]);
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
                header("Content-Disposition: attachment; filename=\"" . $file['orig_filename'] . "\"");

                fpassthru($fh);
            }

            fclose($fh);
        }
    }
}

