<?php

/**
 * EMPS_SchemaOrg Class - helper to create schema.org-compliant ld-json objects
 */
class EMPS_SchemaOrg
{
    public $obj;

    public function __construct()
    {
        $this->obj = [];
        $this->set("@context", "http://schema.org");
    }

    public function to_strings($a){
        $o = [];
        foreach($a as $n => $v){
            if(is_array($v)){
                $v = $this->to_strings($v);
            }else{
                $v = strval($v);
            }
            $o[$n] = $v;
        }
        return $o;
    }

    public function generate(){
        $obj_out = $this->to_strings($this->obj);
        return json_encode($obj_out, JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function set($key, $value){
        $key = trim($key);
        if(!$key){
            return;
        }
        $this->obj[$key] = $value;
    }
}