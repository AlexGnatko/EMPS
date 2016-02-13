<?php
class EMPS_PropertiesEditor {
	public $context_id = 0;
	
	public function handle_request($context_id){
		global $emps,$sd,$smarty;
		
		$emps->uses_flash();
		
		$this->context_id = $context_id;
		
		$sd="";
		$smarty->assign("BackLink",$emps->elink());
		$emps->loadvars();
		
		if($_GET['kill']){
			$id = $_GET['kill'] + 0;
			$row=$emps->db->get_row('e_properties','id='.$id);
			$row['value']=$data[$row['code']];
			$smarty->assign("KillForm",1);
			$smarty->assign("set",$row);			
		}
		
		if($_POST['post_confirm']){
			$id = $_POST['set_id'] + 0;
			$row=$emps->db->get_row("e_properties","id=".$id);
			$emps->db->query('delete from '.TP.'e_properties where context_id='.$this->context_id.' and id='.$id);			
			$list = $row['code'];
			
			$emps->flash("killedcode",$list);
			$sd = "";			
			$emps->redirect_elink();exit();			
		}
		
		if($_POST['post_saveset']){
			$emps->p->save_property($this->context_id,$_POST['code'],$_POST['type'],$_POST['value'],false,0);
			$emps->flash("savedcode",$_POST['code']);
			$emps->redirect_elink();exit();
		}
		
		if($_POST['post_import']){
			$data = json_decode($_POST['import_json'], true);
			foreach($data as $ra){
				switch($ra['type']){
				case "i":
				case "r":
					$value=$ra['v_int'];
					break;
				case "f":
					$value=$ra['v_float'];
					break;
				case "c":
					$value=$ra['v_char'];
					break;
				case "d":
					$value=$ra['v_data'];
					break;
				case "b":
					$value=$ra['v_bool'];
					break;
				default:
					$value=$ra['v_text'];
				}
				$emps->p->save_property($this->context_id, $ra['code'], $ra['type'], $value, false, 0);
			}
			$emps->redirect_elink();exit();
		}
		
		if($_POST['post_delete']){
			$list="";
			while(list($n,$v)=each($_POST['sel'])){
				$n+=0;
				$row=$emps->db->get_row("e_properties","id=$n");
				$emps->db->query('delete from '.TP.'e_properties where context_id='.$this->context_id.' and id='.$n);
				if($list!="") $list.=", ";
				$list.=$row['code'];
			}
			$emps->flash("killedcode",$list);
			$sd = "";
			$emps->redirect_elink();exit();
		}
		if($_POST['post_export']){
			$list = array();
			foreach($_POST['sel'] as $n => $v){
				$n = intval($n);
				$row = $emps->db->get_row("e_properties", "id = ".$n);
				unset($row['id']);
				unset($row['context_id']);
				unset($row['dt']);
				foreach($row as $n => $v){
					if(!$v){
						unset($row[$n]);
					}
				}
				$list[] = $row;
			}
			$code = json_encode($list);
			$smarty->assign("exportcode", $code);
		}
		
		$data=$emps->p->read_properties(array(),$this->context_id);
		
		if($sd){
			$sd+=0;
			$row=$emps->db->get_row('e_properties','id='.$sd);
			$row['value']=$data[$row['code']];
			$smarty->assign("KillLink",$emps->clink("kill=".$row['id']));
			$smarty->assign("set",$row);
		}
		
		$lst=array();
		if(is_array($data['_full'])){
			while(list($n,$v)=each($data['_full'])){
				$v['value']=$data[$v['code']];
				$v['value']=$emps->cut_text(strip_tags($v['value']),100);
				$sd=$v['id'];
				$v['link']=$emps->elink();
				$lst[]=$v;
			}
		}
		
		$smarty->assign("lst",$lst);		
	}
}
?>