<?php
global $emps;

require_once $emps->common_module('xml.class.php');
require_once $emps->common_module('photos/photos.class.php');

class EMPS_Videos
{
    public $p;

    public $table_name = "emps_videos";

    public function __construct()
    {
        global $emps;

        $this->p = new EMPS_Photos;

        $emps->p->register_cleanup(array($this, 'delete_videos_context'));
    }

    function parse_video_url($url)
    {
        $a = array();

        $x = explode('youtube.com/watch?', $url, 2);
        if ($x[1]) {
            $y = explode('&', $x[1]);
            while (list($n, $v) = each($y)) {
                $z = explode('=', $v);
                if ($z[0] == 'v') {
                    $a['youtube_id'] = $z[1];
                }
            }
        } else {
            $x = explode("://vimeo.com/", $url, 2);
            if ($x[1]) {
                $a['vimeo_id'] = $x[1];
            } else {
                $x = explode("://rutube.ru/video/", $url, 2);
                if ($x[1]) {
                    $a['rutube_id'] = $x[1];
                } else {
                    $x = explode("//www.screencast.com/", $url, 2);
                    if ($x[1]) {
                        $xx = explode(" src=\"", $url, 2);
                        $xxx = explode("\"", $xx[1], 2);
                        $a['screencast_url'] = $xxx[0];
                        $a['name'] = $xxx[0];
                    }
                }
            }
        }

        return $a;
    }

    function covtime($youtube_time)
    {
        $start = new DateTime('@0'); // Unix epoch
        $start->add(new DateInterval($youtube_time));
        return $start->getTimestamp();
    }

    function process_video($video_id)
    {
        global $emps;

        $ctx = $emps->p->get_context(DT_VIDEO, 1, $video_id);

        $params = array();
        $params['query'] = array('_id' => $video_id);
        $video = $emps->db->get_row($this->table_name, $params);

//        echo $video_id;
//        dump($video); exit;

        if ($video['youtube_id'] && defined('GOOGLE_KEY_YOUTUBE')) {

            $url = "https://www.googleapis.com/youtube/v3/videos?key=" . GOOGLE_KEY_YOUTUBE . "&part=snippet,contentDetails&id=" . $video['youtube_id'];
            $data = file_get_contents($url);
            $json = json_decode($data, true);

            $snip = $json['items'][0]['snippet'];
            $data = $json['items'][0]['contentDetails'];
//			dump($json['items'][0]);
//			dump($data);
            $SET['name'] = $snip['title'];
            $SET['description'] = $snip['description'];
            $SET['duration'] = $this->covtime($data['duration']);

            $params = array();
            $params['query'] = array("_id" => $video_id);
            $params['update'] = array('$set' => $SET);
            $emps->db->update_one($this->table_name, $params);

            $img = $snip['thumbnails']['standard'];
            if (!$img) {
                $img = $snip['thumbnails']['high'];
                if (!$img) {
                    $img = $snip['thumbnails']['medium'];
                    if (!$img) {
                        $img = $snip['thumbnails']['default'];
                    }
                }
            }

            if ($img) {
                $this->p->delete_photos_context($ctx);

                $ord = 10;

                $data = file_get_contents($img['url']);

                if ($data) {


                    $thumb_filename = tempnam($this->p->tmppath, "emps_video_thumb");
                    file_put_contents($thumb_filename, $data);

                    $data = [];
                    $data['ut'] = 'i';
                    $data['uniq_md5'] = md5(uniqid(time().$img['url']));
                    $data['filename'] = $data['uniq_md5']."-".$data['ut'];
                    $data['orig_filename'] = "thumbnail.jpg";
                    $data = $this->p->up->file_extension($data);
                    $data['context_id'] = $emps->db->oid($ctx);
                    $data['content_type'] = "image/jpeg";
                    $data['user_id'] = $emps->auth->USER_ID;
                    $data['qual'] = 100;
                    $data['thumb'] = EMPS_PHOTO_SIZE;
                    $data['ord'] = $ord;
                    $ord += 10;

                    $file_id = $this->p->new_photo($thumb_filename, $data);

                }

            }
        }

        if ($video['vimeo_id']) {
            $raw = file_get_contents("http://vimeo.com/api/v2/video/" . $video['vimeo_id'] . ".json");
            $data = json_decode($raw, true);

            $data = $data[0];

            $SET = array();
            $SET['name'] = $data['title'];
            $SET['description'] = $data['description'];
            $SET['duration'] = $data['duration'];
            $SET['width'] = $data['width'];
            $SET['height'] = $data['height'];

            $params = array();
            $params['query'] = array("_id" => $video_id);
            $params['update'] = array('$set' => $SET);
            $emps->db->update_one($this->table_name, $params);

            $image = file_get_contents($data['thumbnail_large']);


            if ($image) {
                $this->p->delete_photos_context($ctx);

                $ord = 10;

                if ($image) {


                    $thumb_filename = tempnam($this->p->tmppath, "emps_video_thumb");
                    file_put_contents($thumb_filename, $image);

                    $data = [];
                    $data['ut'] = 'i';
                    $data['uniq_md5'] = md5(uniqid(time().$img['url']));
                    $data['filename'] = $data['uniq_md5']."-".$data['ut'];
                    $data['orig_filename'] = "thumbnail.jpg";
                    $data = $this->p->up->file_extension($data);
                    $data['context_id'] = $emps->db->oid($ctx);
                    $data['content_type'] = "image/jpeg";
                    $data['user_id'] = $emps->auth->USER_ID;
                    $data['qual'] = 100;
                    $data['thumb'] = EMPS_PHOTO_SIZE;
                    $data['ord'] = $ord;

                    $file_id = $this->p->new_photo($thumb_filename, $data);

                }

            }


        }

        if ($video['rutube_id']) {
            $raw = file_get_contents("http://rutube.ru/api/video/" . $video['rutube_id'] . "/?format=json");
            $data = json_decode($raw, true);

//			dump($data);exit();

            $SET = array();
            $SET['name'] = $data['title'];
            $SET['description'] = $data['description'];
            $SET['duration'] = $data['duration'];
            $SET['embed_url'] = $data['embed_url'];

            $params = array();
            $params['query'] = array("_id" => $video_id);
            $params['update'] = array('$set' => $SET);
            $emps->db->update_one($this->table_name, $params);

            $image = file_get_contents($data['thumbnail_url']);

            if ($image) {
                $this->p->delete_photos_context($ctx);

                $ord = 10;

                if ($image) {


                    $thumb_filename = tempnam($this->p->tmppath, "emps_video_thumb");
                    file_put_contents($thumb_filename, $image);

                    $data = [];
                    $data['ut'] = 'i';
                    $data['uniq_md5'] = md5(uniqid(time().$img['url']));
                    $data['filename'] = $data['uniq_md5']."-".$data['ut'];
                    $data['orig_filename'] = "thumbnail.jpg";
                    $data = $this->p->up->file_extension($data);
                    $data['context_id'] = $emps->db->oid($ctx);
                    $data['content_type'] = "image/jpeg";
                    $data['user_id'] = $emps->auth->USER_ID;
                    $data['qual'] = 100;
                    $data['thumb'] = EMPS_PHOTO_SIZE;
                    $data['ord'] = $ord;

                    $file_id = $this->p->new_photo($thumb_filename, $data);

                }

            }
        }

    }

