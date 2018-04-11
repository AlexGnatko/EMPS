<?php
global $emps;

function smarty_plugin_sapecontext($params)
{
    global $sape_context;
    if (defined('_SAPE_USER')) {
        $txt = $params['text'];
        return $sape_context->replace_in_text_segment($txt);
    } else {
        return $params['text'];
    }
}

function smarty_plugin_sapelinks($params)
{
    global $sape;
    if (defined('_SAPE_USER')) {
        $count = $params['count'];
        if ($count > 0) {
            return $sape->return_links($count);
        } else {
            return $sape->return_links();
        }
    }
    return "";
}

function smarty_plugin_sapearticles($params)
{
    global $sape_article;
    if (defined('_SAPE_USER')) {
        $count = $params['count'];
        if ($count > 0) {
            return $sape_article->return_announcements($count);
        } else {
            return $sape_article->return_announcements();
        }
    }
    return "";
}

function smarty_common_photoreport($params)
{
    global $smarty, $emps, $sp_photos;

    require_once($emps->common_module('photos/photos.class.php'));

    if (!isset($sp_photos)) {
        $sp_photos = new EMPS_Photos;
    }

    $context_id = $params['context'];
    $ps = array();

    if (!$context_id) {
        $list = $params['list'];
        $x = explode(",", $list);
        $cl = "";
        foreach ($x as $v) {
            $pic = $emps->db->get_row("e_uploads", "id = " . intval($v));
            $cl .= "." . $v;
            if ($pic) {
                $pic = $sp_photos->explain_pic($pic);
                $ps[] = $pic;
            }
        }
        $smarty->assign("rel", "rel" . md5($cl));
    } else {
        $ps = $sp_photos->list_pics($context_id, 1000);
        $smarty->assign("rel", "rel" . md5($context_id));
    }

    $smarty->assign("ctx", $context_id);

    $smarty->assign("vert", $params['vert']);
    $smarty->assign("fullpic", $params['fullpic']);
    $smarty->assign("size", $params['size']);

    $smarty->assign("pset", $ps);

}

function smarty_plugin_montage($params)
{
    global $smarty, $emps, $sp_photos;

    smarty_common_photoreport($params);

    return $smarty->fetch("db:photos/montage");
}

function smarty_plugin_photoreport($params)
{
    global $smarty, $emps, $sp_photos;

    smarty_common_photoreport($params);

    return $smarty->fetch("db:photos/photoreport");
}

function parse_smarty_params($s)
{
    $params = array();
    $s = htmlspecialchars_decode($s);
    $x = explode("|", $s);
    while (list($n, $v) = each($x)) {
        $v = trim($v);
        $y = explode("=", $v);
        $params[trim($y[0])] = $y[1];
    }
    return $params;
}

function smarty_plugin_video($params)
{
    global $smarty, $emps;

    $id = $params['id'];
    $mctx = $emps->p->get_context(DT_VIDEO, 1, $id);

    $video = $emps->db->get_row("e_videos", "id=$id");
    if ($video) {
        $video = $emps->p->read_properties($video, $mctx);

        $smarty->assign("video", $video);
        return $smarty->fetch("db:videos/videocon");
    }
}

function smarty_plugin_audio($params)
{
    global $smarty, $emps;

    $id = $params['id'];
    $mctx = $emps->p->get_context(DT_FILE, 1, $id);

    $audio = $emps->db->get_row("e_files", "id=$id");
    if ($audio) {
        $audio = $emps->p->read_properties($audio, $mctx);

        $smarty->assign("audio", $audio);
        return $smarty->fetch("db:uploads/audiocon");
    }
}

function smarty_plugin_downloads($params)
{
    global $smarty, $emps, $up;

    require_once($emps->common_module('uploads/uploads.class.php'));

    if (!isset($sp_up)) {
        $sp_up = new EMPS_Uploads;
    }

    $context_id = $params['context'];
    if ($context_id) {
        $lst = $sp_up->list_files($context_id, 1000);
        $smarty->assign("filelist", $lst);
        return $smarty->fetch("db:page/filelist");
    }

    $list = $params['list'];
    if ($list) {
        $lst = array();
        $xx = explode(",", $list);
        while (list($n, $v) = each($xx)) {
            $id = $v + 0;
            $ra = $emps->db->get_row("e_files", "id = " . $id);
            $ra['fsize'] = format_size($ra['size']);
            $lst[] = $ra;

        }

        $smarty->assign("filelist", $lst);
        return $smarty->fetch("db:page/filelist");
    }
}

$fn = $emps->common_module('config/smarty/plugins.php');
if (file_exists($fn)) {
    require_once $fn;
}

