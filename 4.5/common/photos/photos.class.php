<?php
global $emps;

require_once $emps->common_module('uploads/uploads.class.php');

class EMPS_Photos
{
    public $up;
    public $ord = 10;

    public $bypass_time = 0;

    public function __construct()
    {
        global $emps;

        $this->up = new EMPS_Uploads;

        $emps->p->register_cleanup(array($this, 'delete_photos_context'));

        $date = $emps->get_setting("bypass_thumbs");
        if($date) {
            $this->bypass_time = $emps->parse_time($date . " 00:00");
        }
    }

    public function thumb_filename($image_id)
    {
        $folder = $this->up->pick_folder($image_id, DT_IMAGE);
        if (!$folder) return false;

        $file_name = $this->up->UPLOAD_PATH . $folder . "/thumb_" . $image_id . "-img.dat";
        return $file_name;
    }

    public function delete_photo($file_id)
    {
        $this->delete_photo_files($file_id);
        $this->up->delete_file($file_id, DT_IMAGE);
    }

    public function delete_photo_files($file_id)
    {
        $tname = $this->thumb_filename($file_id);
        if ($tname)
            unlink($tname);
        $this->cancel_watermark($file_id);
        $this->delete_thumbs($file_id);
    }

    public function ensure_thumb($ra, $size, $opts)
    {
        global $emps;
        $fname = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-img.dat";
        $fname_wm = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-wm.dat";
        $dname = $this->up->UPLOAD_PATH . $ra['folder'] . "/thumb/" . $ra['id'] . "_" . $size . ".dat";

        $orig_name = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-orig.dat";

        $use_wm = $emps->get_setting("emps_watermark_thumbs");

        if($use_wm){
            if (file_exists($fname_wm)) {
                $fname = $fname_wm;
            }
        }else{
            if (file_exists($orig_name)) {
                $fname = $orig_name;
            }
        }

        $thumb_row = $emps->db->get_row("e_thumbs", "size='{$size}' and upload_id = {$ra['id']} limit 1");

        if (!file_exists($dname) || ($this->bypass_time > filemtime($dname)) || !$thumb_row) {
            //error_log("modifying image: ".$ra['id']." ".$emps->form_time($this->bypass_time)." / ".$emps->form_time(filemtime($dname)));
            if (strstr($ra['type'], "jpeg")) {
                $img = imagecreatefromjpeg($fname);
            } elseif (strstr($ra['type'], "png")) {
                $img = imagecreatefrompng($fname);
            } elseif (strstr($ra['type'], "gif")) {
                $img = imagecreatefromgif($fname);
            } else {
                $ra['fname'] = $fname;
                return $ra;
            }

            $z = explode("x", $size);
            $opts = explode(",", $opts);

            $tx = $z[0];
            $ty = $z[1];

            $sx = imagesx($img);
            $sy = imagesy($img);

            if (array_search("auto", $opts) !== FALSE) {
                if ($sx < $sy) {
                    swap($tx, $ty);
                }
            }

            if (array_search("max", $opts) !== FALSE) {
                if ($tx > $sx) {
                    $tx = $sx;
                }
                if ($ty > $sy) {
                    $ty = $sy;
                }
            }

            if (array_search("inner", $opts) !== FALSE) {
                // $px,$py = target size
                // $sx,$sy = current size
                // $wx,$wy = working x,y
                $wx = $sx;
                $wy = $sy;
                if ($wx > $tx) {
                    $wx = $tx;
                    $wy = ($sy / $sx) * $wx;
                }
                if ($wy > $ty) {
                    $wy = $ty;
                    $wx = ($sx / $sy) * $wy;
                }
                $ty = $wy;
                $tx = $wx;
                //			echo "SX,SY,PX,PY = $sx,$sy,$px,$py ";exit();
            }

            $quality = 100;
            foreach($opts as $opt){
                if(mb_substr($opt, 0, 1) == 'q'){
                    $quality = intval(mb_substr($opt, 1));
                }
            }

            $dst = $this->adapt_image($img, $tx, $ty);
            if (PHOTOSET_WATERMARK) {
                $dst = $this->apply_watermark($dst, $tx, $ty);
            }

            imagejpeg($dst, $dname, $quality);

            $emps->db->query("delete from ".TP."e_thumbs where size = '{$size}' and upload_id = {$ra['id']}");

            $_REQUEST = array();
            $_GLOBALS['id'] = "";
            $_REQUEST['upload_id'] = $ra['id'];
            $_REQUEST['size'] = $size;
            $_REQUEST['dt'] = time();
            $emps->db->sql_insert("e_thumbs");

            if (is_resource($dst)) {
                imagedestroy($dst);
            }
            if (is_resource($img)) {
                imagedestroy($img);
            }
        }
        $id = $ra['id'];
        $r = $emps->db->query("select * from " . TP . "e_thumbs where size='$size' and upload_id=$id limit 1");
        $ra = $emps->db->fetch_named($r);
        $ra['fname'] = $dname;

        return $ra;
    }

