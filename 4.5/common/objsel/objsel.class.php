<?php
class EMPS_ObjectSelector {
	public function serialize_nv($array){
		reset($array);
		$txt="";
		while(list($n,$v)=each($array)){
			if(is_array($v)){
				continue;
			}
			$row=$n.'!$:$!'.$v;
			if($txt!=""){
				$txt.='!$row$!';
			}
			$txt.=$row;
		}
		return $txt;
	}
	
	public function make_and($extra){
		global $emps;
		$and="";
		if($extra){
			$x=explode("|",$extra);
			while(list($n,$v)=each($x)){
				$xx=explode("=",$v,2);
				if(count($xx)==2){
					$and.=" and ";
					$and.=$emps->db->sql_escape($xx[0])." = '".$emps->db->sql_escape($xx[1])."'";
				}else{
					$xx=explode("<>",$v,2);
					if(count($xx)==2){
						$and.=" and ";
						$and.=$emps->db->sql_escape($xx[0])." <> '".$emps->db->sql_escape($xx[1])."'";
					}else{
						$xx=explode("_in_",$v,2);
						if(count($xx)==2){
							$and.=" and ";
							$and.=$emps->db->sql_escape($xx[0])." in (".$emps->db->sql_escape($xx[1]).")";
						}
					}
				}
			}
		}		
		return $and;
	}
};
?>