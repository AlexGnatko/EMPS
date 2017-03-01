<?php
$emps->no_smarty = true;

$pkey = intval($_GET['pkey']);
$number = "0000000";
if (!$pkey) {
    $pkey = 0;
}

$ra = $emps->db->get_row("e_pincode", "access = $pkey");
$number = $ra['pincode'];
$dt = $ra['dt'];
if (!$number) {
    $number = "0000000";
}

header("Content-Type: image/png");
$im = imagecreatetruecolor(7 * 36, 48);
imagealphablending($im, true);

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $transparent);

$s = strval($number);
$l = strlen($s);

$md5 = md5($dt);

for ($i = 0; $i < $l; $i++) {
    $c = $s{$i};
    $v1 = $md5{$i * 2};
    $v2 = $md5{$i * 2 + 1};
    $v1d = hexdec($v1);
    $v2d = hexdec($v2);
    $variant = ($v1d % 3) + 1;
    $direction = ($v2d % 3);
    $fn = EMPS_SCRIPT_PATH . "/i/dig/" . $variant . "/" . $c . ".png";
    if (!file_exists($fn)) {
        $fn = EMPS_PATH_PREFIX . "/i/dig/" . $variant . "/" . $c . ".png";
        $fn = stream_resolve_include_path($fn);
    }
//	echo $fn;exit();
    $ci = imagecreatefrompng($fn);
//	imagepalettecopy($im,$ci);

    if ($direction == 1) {
        imageflip($ci, IMG_FLIP_HORIZONTAL);
    }

    imagecopy($im, $ci, $i * 36, 0, 0, 0, 36, 48);

    imagedestroy($ci);
}
//$white = imagecolorallocate($im, 255, 255, 255);
imagesetthickness($im, 2);
imagealphablending($im, false);

imageline($im, 1, 9 * 4, 43 * 4, 1, $transparent);
imageline($im, 25 * 4, 11 * 4, 62 * 4, 1, $transparent);
imageline($im, 24 * 4, 6 * 4, 59 * 4, 9 * 4, $transparent);


imagesavealpha($im, true);
imagepng($im);
imagedestroy($im);