    public function delete_thumbs($photo_id)
    {
        global $emps;
        $folder = $this->up->pick_folder($photo_id, DT_IMAGE);
        if (!$folder) return false;
        $r = $emps->db->query("select * from " . TP . "e_thumbs where upload_id=$photo_id");
        while ($ra = $emps->db->fetch_named($r)) {
            $tname = $this->up->UPLOAD_PATH . $folder . "/thumb/" . $photo_id . "_" . $ra['size'] . ".dat";
            if (file_exists($tname)) {
                unlink($tname);
            }
        }
        $emps->db->query("delete from " . TP . "e_thumbs where upload_id=$photo_id");
    }

    public function adapt_image($img, $tx, $ty)
    {
        if (!$img) return false;

        $sx = imagesx($img);
        $sy = imagesy($img);

        if ($tx == $sx && $ty == $sy) return $img;

        $ar = $sx / $sy;

        $rate = $this->minval($sx / $tx, $sy / $ty);

        $nsx = $sx / $rate;
        $nsy = $nsx / $ar;

        $stx = 0;
        $sty = 0;

        if ($nsx > $tx) {
            $stx += round(($nsx - $tx) / 2, 0);
            $nsx = $tx;
        }

        if ($nsy > $ty) {
            $sty += round(($nsy - $ty) / 2, 0);
            $nsy = $ty;
        }

        $dst = imagecreatetruecolor($nsx, $nsy);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled($dst, $img, 0, 0, $stx * $rate, $sty * $rate, $nsx, $nsy, $nsx * $rate, $nsy * $rate);
        return $dst;
    }

    public function swap(&$a, &$b)
    {
        $c = $b;
        $b = $a;
        $a = $c;
    }

    public function minval($a, $b)
    {
        if ($a < $b) return $a;
        return $b;
    }

    public function treat_upload($oname, $fname, $ra)
    {
        global $emps;

        if (strstr($ra['type'], "jpeg")) {
            $img = imagecreatefromjpeg($oname);
        } elseif (strstr($ra['type'], "png")) {
            $img = imagecreatefrompng($oname);
        } elseif (strstr($ra['type'], "gif")) {
            $img = imagecreatefromgif($oname);
        } else {
            copy($oname, $fname);
            return;
        }

        $format = $ra['thumb'];
        $x = explode("|", $format);
        $y = explode("x", $x[0]);
        $z = explode("x", $x[1]);

        $tx = $z[0];
        $ty = $z[1];
        $px = $y[0];
        $py = $y[1];

        $sx = imagesx($img);
        $sy = imagesy($img);

        $opts = explode(",", $x[2]);
        if (array_search("auto", $opts) !== FALSE) {
            if ($sx < $sy) {
                $this->swap($px, $py);
                $this->swap($tx, $ty);
            }
        }

        if (array_search("max", $opts) !== FALSE) {
            if ($px > $sx) {
                $px = $sx;
            }
            if ($py > $sy) {
                $py = $sy;
            }
        }
        if (array_search("inner", $opts) !== FALSE) {
            // $px,$py = target size
            // $sx,$sy = current size
            // $wx,$wy = working x,y
            $wx = $sx;
            $wy = $sy;
            if ($wx > $px) {
                $wx = $px;
                $wy = ($sy / $sx) * $wx;
            }
            if ($wy > $py) {
                $wy = $py;
                $wx = ($sx / $sy) * $wy;
            }
            $py = $wy;
            $px = $wx;
//			echo "SX,SY,PX,PY = $sx,$sy,$px,$py ";exit();
        }

        $dst = $this->adapt_image($img, $tx, $ty);

        if (PHOTOSET_WATERMARK) {
            $dst2 = $this->apply_watermark($this->adapt_image($img, $px, $py), $px, $py);
        } else {
            $dst2 = $this->adapt_image($img, $px, $py);
        }

        if ($sx == $px && $sy == $py && !PHOTOSET_WATERMARK) {
        } else {
            imagejpeg($dst2, $oname, 100);
        }

        imagejpeg($dst, $fname, 100);

        $size = filesize($fname);
        $emps->db->query("update " . TP . "e_uploads set size=$size where id=" . $ra['id']);

        if (is_resource($dst2)) {
            imagedestroy($dst2);
        }

        if (is_resource($img)) {
            imagedestroy($img);
        }
        if (is_resource($dst)) {
            imagedestroy($dst);
        }
    }

