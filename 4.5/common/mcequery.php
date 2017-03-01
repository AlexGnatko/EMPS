<?php
require_once($emps->common_module('videos/videos.class.php'));

$photos = new EMPS_Photos;
$videos = new EMPS_Videos;

if ($emps->auth->credentials("users")) {
    $smarty->assign("lang", $emps->lang);

    $emps->no_smarty = true;

    $emps->loadvars();

    $context_id = intval($key);
    $mode = $start;
    $subcode = $ss;

    $x = explode("-", $subcode);
    $id = intval($x[0]);
    $subcode = $x[1];

    $smarty->assign("context_id", $context_id);
    $smarty->assign("mode", $mode);

    if ($mode == 'imgbyid') {
        if ($id) {
            $pic = $emps->db->get_row("e_uploads", "id = " . $id);
            if ($pic) {
                $pic = $photos->image_extension($pic);
                $pic = $photos->image_sizes($pic);
                $smarty->assign("pic", $pic);
            }
        }
        if ($subcode == 'body' || $subcode == 'freepic') {


            if ($subcode == 'freepic') {
                $smarty->assign("mode", "freepic");
                $smarty->display("db:mce/imgbyid");
            } else {
                $smarty->assign("mode", "pic");
                $smarty->display("db:mce/imgbyid");
            }
        } elseif ($subcode == 'freefooter') {
            $smarty->assign("back", 1);
            $smarty->assign("onclick", "tmce_load('/mcequery/" . $context_id . "/imgbyid/" . $pic['id'] . "-body/'," .
                "'','/mcequery/" . $context_id . "/imgbyid/" . $pic['id'] . "-picfooter/')");

            $smarty->assign("mode", "footer");
            $smarty->display("db:mce/imgbyid");

        } elseif ($subcode == 'footer' || $subcode == 'picfooter') {
            if ($subcode == 'picfooter') {
                $smarty->assign("back", 1);
                $smarty->assign("onclick", "tmce_load('/mcequery/" . $context_id . "/imgbyid/-list/'," .
                    "'','/mcequery/" . $context_id . "/imgbyid/-footer/')");
            }

            $smarty->assign("mode", "footer");
            $smarty->display("db:mce/imgbyid");
        } elseif ($subcode == 'list') {
            $lst = $photos->list_pics($context_id, 1000);

            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $smarty->display("db:mce/imgbyid");

        } else {
            $tmce = array();

            $smarty->assign("mode", "title");
            $tmce['title'] = $smarty->fetch("db:mce/imgbyid");

            $lst = $photos->list_pics($context_id, 1000);

            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $tmce['body'] = $smarty->fetch("db:mce/imgbyid");

            $smarty->assign("mode", "footer");
            $tmce['footer'] = $smarty->fetch("db:mce/imgbyid");

            $smarty->assign("tmce", $tmce);
        }
    }

    if ($mode == 'vidbyid') {
        if ($subcode == 'list') {
        } else {
            $tmce = array();

            $smarty->assign("mode", "title");
            $tmce['title'] = $smarty->fetch("db:mce/vidbyid");

            $lst = $videos->load_videos($context_id, 0);

//			dump($lst);
            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $tmce['body'] = $smarty->fetch("db:mce/vidbyid");

            $smarty->assign("mode", "footer");
            $tmce['footer'] = $smarty->fetch("db:mce/vidbyid");

            $smarty->assign("tmce", $tmce);
        }
    }

    if ($mode == 'audiobyid') {
        if ($subcode == 'list') {
        } else {
            $tmce = array();

            $smarty->assign("mode", "title");
            $tmce['title'] = $smarty->fetch("db:mce/audiobyid");

            $lst = $photos->up->list_files($context_id, 0);

//			dump($lst);
            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $tmce['body'] = $smarty->fetch("db:mce/audiobyid");

            $smarty->assign("mode", "footer");
            $tmce['footer'] = $smarty->fetch("db:mce/audiobyid");

            $smarty->assign("tmce", $tmce);
        }
    }


    if ($mode == 'montage') {
        if ($subcode == 'list') {
        } else {
            $tmce = array();

            $smarty->assign("mode", "title");
            $tmce['title'] = $smarty->fetch("db:mce/montage");

            $lst = $photos->list_pics($context_id, 1000);

            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $tmce['body'] = $smarty->fetch("db:mce/montage");

            $smarty->assign("mode", "footer");
            $tmce['footer'] = $smarty->fetch("db:mce/montage");

            $smarty->assign("tmce", $tmce);
        }
    }

    if ($mode == 'photoreport') {
        if ($subcode == 'list') {
        } else {
            $tmce = array();

            $smarty->assign("mode", "title");
            $tmce['title'] = $smarty->fetch("db:mce/photoreport");

            $lst = $photos->list_pics($context_id, 1000);

            $smarty->assign("lst", $lst);

            $smarty->assign("mode", "body");
            $tmce['body'] = $smarty->fetch("db:mce/photoreport");

            $smarty->assign("mode", "footer");
            $tmce['footer'] = $smarty->fetch("db:mce/photoreport");

            $smarty->assign("tmce", $tmce);
        }
    }

    if (!$subcode) {
        $smarty->display("db:mce/wrapper");
    }
}

?>