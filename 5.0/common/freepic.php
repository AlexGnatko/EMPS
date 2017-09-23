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
    $id = $emps->db->oid($file['_id']);

    $thumb_file_id = $photos->ensure_thumb($file, $_GET['size'], $_GET['opts']);

    if(!$thumb_file_id){
        exit;
    }

    $thumb_file = $up->file_info($thumb_file_id);

    $fh = $up->bucket->openDownloadStream($emps->db->oid($thumb_file_id));

    if ($fh) {
        ob_end_clean();

        $size = $thumb_file['length'];

        if (class_exists('http\Env\Response')) {
            $body = new http\Message\Body($fh);
            $resp = new http\Env\Response;

            $content_type = "image/jpeg";
            if (strstr($file['content_type'], "jpeg")) {
            } elseif (strstr($file['content_type'], "png")) {
            } elseif (strstr($file['content_type'], "gif")) {
            } else {
                $content_type = $file['content_type'];
            }

            $resp->setContentType($content_type);
            $resp->setHeader("Content-Length", $size);
            $resp->setHeader("Last-Modified", date("r", $file['dt']));
            $resp->setHeader("Expires", date("r", time() + 60 * 60 * 24 * 7));
            $resp->setCacheControl("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
            $resp->setHeader("Pragma", "");

            $resp->setBody($body);
            $resp->send();
        } else {
            header("Content-Type: image/jpeg");
            header("Content-Length: " . $size);
            header("Last-Modified: ", date("r", $file['dt']));
            header("Expires: ", date("r", time() + 60 * 60 * 24 * 7));
            header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));

            fpassthru($fh);
        }

        fclose($fh);
    }

}

