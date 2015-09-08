<?php
// CALLED ONLY FOR PROGRAM MODULES

global $pp,$key,$smarty;

$ctx=$emps->website_ctx;

if($emps->auth->credentials("admin,oper")){
	if(isset($smarty)){
		$smarty->assign("AdminMode", 1);
	}
}

// Define enumerations

$emps->load_enums_from_file();

$mdays="";
for($i=1;$i<=31;$i++){
	if($mdays!=""){
		$mdays.=";";
	}
	$mdays.=$i."=".$i;
}

$years="";
$cur=date("Y",time());
$opt=$cur-40;
$future=$cur;
$max=$future;
$min=1900;
for($i=$min;$i<=$future;$i++){
	if($i<=$max){
		if($years!=""){
			$years.=";";
		}
		$years.=$i."=".$i;
		if($i==$opt){
			$years.="=def";
		}
	}
}

$hours = "";
for($i=0;$i<24;$i++){
	if($hours!=""){
		$hours.=";";
	}
	$hours.=$i."=".sprintf("%02d",$i);
	if($i==12){
		$hours.="=def";
	}
}

$mins = "";
for($i=0;$i<60;$i+=5){
	if($mins!=""){
		$mins.=";";
	}
	$mins.=$i."=".sprintf("%02d",$i);
}

$emps->make_enum("hours",$hours);
$emps->make_enum("minutes",$mins);
$emps->make_enum("mdays",$mdays);
$emps->make_enum("years",$years);

$times="00=нет";
for($i=6;$i<23;$i++){
	for($k=0;$k<60;$k+=15){
		$time=sprintf("%02d_%02d=%02d:%02d",$i,$k,$i,$k);
		if($times!=""){
			$times.=";";
		}
		$times.=$time;
	}
}
$time=sprintf("%02d_%02d=%02d:%02d",$i,0,$i,0);
$times.=";".$time;

$emps->make_enum("times",$times);

$emps->make_enum("months","1=Января;2=Февраля;3=Марта;4=Апреля;5=Мая;6=Июня;7=Июля;8=Августа;9=Сентября;10=Октября;11=Ноября;12=Декабря");
$emps->make_enum("wdays","1=ПН;2=ВТ;3=СР;4=ЧТ;5=ПТ;6=СБ;7=ВС");

$file_name = $emps->common_module('config/project/webinit.php');
if(file_exists($file_name)){
	require_once $file_name;
}

if($_GET['load_enum']){
	$response = array();
	$response['code'] = "OK";
	$response['enum'] = $emps->enum[$_GET['load_enum']];
	$emps->no_smarty = true;
	header("Content-Type: application/json; charset=utf-8");

	echo json_encode($response);
}

?>