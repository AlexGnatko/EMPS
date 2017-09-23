<?php

$emps->no_smarty = true;

require_once $emps->common_module('photos/photos.class.php');
$photos = new EMPS_Photos;
$up = $photos->up;

$filename = $photos->get_pic_md5();

$lst = $up->list_files_ex(['filename' => $filename, 'ut' => 'i'], ['limit' => 1, 'sort' => ['_id' => -1]]);
$file = false;
if(count($lst) > 0) {
    $file = $lst[0];
}

if ($file) {
    if ($file['view__id']) {
        $file = $up->file_info($file['view__id']);
    }

    $id = $emps->db->oid($file['_id']);

    $fh = $up->bucket->openDownloadStream($id);

    if ($fh) {
        ob_end_clean();

        $size = $file['length'];

        if (class_exists('http\Env\Response')) {

            $body = new http\Message\Body($fh);
            $resp = new http\Env\Response;

            $resp->setContentType($file['content_type']);
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $file['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
            $resp->setHeader("Pragma", "");
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            //$resp->setThrottleRate(1024 * 512, 0);

            $resp->setBody($body);
            $resp->send();
        }else{
            header("Content-Type: ".$file['content_type']);
            header("Content-Length: " . $size);
            header("Last-Modified: ", date("r", $file['dt']));
            header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
            header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));

            fpassthru($fh);
        }

        fclose($fh);
    }

}

