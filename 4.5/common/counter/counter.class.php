<?php

class EMPS_Counter {
	public $period_size;

	public function __construct(){
		$this->period_size = 60*60*3;
	}

	public function increment_counter($code, $context_id, $count){
		global $emps,$SET;
		
		$period = floor(time()/($this->period_size));
		
		$SET = array();
		$SET['code'] = $code;
		$SET['context_id'] = $context_id;
		$SET['per'] = $period;
		$SET['vle'] = $count;
		$SET['dt'] = time();
		$row = $emps->db->get_row("e_counter","code = '$code' and context_id = $context_id and per = $period");
		if($row){
			$SET['vle'] = $row['vle'] + $count;
			$emps->db->sql_update("e_counter","id = ".$row['id']);
		}else{
			$emps->db->sql_insert("e_counter");
		}
	}
	
	public function counter_total($code, $context_id, $fromdt, $todt){
		global $emps;

		$fromperiod = floor($fromdt/($this->period_size));		
		$period = floor($todt/($this->period_size));
		
		$r = $emps->db->query("select sum(vle) from ".TP."e_counter where code='$code' and context_id = $context_id
				and per<=$period and per>=$fromperiod ");
		$ra = $emps->db->fetch_row($r);
		
		return intval($ra[0]);
	}
	
	public function get_per($dt){
		return floor($dt / ($this->period_size));
	}
}


?>