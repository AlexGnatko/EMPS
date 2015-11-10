<?php
global $emps;

require_once $emps->common_module('xml.class.php');
require_once $emps->common_module('photos/photos.class.php');

class EMPS_Videos {
	public $p;
	
	public function __construct(){
		global $emps;
		
		$this->p = new EMPS_Photos;
		
		$emps->p->register_cleanup(array($this,'delete_videos_context'));		
	}
	
	function parse_video_url($url){
		$a=array();
		
		$x=explode('youtube.com/watch?',$url,2);
		if($x[1]){
			$y=explode('&',$x[1]);
			while(list($n,$v)=each($y)){
				$z=explode('=',$v);
				if($z[0]=='v'){
					$a['youtube_id']=$z[1];
				}
			}
		}else{
			$x = explode("://vimeo.com/", $url, 2);
			if($x[1]){
				$a['vimeo_id'] = $x[1];
			}else{
				$x = explode("://rutube.ru/video/", $url, 2);
				if($x[1]){
					$a['rutube_id'] = $x[1];					
				}
			}
		}
		
		return $a;
	}
	
	function process_video($video_id){
		global $emps,$SET;
		$os=$SET;
		$SET=array();
		$ctx=$emps->p->get_context(DT_VIDEO,1,$video_id);
		
		$video=$emps->db->get_row("e_videos","id=$video_id");
		if($video['youtube_id']){
			$xml=file_get_contents('http://gdata.youtube.com/feeds/api/videos/'.$video['youtube_id']);
			
			file_put_contents(EMPS_SCRIPT_PATH.'/video.xml',$xml);			
			$xml_parser = new Simple_XMLParser;
			$xml_parser->parse($xml);
			$xml = $xml_parser->data['ENTRY'][0]['child'];
			
			if(!$video['name']){
				$SET['name']=$xml['TITLE'][0]['data'];
			}
			if(!$video['description']){
				$SET['description']=$xml['CONTENT'][0]['data'];
			}
			
			$group=$xml['MEDIA:GROUP'][0]['child'];
			if($group){
				while(list($n,$v)=each($group['MEDIA:CONTENT'])){
					$a=$v['attribs'];
					if($a['TYPE']=='application/x-shockwave-flash'){
						$SET['flash']=$a['URL'];
						$SET['dflash']=$a['DURATION'];
						$SET['duration']=$a['DURATION'];
					}
					if($a['TYPE']=='video/3gpp'){
						$SET['3gp']=$a['URL'];
						$SET['d3gp']=$a['DURATION'];
					}
				}
			}
			
			$SET['vslink']=$group['MEDIA:PLAYER'][0]['attribs']['URL'];
			
			if(!$SET['duration']){
				$SET['duration']=$group['YT:DURATION'][0]['attribs']['SECONDS'];
			}
			
			unset($_REQUEST['id']);
			unset($SET['id']);
			unset($GLOBALS['id']);
			
			$emps->db->sql_update("e_videos","id=$video_id");
			$emps->p->save_properties($SET,$ctx,P_VIDEO);
			
			if($group){
				$this->p->delete_photos_context($ctx);
				
				$ord=10;
				
				$list = array_reverse($group['MEDIA:THUMBNAIL']);
				
				$maxwidth = 0;
				$maxheight = 0;
				
		
				while(list($n,$v)=each($list)){
					$a=$v['attribs'];
					if($a){
						$data=file_get_contents($a['URL']);
						if($data){
							$_REQUEST=array();		
							$SET=array();
							$_REQUEST['md5']=md5(uniqid(time()+1231111));
							$_REQUEST['filename']=$a['URL'];
							$_REQUEST['type']='image/jpeg';
							$_REQUEST['size']=strlen($data);
							$_REQUEST['thumb']=$a['WIDTH'].'x'.$a['HEIGHT']."|120x90|auto,max";
							$_REQUEST['context_id']=$ctx;
							$_REQUEST['ord']=$ord+10;
							$emps->db->sql_insert("e_uploads");
							$file_id=$emps->db->last_insert();
							$oname=$this->p->up->upload_filename($file_id,DT_IMAGE);
							
							if($maxheight < $a['HEIGHT']){
								$maxheight = $a['HEIGHT'];
							}
							
							if($maxwidth < $a['WIDTH']){
								$maxwidth = $a['WIDTH'];
							}							
					
							file_put_contents($oname,$data);
					
							$row=$emps->db->get_row("e_uploads","id=$file_id");
							if($row){
								$fname=$this->p->thumb_filename($file_id);	
								$this->p->treat_upload($oname,$fname,$row);							
/*								dump($row);
								echo $oname." // ".$fname;
								exit();*/
							}
						}
					}
				}
				$SET = array('width'=>$maxwidth, 'height'=>$maxheight);
				
				$emps->p->save_properties($SET,$ctx,P_VIDEO);									
			}
		}
		
		if($video['vimeo_id']){
			$raw = file_get_contents("http://vimeo.com/api/v2/video/".$video['vimeo_id'].".json");
			$data = json_decode($raw, true);
			
			$data = $data[0];
			
			$SET = array();
			$SET['name'] = $data['title'];
			$SET['description'] = $data['description'];			
			$SET['duration'] = $data['duration'];
			$SET['width'] = $data['width'];
			$SET['height'] = $data['height'];			
			
			$emps->db->sql_update("e_videos","id=$video_id");
			$emps->p->save_properties($SET,$ctx,P_VIDEO);			
			
			$image = file_get_contents($data['thumbnail_large']);
			
			$x = explode("/", $data['thumbnail_large']);
			$name = $x[count($x)-1];
			
			$_REQUEST = array();		
			$SET = array();
			$_REQUEST['md5']=md5(uniqid(time()+1231111));
			$_REQUEST['filename']=$name;
			$_REQUEST['type']='image/jpeg';
			$_REQUEST['size']=strlen($image);
			$_REQUEST['thumb']="1920x1080|120x90|inner";
			$_REQUEST['context_id']=$ctx;
			$_REQUEST['ord']=10;
			$emps->db->sql_insert("e_uploads");
			$file_id=$emps->db->last_insert();
			$oname=$this->p->up->upload_filename($file_id, DT_IMAGE);
			
			file_put_contents($oname,$image);
	
			$row=$emps->db->get_row("e_uploads","id=$file_id");
			if($row){
				$fname=$this->p->thumb_filename($file_id);	
				$this->p->treat_upload($oname,$fname,$row);							
			}
			

//			dump($data);exit();
		}
	
		if($video['rutube_id']){
			$raw = file_get_contents("http://rutube.ru/api/video/".$video['rutube_id']."/?format=json");
			$data = json_decode($raw, true);
			
//			dump($data);exit();
			
			$SET = array();
			$SET['name'] = $data['title'];
			$SET['description'] = $data['description'];			
			$SET['duration'] = $data['duration'];
			$SET['embed_url'] = $data['embed_url'];
			
			$emps->db->sql_update("e_videos","id=$video_id");
			$emps->p->save_properties($SET,$ctx,P_VIDEO);			
			
			$image = file_get_contents($data['thumbnail_url']);
			
			$x = explode("/", $data['thumbnail_url']);
			$name = $x[count($x)-1];
			
			$_REQUEST = array();		
			$SET = array();
			$_REQUEST['md5']=md5(uniqid(time()+1231111));
			$_REQUEST['filename']=$name;
			$_REQUEST['type']='image/jpeg';
			$_REQUEST['size']=strlen($image);
			$_REQUEST['thumb']="1920x1080|120x90|inner";
			$_REQUEST['context_id']=$ctx;
			$_REQUEST['ord']=10;
			$emps->db->sql_insert("e_uploads");
			$file_id=$emps->db->last_insert();
			$oname=$this->p->up->upload_filename($file_id, DT_IMAGE);
			
			file_put_contents($oname,$image);
	
			$row=$emps->db->get_row("e_uploads","id=$file_id");
			if($row){
				$fname=$this->p->thumb_filename($file_id);	
				$this->p->treat_upload($oname,$fname,$row);							
			}
			

//			dump($data);exit();
		}
	
		$SET=$os;
	}
	