    public function first_pic($context_id)
    {
        global $emps;
        $r = $emps->db->query("select * from " . TP . "e_uploads where context_id=$context_id order by ord asc limit 1");
        $ra = $emps->db->fetch_named($r);
        if ($ra) {
            $ra = $this->image_extension($ra);
        }
        return $ra;
    }

    public function list_pics($context_id, $limit)
    {
        global $emps;
        $lst = array();
        $sql_limit = "";
        if ($limit) {
            $sql_limit = " limit $limit ";
        }
        $r = $emps->db->query("select * from " . TP . "e_uploads where context_id=$context_id order by ord $sql_limit");
        while ($ra = $emps->db->fetch_named($r)) {
            $ra = $this->image_extension($ra);
            $lst[] = $ra;
        }
        return $lst;
    }

    public function pixel_size($upload_id)
    {
        global $emps;
        $file_name = $this->up->upload_filename($upload_id, DT_IMAGE);
        if (!file_exists($file_name)) return false;
        $s = getimagesize($file_name);
        $psize = $s[0] . "x" . $s[1];
        $emps->db->query("update " . TP . "e_uploads set psize='$psize' where id=$upload_id");
        return $psize;
    }

    public function import_photos($context_id, $data)
    {
        global $SET, $emps;
        $this->up->delete_files_context($context_id);
        $this->delete_photos_context($context_id);

        $SET = array();
        foreach ($data as $pic) {
            $ord = $pic['ord'];
            $type = $pic['type'];
            $name = $pic['filename'];
            $descr = $pic['descr'];
            $md5 = $pic['md5'];
            $size = $pic['size'];
            $url = $pic['url'];

            if (!$url) continue;

            $row = $emps->db->get_row("e_uploads", "md5='$md5'");
            if ($row) {
                $_REQUEST = array();
                $_REQUEST['filename'] = $name;
                $_REQUEST['descr'] = $descr;
                $_REQUEST['type'] = $type;
                $_REQUEST['size'] = $size;
                $_REQUEST['thumb'] = EMPS_PHOTO_SIZE;
                $_REQUEST['ord'] = $ord;
                $emps->db->sql_update("e_uploads", "id=" . $row['id']);

                $file_id = $row['id'];
            } else {
                $_REQUEST = array();
                $_REQUEST['md5'] = $md5;
                $_REQUEST['filename'] = $name;
                $_REQUEST['descr'] = $descr;
                $_REQUEST['type'] = $type;
                $_REQUEST['size'] = $size;
                $_REQUEST['thumb'] = EMPS_PHOTO_SIZE;
                $_REQUEST['context_id'] = $context_id;
                $_REQUEST['ord'] = $ord;
                $emps->db->sql_insert("e_uploads");
                $file_id = $emps->db->last_insert();
            }

            $oname = $this->up->upload_filename($file_id, DT_IMAGE);

            $data = file_get_contents($url);
            file_put_contents($oname, $data);

            $row = $emps->db->get_row("e_uploads", "id=$file_id");
            $tname = $this->thumb_filename($file_id);
            $this->treat_upload($oname, $tname, $row);

        }
    }

