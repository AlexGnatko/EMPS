<?php
$emps->no_smarty = true;

require_once('HTTP/Download.php');

if ($key) {
    $file = $emps->db->get_row("e_files", "md5='$key'");
    header("Content-Type: audio/mpeg");

    require_once($emps->common_module('uploads/uploads.class.php'));
    $up = new EMPS_Uploads;

    $fname = $up->upload_filename($file['id'], DT_FILE);

    $f = fopen($fname, "rb");
    $params = array();
    $params['resource'] = $f;
    $params['contenttype'] = "audio/mpeg";

    HTTP_Download::staticSend($params);

    fclose($f);
}

?>