	function delete_video($id){
		global $emps;
		$ictx=$emps->p->get_context(DT_VIDEO,1,$id);
		
		$emps->p->delete_context($ictx);	
		
		$emps->db->query("delete from ".TP."e_videos where id=$id");
	}
	
	function delete_videos_context($context_id){
		global $emps;
		
		$r = $emps->db->query("select * from ".TP."e_videos where context_id = ".$context_id);
		while($ra = $emps->db->fetch_named($r)){
			$this->delete_video($ra['id']);
		}
	}
	
	function convert_duration($duration) {
		$h = (integer)floor($duration / 3600);
		$m = (integer)floor(($duration - $h*3600) / 60);
		$s = $duration - $h*3600 - $m*60;
		return sprintf('%02d:%02d:%02d', $h, $m, $s);
	}
	
	function load_videos($context_id,$sel_id){
		global $emps,$sd;
		$r=$emps->db->query("select * from ".TP."e_videos where context_id=$context_id order by ord asc, id asc");
		$lst=array();
		$emps->loadvars();
		while($ra=$emps->db->fetch_named($r)){
			$cctx=$emps->p->get_context(DT_VIDEO,1,$ra['id']);
		
			$sd='videos.'.$ra['id'];
			$ra['vlink']=$emps->elink();
			$ra['pic']=$this->p->first_pic($cctx);
			
			if($ra['id']==$sel_id){
				$ra['sel']=true;
			}
		
			$lst[]=$ra;
		}
		return $lst;
	}	
	
	function count_videos($context_id){
		global $emps;
		
		$r = $emps->db->query("select count(*) from ".TP."e_videos where context_id = ".$context_id);
		$ra = $emps->db->fetch_row($r);
		
		return $ra[0];
	}
}
?>