    function apply_watermark($img, $x, $y)
    {
        global $emps;

        if (PHOTOSET_WATERMARK) {
            $max = $x;
            if ($max < $y) $max = $y;

            if ($max < EMPS_MIN_WATERMARKED) {
                return $img;
            }

            if ($max >= 3000) {
                $wm = EMPS_SCRIPT_PATH . "/i/watermarks/watermark2000.png";
            } elseif ($max >= 1024) {
                $wm = EMPS_SCRIPT_PATH . "/i/watermarks/watermark1000.png";
            } else {
                $wm = EMPS_SCRIPT_PATH . "/i/watermarks/watermark600.png";
            }

            $wmimg = imagecreatefrompng($wm);

            $sx = imagesx($wmimg);
            $sy = imagesy($wmimg);

            if ($wmimg) {
                $dst = imagecreatetruecolor($x, $y);
                imagecopy($dst, $img, 0, 0, 0, 0, $x, $y);
                imagecopyresampled($dst, $wmimg, $x - ($sx * (1 + EMPS_WATERMARK_DISTANCE)), $y - ($sy + ($sx * (EMPS_WATERMARK_DISTANCE))), 0, 0, $sx, $sy, $sx, $sy);

                if (is_resource($img)) {
                    imagedestroy($img);
                }

                return $dst;
            } else {
                return $img;
            }
        } else {
            return $img;
        }
    }

    public function delete_photos_context($context_id)
    {
        global $emps;
        $r = $emps->db->query("select * from " . TP . "e_uploads where context_id=$context_id");
        while ($ra = $emps->db->fetch_named($r)) {
            $this->up->delete_file($ra['id'], DT_IMAGE);
        }
    }

    public function get_pic_md5()
    {
        global $key;
        $md5 = substr($key, 0, 32);
        return $md5;
    }

    public function image_extension($ra)
    {
        if (strstr($ra['type'], 'jpeg')) {
            $ra['ext'] = "jpg";
        }
        if (strstr($ra['type'], 'jpg')) {
            $ra['ext'] = "jpg";
        }
        if (strstr($ra['type'], 'png')) {
            $ra['ext'] = "png";
        }
        if (strstr($ra['type'], 'gif')) {
            $ra['ext'] = "gif";
        }
        if (strstr($ra['type'], 'svg')) {
            $ra['ext'] = "svg";
        }

        if (!$ra['qual']) {
            $ra['qual'] = 100;
        }

        return $ra;
    }

    public function image_sizes($ra)
    {
        $ps = $this->pixel_size($ra['id']);
        $x = explode("x", $ps);
        $ra['width'] = $x[0];
        $ra['height'] = $x[1];
        return $ra;
    }

    public function download_image($context_id, $url)
    {
        global $emps, $SET;

        $data = file_get_contents($url);
        if ($data === FALSE) {
            return false;
        }

        $type = "image/jpeg";
        $filename = "file.jpg";

        $headers = get_headers($url, 1);

        foreach ($headers as $header) {
            if (stristr($header, "Content-Type")) {
                if (stristr($header, "png")) {
                    $filename = "file.png";
                    $type = "image/png";
                }
                if (stristr($header, "gif")) {
                    $filename = "file.gif";
                    $type = "image/gif";
                }
            }
        }

        if (stristr($url, ".png")) {
            $filename = "file.png";
            $type = "image/png";
        }
        if (stristr($url, ".gif")) {
            $filename = "file.gif";
            $type = "image/gif";
        }

        $path = parse_url($url, PHP_URL_PATH);

        $x = explode("/", $path);
        if (count($x) > 1) {
            $fn = trim($x[count($x) - 1]);
            if ($fn) {
                $filename = $fn;
            }
        }

        $SET = array();
        $SET['md5'] = md5(uniqid(time()));
        $SET['filename'] = $filename;
        $SET['type'] = $type;
        $SET['thumb'] = "1600x1600|100x100|inner";
        $SET['context_id'] = $context_id;
        $SET['ord'] = $this->ord;
        $SET['descr'] = $this->descr;
        $emps->db->sql_insert("e_uploads");
        $file_id = $emps->db->last_insert();

        $oname = $this->up->upload_filename($file_id, DT_IMAGE);
        file_put_contents($oname, $data);

        $row = $emps->db->get_row("e_uploads", "id=$file_id");
        $tname = $this->thumb_filename($file_id);
        $this->treat_upload($oname, $tname, $row);

        $size = filesize($oname);
        $emps->db->query("update " . TP . "e_uploads set size=$size where id=" . $file_id);

//		var_dump($row);echo "\r\n";
        return true;
    }


