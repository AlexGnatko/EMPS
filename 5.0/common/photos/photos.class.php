<?php
global $emps;

require_once $emps->common_module('uploads/uploads.class.php');

class EMPS_Photos
{
    public $up;
    public $ord = 10;

    public $tmppath = "";

    public function __construct()
    {
        global $emps;

        $this->tmppath = EMPS_SCRIPT_PATH . EMPS_UPLOAD_SUBFOLDER;

        $this->up = new EMPS_Uploads;

        $emps->p->register_cleanup(array($this, 'delete_photos_context'));
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
        $this->up->delete_file($file_id);
    }

    public function delete_photo_files($file_id)
    {
        $this->cancel_watermark($file_id);
        $this->delete_thumbs($file_id);
    }

    public function ensure_thumb($ra, $size, $thumb_opts)
    {
        global $emps;

        //$thumb_data['filename'] = $thumb_data['md5']."-".$thumb_data['t']."-".$thumb_dims."-".$img_opts;
        $thumb_gfs = $ra['uniq_md5']."-t-".$size."-".$thumb_opts;

        $photo_id = $emps->db->oid($ra['_id']);

        $lst = $this->up->list_files_ex(['photo__id' => $photo_id, 'ut' => 't', 'filename' => $thumb_gfs], ['limit' => 1, 'sort' => ['ord' => 1, '_id' => 1]]);
        if(count($lst) > 0){
            $thumb = $lst[0];
            return $thumb['_id'];
        }

        $main_fname = tempnam($this->tmppath, "emps_photo_main");
        $thumb_fname = tempnam($this->tmppath, "emps_photo_thumb");

        $file = fopen($main_fname, 'wb');

        $this->up->bucket->downloadToStream($photo_id, $file);
        fclose($file);

        if (strstr($ra['content_type'], "jpeg")) {
            $img = imagecreatefromjpeg($main_fname);
        } elseif (strstr($ra['content_type'], "png")) {
            $img = imagecreatefrompng($main_fname);
        } elseif (strstr($ra['content_type'], "gif")) {
            $img = imagecreatefromgif($main_fname);
        } else {
            /* PASSTHRU */
            return $ra;
        }

        $z = explode("x", $size);
        $opts = explode(",", $thumb_opts);

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

        $dst = $this->adapt_image($img, $tx, $ty);
        if (PHOTOSET_WATERMARK) {
            $dst = $this->apply_watermark($dst, $tx, $ty);
        }

        imagejpeg($dst, $thumb_fname, 100);

        // INSERT
        $thumb_data = [];
        $emps->copy_values($thumb_data, $ra, "uniq_md5,context_id,orig_filename,ord,user_id");

        $thumb_data['context_id'] = $emps->db->oid($thumb_data['context_id']);

        $thumb_data['ut'] = "t";
        $thumb_data['filename'] = $thumb_gfs;
        $thumb_data['orig_filename'] = $thumb_data['orig_filename']."-".$size."-".$thumb_opts.".jpg";
        $thumb_data['content_type'] = "image/jpeg";
        $thumb_data['ext'] = "jpg";
        $thumb_data['qual'] = 100;
        $thumb_data['photo__id'] = $emps->db->oid($ra['_id']);

        $thumb_file_id = $this->up->new_file($thumb_fname, $thumb_data);

        unlink($thumb_fname);
        unlink($main_fname);

        if (is_resource($dst)) {
            imagedestroy($dst);
        }
        if (is_resource($img)) {
            imagedestroy($img);
        }

        return $thumb_file_id;
    }

    public function delete_thumbs($photo_id)
    {
        global $emps;

        $photo_id = $emps->db->oid($photo_id);

        $lst = $this->up->list_files_ex(['photo__id' => $photo_id], ['limit' => 0]);
        foreach($lst as $file){
            $this->up->delete_file($emps->db->oid($file['_id']));
        }
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

    public function new_photo($oname, $data)
    {
        global $emps;

        $main_fname = tempnam($this->tmppath, "emps_photo_main");
        $thumb_fname = tempnam($this->tmppath, "emps_photo_thumb");

        if (strstr($data['content_type'], "jpeg")) {
            $img = imagecreatefromjpeg($oname);
        } elseif (strstr($data['content_type'], "png")) {
            $img = imagecreatefrompng($oname);
        } elseif (strstr($data['content_type'], "gif")) {
            $img = imagecreatefromgif($oname);
        } else {
            /* TODO: PASSTHRU */
        }

        $format = $data['thumb'];
        $x = explode("|", $format);
        $y = explode("x", $x[0]);
        $z = explode("x", $x[1]);
        $image_dims = $x[0];
        $thumb_dims = $x[1];
        $img_opts = $x[2];

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
            copy($oname, $main_fname);
        } else {

            if (strstr($data['content_type'], "jpeg")) {
                imagejpeg($dst2, $main_fname, 100);
            } elseif (strstr($data['content_type'], "png")) {
                imagepng($dst2, $main_fname, 100);
            } elseif (strstr($data['content_type'], "gif")) {
                imagegif($dst2, $main_fname);
            }
        }

        imagejpeg($dst, $thumb_fname, 100);

        $data['width'] = round($px, 0);
        $data['height'] = round($py, 0);

        $file_id = $this->up->new_file($main_fname, $data);

        $thumb_data = [];
        $emps->copy_values($thumb_data, $data, "uniq_md5,context_id,ord");
        $thumb_data['context_id'] = $emps->db->oid($thumb_data['context_id']);
        $thumb_data = $data;
        $thumb_data['ut'] = "t";
        $thumb_data['filename'] = $thumb_data['uniq_md5']."-".$thumb_data['ut']."-".$thumb_dims."-".$img_opts;
        $thumb_data['orig_filename'] = $thumb_data['orig_filename']."-".$thumb_dims."-".$img_opts.".jpg";
        $thumb_data['content_type'] = "image/jpeg";
        $thumb_data['thumb'] = $thumb_dims."|".$img_opts;
        $thumb_data['photo__id'] = $emps->db->oid($file_id);

        $thumb_file_id = $this->up->new_file($thumb_fname, $thumb_data);

        unlink($main_fname);
        unlink($thumb_fname);

        if (is_resource($dst2)) {
            imagedestroy($dst2);
        }

        if (is_resource($img)) {
            imagedestroy($img);
        }
        if (is_resource($dst)) {
            imagedestroy($dst);
        }

        return $file_id;
    }

