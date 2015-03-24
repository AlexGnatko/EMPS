<?php
define("EMPS_P_WIDTH",70);
require_once($emps->common_module('mail/simplehtmldom/simple_html_dom.php'));

function hardtrim($s){
	$l=mb_strlen($s,"utf-8");
	$r="";$lc="";$c="";
	for($i=0;$i<$l;$i++){
		$c=mb_substr($s,$i,1,"utf-8");
		if($c==' ' || $c=="\t" || $c=="\r" || $c=="\n"){
			$c=" ";
			if($lc==$c){
				continue;
			}
		}
		$lc=$c;
		$r.=$c;
	}
	return $r;
}

function replace_xx_tags($xx,$txt){
	global $rep,$torep;
	$l=strlen($txt);
	$intag=false;
	$tagcode="";
	$pc="";
	$rep=array();
	$torep=array();
	for($i=0;$i<$l;$i++){
		$c=$txt{$i};
		if($c=='>'){
			$intag=false;
			$x=explode(":",$tagcode);
			if($x[0]==$xx){
				$rep[]="<".$tagcode.">";
				$torep[]="{{".$tagcode."}}";
			}
			$tagcode="";
		}
		if($pc=='<'){
			$intag=true;
			$tagcode="";
		}
		if($intag){
			$tagcode.=$c;
		}
		$pc=$c;                       
	}
	$txt=str_replace($rep,$torep,$txt);
	return $txt;
}

function get_texts($txt){
	$l=strlen($txt);
	$level=0;
	$parts=array();
	$part="";
	$intag=false;
	$pc="";
	for($i=0;$i<$l;$i++){
		$c=$txt{$i};
		if($pc=='<' && $c!='/'){
			$level++;
			$parts[]=$part;
			$part="";
			$pc=$c;
			continue;
		}
		if($pc=='>'){
			$intag=false;
		}
		if($c=='<'){
			$intag=true;
		}
		if(($pc=='<' && $c=='/') || ($pc=='/' && $c=='>')){

			$level--;
			$pc=$c;
			continue;
		}
		if($level==0 && !$intag){
			$part.=$c;
		}
		$pc=$c;
	}
	$parts[]=$part;
	return $parts;
}

function addtext($iout,$txt){
	$txt=html_entity_decode($txt,ENT_NOQUOTES,"UTF-8");
	if(trim($txt)==""){
		$txt="";
	}
	$l=mb_strlen($iout,"utf-8");
	$sub=mb_substr($iout,$l-1,1,"utf-8");
	if($sub=="\n"){
		if(mb_substr($txt,0,1,"utf-8")!="\r"){
			$txt=ltrim($txt);
		}
	}
	$lc=mb_substr($iout,$l-1,1,"utf-8");
	$fc=mb_substr($txt,0,1,"utf-8");
	$ttxt=trim($txt);
	$tfc=mb_substr($ttxt,0,1,"utf-8");
	if($l>0 && mb_strlen($txt,"utf-8")>0){
		if($lc!=' ' && $lc!="\n" && $lc!="\t" && $lc){
			if($fc!=' ' && $fc!="\r" && $fc!="\t" && $fc!="," && $fc!="." && $fc!=":" && $fc!="(" && $fc){
				$iout.=" ";
			}
		}
	}

	if($tfc==',' || $tfc=='.' || $tfc==':' || $tfc==' '){
		if(ord($lc)==10 || !$lc){
			$txt=ltrim(mb_substr($txt,1,mb_strlen($txt,"utf-8")-1,"utf-8"));
		}
	}
	$iout.=$txt;
	return $iout;
}

function child_of($tag,$e){
	while($e=$e->parent()){
		if($e->tag==$tag) return true;
	}
	return false;
}

function wrap_string($txt,$indent,$first){
	if(trim($txt)=="") return $txt;
	$spacer=str_repeat(" ",$indent);
	if($first) $txt=$spacer.$txt;
	
	$l=strlen($txt);
	$col=0;
	$lspace=0;
	$out="";
	$word="";
	for($i=0;$i<$l;$i++){
		$c=mb_substr($txt,$i,1,"utf-8");
		$col++;
		if($c==' '){
			if($col>EMPS_P_WIDTH){
				$out.="\r\n".$spacer;
				$word=ltrim($word);
				$col=$indent+strlen($word);
			}
			$out.=$word;
			$word="";
		}
		$word.=$c;
	}
	$out.=$word;
	return $out;
}