    function flex_watermark($img)
    {
        global $emps;

        $ox = imagesx($img);
        $oy = imagesy($img);

        $x = $ox;
        $y = $oy;

        $max = $ox;
        if ($max < $oy) $max = $oy;

        $min_watermarked = intval($emps->get_setting("emps_min_watermarked"));
        if(!$min_watermarked){
            $min_watermarked = EMPS_MIN_WATERMARKED;
        }

        $wm_pos = $emps->get_setting("emps_watermark_pos");
        if(!$wm_pos){
            $wm_pos = "br";
        }

        if ($max < $min_watermarked) {
            return $img;
        }

        $tx = $emps->get_setting("emps_watermark_fixed_size");
        if(!$tx){
            $tx = ceil($x / 10);
        }

        $wm = EMPS_SCRIPT_PATH . "/i/watermark.png";

        $wmimg = imagecreatefrompng($wm);

        $sx = imagesx($wmimg);
        $sy = imagesy($wmimg);

        $ty = ($sx / $sy) * $tx;

        $padding = $emps->get_setting("emps_watermark_fixed_padding");
        if(!$padding){
            $padding = 5;
        }

//		echo "sx: $sx, sy: $sy, tx: $tx, ty: $ty, x: $x, y: $y ";exit();

        if ($wmimg) {
            $dst = imagecreatetruecolor($x, $y);
            imagecopy($dst, $img, 0, 0, 0, 0, $x, $y);
            if($wm_pos == 'br'){
                imagecopyresampled($dst, $wmimg, $x - $tx - $padding, $y - $ty - $padding, 0, 0, $tx, $ty, $sx, $sy);
            }
            if($wm_pos == 'bl'){
                imagecopyresampled($dst, $wmimg, $padding, $y - $ty - $padding, 0, 0, $tx, $ty, $sx, $sy);
            }

            if (is_resource($img)) {
                imagedestroy($img);
            }

            return $dst;
        } else {
            return $img;
        }
    }

