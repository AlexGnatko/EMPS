<?php

class EMPS_Properties {
    private $cleanups = array();
	
	public function get_context($type, $subtype, $ref_id){
		global $emps;
		
		$params = array();
		
		if(is_string($ref_id) || is_object($ref_id)){
			$query = array('type' => $type, 'subtype' => $subtype, 'ref_id' => $emps->db->oid($ref_id));
		}else{
			$query = array('type' => $type, 'subtype' => $subtype, 'numeric_id' => $ref_id);
		}
		$params['query'] = $query;
//		$params['options'] = array('readConcern' => new MongoDB\Driver\ReadConcern('majority'));
		$row = $emps->db->get_row("emps_contexts", $params);
		if(!$row){
			$row = array();
			$row['type'] = $type;
			$row['subtype'] = $subtype;
			if(is_string($ref_id) || is_object($ref_id)){
				$row['ref_id'] = $emps->db->oid($ref_id);
			}else{
				$row['numeric_id'] = $ref_id;
			}
			$params = array();
			$params['doc'] = $row;
			$params['options'] = array('writeConcern' => new MongoDB\Driver\WriteConcern('majority', 15000));
			$emps->db->insert("emps_contexts", $params);
			return $emps->db->last_id;
		}
		return $row['_id'];
	}
	
	public function load_context($_id){
		global $emps;
		
		$row = $emps->db->get_row("emps_contexts", array('query' => array('_id' => $_id)));
		return $row;
	}

    public function register_cleanup($call)
    {
        foreach($this->cleanups as $v){
            if (get_class($v[0]) == get_class($call[0])) {
                return false;
            }
        }
        $this->cleanups[] = $call;
        return true;
    }

    public function delete_context($context_id)
    {
        global $emps;
        reset($this->cleanups);
        foreach($this->cleanups as $v) {
            $callme = "";
            if (is_callable($v, false, $callme)) {
                $obj = $v[0];
                $method = $v[1];
                $obj->$method($context_id);
            }
        }
        $params = [];
        $params['query'] = ['context_id' => $emps->db->oid($context_id)];
        $emps->db->delete_one("emps_contexts", $params);
        //$emps->db->query('delete from ' . TP . "e_contexts where id=$context_id");
    }
}

