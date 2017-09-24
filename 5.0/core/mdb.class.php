<?php
$emps_autoload_prefixes[] = "mongo-php-library/src/|MongoDB/";

require_once "mongo-php-library/src/functions.php";

class EMPS_MongoDB {
	public $mdb, $mongo;
	
	public $last_result, $last_id;
	
	private $collection_cache = array();
	
	public function __construct(){
		global $emps_mongodb_config;

		$options = $emps_mongodb_config['options'];
		//$options['readConcernLevel'] = "linearizable";
		$this->mongo = new MongoDB\Driver\Manager($emps_mongodb_config['url'], $options);
		$this->mdb = new MongoDB\Database($this->mongo, $emps_mongodb_config['database']);
		$this->database_name = $emps_mongodb_config['database'];

		unset($emps_mongodb_config);
	}	
	
	public function __destruct(){
		unset($this->mdb);
		foreach($this->collection_cache as $name => $collection){
			$this->free_collection($name);
		}
	}
	
	public function oid($str){
		if(is_object($str)){
			return $str;
		}
		return new MongoDB\BSON\ObjectID($str);
	}
	
	public function oid_string($oid){
		if(is_string($oid)){
			return $oid;
		}
		return $oid->__toString();
	}
	
	public function collection($collection_name){
		if(isset($this->collection_cache[$collection_name])){
			return $this->collection_cache[$collection_name];
		}
		$options['readConcern'] = new MongoDB\Driver\ReadConcern("local");
		//var_dump($options);
		$collection = new MongoDB\Collection($this->mongo, $this->database_name, $collection_name, $options);
		$this->collection_cache[$collection_name] = $collection;
		return $collection;
	}

	public function gridfs_bucket(){
	    $bucket = $this->mdb->selectGridFSBucket(['bucketName' => 'empsfs']);
	    return $bucket;
    }
	
	public function free_collection($collection_name){
		if(isset($this->collection_cache[$collection_name])){
			unset($this->collection_cache[$collection_name]);
		}
	}
	
	public function find($collection_name, $params){
	    global $emps;

		$collection = $this->collection($collection_name);

		if(!$collection){
			return false;
		}
		
		if(!isset($params['options'])){
			$options = array();
		}else{
			$options = $params['options'];
		}


		//$concern = $collection->getReadConcern();
		//var_dump($concern);
		//echo "<br/>LEVEL: ".$concern->getLevel()."<br/>";

		if(!isset($options['readConcern'])) {
            //$options['readConcern'] = $this->mongo->getReadConcern();
//            $options['readConcern'] = new MongoDB\Driver\ReadConcern("linearizable");
        }
        //var_dump($options);
        //$emps->json_dump($options);
		
		$cursor = $collection->find($params['query'], $options);
		
		$this->found_rows = $collection->count($params['query'], array());
		
		return $cursor;
	}
	
	public function distinct($collection_name, $field, $params){
		$collection = $this->collection($collection_name);

		if(!$collection){
			return false;
		}
		
		if(!isset($params['options'])){
			$options = array();
		}else{
			$options = $params['options'];
		}
		
		$cursor = $collection->distinct($field, $params['query'], $options);
		
		return $cursor;
	}
	
	public function count_rows($collection_name, $params){
		$collection = $this->collection($collection_name);

		if(!$collection){
			return false;
		}
		
		if(!isset($params['options'])){
			$options = array();
		}else{
			$options = $params['options'];
		}
		
		return $collection->count($params['query'], $options);
	}
	
	public function get_row($collection_name, $params){
		
		if(!isset($params['options'])){
			$options = array();
		}else{
			$options = $params['options'];
		}
		if(!isset($options['limit'])){
			$options['limit'] = 1;
		}
		$params['options'] = $options;
		
		$cursor = $this->find($collection_name, $params);
		if(!$cursor){
			return false;
		}
		
		$arr = $cursor->toArray();
		
		return $arr[0];
	}
	
	public function to_array($data)
	{
		if (is_array($data) || is_object($data))
		{
			$result = array();
			foreach ($data as $key => $value)
			{
				$result[$key] = $this->to_array($value);
			}
			return $result;
		}
		return $data;
	}
	
	public function get_next_id($counter){
		$collection = $this->collection("emps_counters");
		
		$new = $collection->findOneAndUpdate(array("_id" => $counter), array('$inc' => array('seq' => 1)), array('new' => true, 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER));
		if(!$new){
			$params = array();
			$params['doc'] = array("_id" => $counter, "seq" => 1);
			$this->insert("emps_counters", $params);
			return 1;
		}
		return $new->seq;
	}
	
	public function insert($collection_name, $params){
		unset($this->last_result);
		unset($this->last_id);
		$collection = $this->collection($collection_name);
		
		$doc = $params['doc'];
		$doc['cdt'] = time();
		$doc['dt'] = time();
		
		$result = $collection->insertOne($doc);
		$this->last_result = $result;
		$this->last_id = $result->getInsertedId();
		return $result;
	}
	
	public function delete_many($collection_name, $params){
		$collection = $this->collection($collection_name);
		$result = $collection->deleteMany($params['query']);
		return $result;
	}
	
	public function delete_one($collection_name, $params){
		$collection = $this->collection($collection_name);
		$result = $collection->deleteOne($params['query']);
		return $result;
	}
	
	public function update_one($collection_name, $params){
		$collection = $this->collection($collection_name);
		$update = $params['update'];
		$update['$set']['dt'] = time();
		$result = $collection->updateOne($params['query'], $update);
		return $result;
	}
	
	public function get_array($obj){
		if(!$obj){
			return array();
		}
		$lst = array();
		foreach($obj as $n => $v){
			$lst[$n] = $v;
		}
		return $lst;
	}
	
	public function safe_array_ex($ra){
		if(is_array($ra)){
			foreach($ra as $n => $v){
				$ra[$n] = $this->safe_array_ex($v);
			}
		}else{
			if(is_object($ra)){
				if(get_class($ra) == 'MongoDB\BSON\ObjectID'){
					$ra = strval($ra);
				}
			}
		}
		return $ra;
	}
	
	public function safe_array($row){
		$ra = $row->getArrayCopy();
		
		return $this->safe_array_ex($ra);
	}
}