    public function ensure_tilt($file_id, $angle)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {

            $fname = $this->up->upload_filename($file_id, DT_IMAGE);

            $orig_name = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-orig.dat";
            if (!file_exists($orig_name)) {
                copy($fname, $orig_name);
            }

            if (strstr($ra['type'], "jpeg")) {
                $img = imagecreatefromjpeg($orig_name);
            } elseif (strstr($ra['type'], "png")) {
                $img = imagecreatefrompng($orig_name);
            } elseif (strstr($ra['type'], "gif")) {
                $img = imagecreatefromgif($orig_name);
            } else {
                return;
            }

            $white = imagecolorallocate($img, 255, 255, 255);

            imagesetinterpolation($img, IMG_BICUBIC);

            $sx = imagesx($img);
            $sy = imagesy($img);


            $dst = imagerotate($img, $angle, $white);
            if ($dst !== false) {

                $dsx = imagesx($dst);
                $dsy = imagesy($dst);

                $diffx = abs(sin(deg2rad($angle))) * $sy;
                $diffy = abs(sin(deg2rad($angle))) * $sx;

                $rect = array();
                $rect['x'] = $diffx;
                $rect['y'] = $diffy;
                $rect['width'] = $dsx - $diffx * 2;
                $rect['height'] = $dsy - $diffy * 2;

                $dst2 = imagecrop($dst, $rect);

                $emps->db->query("update " . TP . "e_uploads set dt = " . time() . " where id = " . $ra['id']);

                imagejpeg($dst2, $fname, 100);

                if (is_resource($dst)) {
                    imagedestroy($dst);
                }
                if (is_resource($dst2)) {
                    imagedestroy($dst2);
                }

            }
            if (is_resource($img)) {
                imagedestroy($img);
            }

            $this->delete_thumbs($file_id);
        }
    }

    public function ensure_watermark($file_id)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {

            $fname = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-img.dat";
            if (strstr($ra['type'], "jpeg")) {
                $img = imagecreatefromjpeg($fname);
            } elseif (strstr($ra['type'], "png")) {
                $img = imagecreatefrompng($fname);
            } elseif (strstr($ra['type'], "gif")) {
                $img = imagecreatefromgif($fname);
            } else {
                return;
            }


            $dst = $this->flex_watermark($img);

            $wmname = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-wm.dat";

//			dump($img);
//			dump($dst);exit();			
            imagejpeg($dst, $wmname, 100);

            if (is_resource($img)) {
                imagedestroy($img);
            }
            if (is_resource($dst)) {
                imagedestroy($dst);
            }

            $emps->db->query("update " . TP . "e_uploads set wmark = 1, dt = " . time() . " where id = " . $file_id);
        }
    }

    public function cancel_watermark($file_id)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {

            $wmname = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-wm.dat";
            if (file_exists($wmname)) {
                unlink($wmname);
            }

            $emps->db->query("update " . TP . "e_uploads set wmark = 0 where id = " . $file_id);
        }
    }

    public function cancel_tilt($file_id)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {
            $fname = $this->up->upload_filename($file_id, DT_IMAGE);

            $orig_name = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-orig.dat";
            if (file_exists($orig_name)) {
                copy($orig_name, $fname);
            }
        }
    }

    public function adapt_image_16x9($img, $tx, $ty, $mode)
    {
        if (!$img) {
            return false;
        }

        $sx = imagesx($img);
        $sy = imagesy($img);

        if ($tx == $sx && $ty == $sy) {
            return false;
        }

        $topy = 0;
        if ($mode == "bottom") {
            $topy = $sy - $ty;
        }
        if ($mode == "center") {
            $topy = ($sy - $ty) / 2;
        }
        if ($mode == "optimal") {
            $topy = ($sy * 0.33) - ($ty / 2);
        }

        if ($topy < 0) {
            $topy = 0;
        }
        if ($topy > ($sy - $ty)) {
            $topy = $sy - $ty;
        }

        if ($tx > $sx) {
            $tx = $sx;
        }
        if ($ty > $sy) {
            $ty = $sy;
        }
        if ($tx == $sx && $ty == $sy) {
            return false;
        }

        $dst = imagecreatetruecolor($tx, $ty);

        imagecopyresampled($dst, $img, 0, 0, 0, $topy, $tx, $ty, $tx, $ty);
        return $dst;
    }

    public function resize_16x9($file_id, $mode)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {

            $fname = $this->up->upload_filename($file_id, DT_IMAGE);

            $orig_name = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-orig.dat";
            if (!file_exists($orig_name)) {
                copy($fname, $orig_name);
            }

            if (strstr($ra['type'], "jpeg")) {
                $img = imagecreatefromjpeg($orig_name);
            } elseif (strstr($ra['type'], "png")) {
                $img = imagecreatefrompng($orig_name);
            } elseif (strstr($ra['type'], "gif")) {
                $img = imagecreatefromgif($orig_name);
            } else {
                return;
            }

            $sx = imagesx($img);
            $sy = imagesy($img);

            $tx = $sx;
            $ty = round(($sx / 16) * 9, 0);

            $qual = $ra['qual'];
            if (!$qual) {
                $qual = 100;
            }

            $dst = $this->adapt_image_16x9($img, $tx, $ty, $mode);
            if ($dst !== false) {

                imagejpeg($dst, $fname, $qual);
                imagejpeg($dst, $orig_name, 100);

                if (is_resource($dst)) {
                    imagedestroy($dst);
                }
            }
            if (is_resource($img)) {
                imagedestroy($img);
            }

            $this->delete_thumbs($file_id);
        }

    }

    public function set_quality($file_id, $mode)
    {
        global $emps;

        $ra = $emps->db->get_row("e_uploads", "id = " . $file_id);
        if ($ra) {
            $emps->db->query("update " . TP . "e_uploads set qual = $mode where id = " . $file_id);
            $ra['qual'] = $mode;

            $fname = $this->up->upload_filename($file_id, DT_IMAGE);

            $orig_name = $this->up->UPLOAD_PATH . $ra['folder'] . "/" . $ra['id'] . "-orig.dat";
            if (!file_exists($orig_name)) {
                copy($fname, $orig_name);
            }

            if (strstr($ra['type'], "jpeg")) {
                $img = imagecreatefromjpeg($orig_name);
            } elseif (strstr($ra['type'], "png")) {
                $img = imagecreatefrompng($orig_name);
            } elseif (strstr($ra['type'], "gif")) {
                $img = imagecreatefromgif($orig_name);
            } else {
                return;
            }

            imagejpeg($img, $fname, $mode);

            $size = filesize($fname);
            $emps->db->query("update " . TP . "e_uploads set size=$size where id=" . $file_id);

            if (is_resource($img)) {
                imagedestroy($img);
            }
        }
    }
}

