<?php
$emps->no_smarty = true;

ob_start();

$variants = 4;

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

$nodir = $emps->get_setting("straight_captcha");

header("Content-Type: image/png");
$im = imagecreatetruecolor(7 * 36, 48);
imagealphablending($im, true);

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $transparent);

$colors = array();

for($i = 0; $i < 8; $i++){
//    $colors[$i] = imagecolorallocatealpha( $im, mt_rand(60, 200), mt_rand(60, 200), mt_rand(60, 200), 60);
    $colors[$i] = imagecolorallocatealpha( $im, mt_rand(120, 240), mt_rand(120, 240), mt_rand(120, 240), 60);
}

for($i = 0; $i <= 21; $i++){
    for($k = 0; $k <= 4; $k++){
        $idx = mt_rand(0, 7);
        imagefilledellipse($im, $i * 12 , $k * 12, 18 + mt_rand(-5, 5), 18 + mt_rand(-5, 5), $colors[$idx]);
    }
}

$s = strval($number);
$l = strlen($s);

$md5 = md5($dt);

for ($i = 0; $i < $l; $i++) {
    $c = $s{$i};
    $v1 = $md5{$i * 2};
    $v2 = $md5{$i * 2 + 1};
    $v1d = hexdec($v1);
    $v2d = hexdec($v2);
    $variant = ($v1d % $variants) + 1;
    $direction = ($v2d % $variants);
    $fn = EMPS_SCRIPT_PATH . "/i/dig/" . $variant . "/" . $c . ".png";
    if (!file_exists($fn)) {
        $fn = EMPS_PATH_PREFIX . "/i/dig/" . $variant . "/" . $c . ".png";
        $fn = stream_resolve_include_path($fn);
    }
//	echo $fn;exit();
    $ci = imagecreatefrompng($fn);
//	imagepalettecopy($im,$ci);

    if(!$nodir) {
        if ($direction == 1) {
            imageflip($ci, IMG_FLIP_HORIZONTAL);
        }
    }

    imagecopy($im, $ci, $i * 36, 0, 0, 0, 36, 48);

    imagedestroy($ci);
}
//$white = imagecolorallocate($im, 255, 255, 255);
imagesetthickness($im, 2);
imagealphablending($im, false);

//imageline($im, 1, 9 * 4, 43 * 4, 1, $transparent);
//imageline($im, 25 * 4, 11 * 4, 62 * 4, 1, $transparent);
//imageline($im, 24 * 4, 6 * 4, 59 * 4, 9 * 4, $transparent);


imagesavealpha($im, true);
imagepng($im);
imagedestroy($im);

$data = ob_get_clean();

if ($_GET['encoded']) {
    $output = base64_encode($data);
    header("Content-Type: text/plain");
    echo "data:image/png;base64,";
    echo $output;
    exit;
}

$only = $emps->get_setting("encoded_captcha");
if ($only) {
    exit;
}

echo $data;