function format_paragraph($txt,$pfx,$indent){
	$xpfx=str_replace(" ","{{sp}}",$pfx);
	$res=$pfx.$txt;
	$x=explode("\r\n",$res);
	$xr="";
	while(list($n,$v)=each($x)){
		if($n==0){
			$xr.=wrap_string($v,$indent,false);
		}else{
			$xr.=wrap_string($v,$indent,true);
		}
		if($n<count($x)-1){
			$xr.="\r\n";
		}
	}
//	$xr=str_replace("\r\n\r\n","\r\n",$xr);
	$xr=str_replace($pfx,$xpfx,$xr);
	return $xr;
}

function process_element($e){
	global $order,$holdup;
	
	$holdup="";
	$localhold=false;
	$texts=get_texts($e->innertext);
	$de=$e;
	$e=$de->first_child();
	$i=0;
	$iout="";
	$holdspace="";
	do{
		$iout=addtext($iout,hardtrim($texts[$i]));
		$i++;
		if(!$e) break;
		if($e->tag=="br"){
			$iout.="\r\n";
		}
		$iout=addtext($iout,process_element($e));
	}while($e=$e->next_sibling());
	while(isset($texts[$i])){
		$iout=addtext($iout,hardtrim($texts[$i++]));
	}

	switch($de->tag){
	case "ol":
		$order=1;
		break;
	case "li":
		$iout.="\r\n";
		if(child_of("ol",$de)){
			$iout=format_paragraph($iout,sprintf("% 2d. ",$order),4);
			$holdspace=str_repeat(" ",4);
			$order++;
		}
		if(child_of("ul",$de)){
			$holdspace=str_repeat(" ",3);
			$iout=format_paragraph($iout," * ",3);
		}
		break;
	case "a":
		if(mb_strlen(trim($iout),"utf-8")>0){
			$post=":";
			$last=true;
			$s=$de;
			while($s=$s->next_sibling()){
				if($s->tag=="a") $last=false;
			}
			if($last){
				$holdup=$de->href."\r\n";
				$localhold=true;
			}else{
				$iout=$iout.$post."\r\n".$de->href."\r\n";
			}
			//$iout=$iout."\r\n"."URL!!!"."endlink\r\n";
		}else{
			$iout=trim($iout);
		}
		break;
	case "p":
		$iout="\r\n".trim($iout)."\r\n";
		break;
	case "head":
		$iout="";
		break;
	case "br":
		$iout="\r\n";
		break;
	case "h1":
		$iout=trim(mb_strtoupper($iout,"utf-8"));
		$fill=str_repeat("-",mb_strlen($iout,"utf-8"));
		$iout="\r\n".$iout."\r\n$fill";
		break;
	case "h2":
		$iout=trim($iout);
		$fill=str_repeat("-",mb_strlen($iout,"utf-8"));
		$iout="\r\n".$iout."\r\n$fill";
		break;
	case "h3":	
	case "h4":
	case "h5":
		$iout=trim($iout);
		$iout="\r\n".$iout.":\r\n";
		break;
	}
	if($holdup && !$localhold){
		$iout=rtrim($iout);
		$lastc=mb_substr($iout,mb_strlen($iout,"utf-8")-1,1,"utf-8");
		if($lastc=="." || $lastc==":" || $lastc==","){
			$iout=mb_substr($iout,0,mb_strlen($iout,"utf-8")-1,"utf-8");
		}
		$iout.=":\r\n".$holdspace.$holdup;
		$holdup="";
	}
	return $iout;
}

$text=str_ireplace("<br>","<br/>",$text);
$text=str_replace("\r\n"," ",$text);
$text=str_replace("\n"," ",$text);

$order=1;

$html=str_get_html("<div>".$text."</div>");

$e=$html->find("div",0);

$out="";

$out=process_element($e);

$out=format_paragraph($out,"",0);
?>