<?php
$emps->no_smarty=true;

$pkey=$_GET['pkey']+0;
$number="0000000";
if(!$pkey) $pkey=0;
$ra=$emps->db->get_row("e_pincode","access=$pkey");
$number=$ra['pincode'];
if(!$number) $number="0000000";

header("Content-Type: image/png");
$im=imagecreate(7*9,12);

$s=$number."";
$l=strlen($s);
for($i=0;$i<$l;$i++){
	$c=$s{$i};
	$fn = EMPS_SCRIPT_PATH."/i/dig/".$c.".png";
	if(!file_exists($fn)){
		$fn = EMPS_PATH_PREFIX."/i/dig/".$c.".png";
		$fn = stream_resolve_include_path($fn);
	}
//	echo $fn;exit();
	$ci=imagecreatefrompng($fn);
	imagepalettecopy($im,$ci);

	imagecopy($im,$ci,$i*9,0,0,0,9,12);

	imagedestroy($ci);
}

$white = imagecolorallocate($im, 255, 255, 255);
imageline($im,1,9,43,1,$white);
imageline($im,25,11,62,1,$white);
//imageline($im,24,6,59,9,$white);

imagepng($im);
imagedestroy($im);

?>