    public function first_pic($context_id)
    {
        global $emps;

        $lst = $this->up->list_files_ex(['context_id' => $emps->db->oid($context_id), 'ut' => 'i'], ['limit' => 1, 'sort' => ['ord' => 1]]);
        if(count($lst) > 0){
            return $lst[0];
        }

        return [];
    }

    public function list_pics($context_id, $limit)
    {
        global $emps;
        return $this->up->list_files_ex(['context_id' => $emps->db->oid($context_id), 'ut' => 'i'], ['limit' => $limit, 'sort' => ['ord' => 1]]);
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
                $_REQUEST['context_id'] = $emps->db->oid($context_id);
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
        $lst = $this->up->list_files($context_id, 'i', 0);
        foreach($lst as $file){
            $this->delete_photo($file['_id']);
        }
    }

    public function get_pic_md5()
    {
        global $key;
        $md5 = substr($key, 0, 34);
        return $key;
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

        if ($max < EMPS_MIN_WATERMARKED) {
            return $img;
        }

        $tx = ceil($x / 10);

        $wm = EMPS_SCRIPT_PATH . "/i/watermark.png";

        $wmimg = imagecreatefrompng($wm);

        $sx = imagesx($wmimg);
        $sy = imagesy($wmimg);

        $ty = ($sx / $sy) * $tx;

//		echo "sx: $sx, sy: $sy, tx: $tx, ty: $ty, x: $x, y: $y ";exit();

        if ($wmimg) {
            $dst = imagecreatetruecolor($x, $y);
            imagecopy($dst, $img, 0, 0, 0, 0, $x, $y);
            imagecopyresampled($dst, $wmimg, $x - $tx, $y - $ty, 0, 0, $tx, $ty, $sx, $sy);

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
        return;

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

        $photo_id = $emps->db->oid($file_id);

        $lst = $this->up->list_files_ex(['_id' => $photo_id, 'ut' => 'i'], ['limit' => 1, 'sort' => ['ord' => 1, '_id' => 1]]);
        if(count($lst) > 0){
            $pic = $lst[0];
        }
        if($pic){

            $lst = $this->up->list_files_ex(['photo__id' => $photo_id, 'ut' => 'q'], ['limit' => 1, 'sort' => ['ord' => 1, '_id' => 1]]);
            if(count($lst) > 0){
                foreach($lst as $eq){
                    $this->up->delete_file($eq['_id']);
                }
            }

            $file_gfs = $pic['uniq_md5']."-q-".$mode;
            $main_fname = tempnam($this->tmppath, "emps_photo_main");
            $qual_fname = tempnam($this->tmppath, "emps_photo_qual");

            $file = fopen($main_fname, 'wb');

            $this->up->bucket->downloadToStream($photo_id, $file);
            fclose($file);


            if (strstr($pic['content_type'], "jpeg")) {
                $img = imagecreatefromjpeg($main_fname);
            } elseif (strstr($pic['type'], "png")) {
                $img = imagecreatefrompng($main_fname);
            } elseif (strstr($pic['type'], "gif")) {
                $img = imagecreatefromgif($main_fname);
            } else {
                return;
            }

            imagejpeg($img, $qual_fname, $mode);

            $size = filesize($qual_fname);

            // INSERT
            $thumb_data = [];
            $emps->copy_values($thumb_data, $pic, "uniq_md5,context_id,orig_filename,user_id");

            $thumb_data['context_id'] = $emps->db->oid($thumb_data['context_id']);

            $thumb_data['ut'] = "q";
            $thumb_data['filename'] = $file_gfs;
            $thumb_data['orig_filename'] = $thumb_data['orig_filename']."-q-".$mode.".jpg";
            $thumb_data['content_type'] = "image/jpeg";
            $thumb_data['ext'] = "jpg";
            $thumb_data['qual'] = $mode;
            $thumb_data['photo__id'] = $emps->db->oid($pic['_id']);

            $qual_file_id = $this->up->new_file($qual_fname, $thumb_data);

            $this->up->update_file($pic['_id'], ['view__id' => $qual_file_id, 'qual' => $mode]);

            if (is_resource($img)) {
                imagedestroy($img);
            }

            unlink($main_fname);
            unlink($qual_fname);

            return $qual_file_id;
        }

        return false;
    }
}