    function delete_video($id)
    {
        global $emps;
        $ictx = $emps->p->get_context(DT_VIDEO, 1, $emps->db->oid($id));

        $emps->p->delete_context($ictx);

        $params = [];
        $params['query'] = ['_id' => $emps->db->oid($id)];
        $emps->db->delete_one($this->table_name, $params);

    }

    function delete_videos_context($context_id)
    {
        global $emps;

        $lst = $this->list_videos($context_id);
        foreach($lst as $ra){
            $this->delete_video($ra['_id']);
        }
    }

    function convert_duration($duration)
    {
        $h = (integer)floor($duration / 3600);
        $m = (integer)floor(($duration - $h * 3600) / 60);
        $s = $duration - $h * 3600 - $m * 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    function list_videos($context_id)
    {
        global $emps;

        $params = [];
        $params['query'] = ['context_id' => $emps->db->oid($context_id)];
        $cursor = $emps->db->find($this->table_name, $params);
        $lst = [];
        foreach($cursor as $ra){

            $ra['dur'] = $this->convert_duration($ra['duration']);
            $ctx = $emps->p->get_context(DT_VIDEO, 1, $ra['_id']);
            $ra['pic'] = $this->p->first_pic($ctx);
            $ra['time'] = $emps->form_time($ra['cdt']);

            $lst[] = $ra;
        }

        return $lst;

    }

    function count_videos($context_id)
    {
        global $emps;

        $params = [];
        $params['query'] = ['context_id' => $emps->db->oid($context_id)];

        $count = $emps->db->count_rows($this->table_name, $params);

        return $count;
    }
}
