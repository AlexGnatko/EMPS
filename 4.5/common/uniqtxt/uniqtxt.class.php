<?php

class EMPS_UniqueTexts {
    public $is_available = false;

    public function check_available(){
        global $emps;

        $r = $emps->db->query("show tables like '".TP."e_unique_texts'");
        $ra = $emps->db->fetch_row($r);
        if($ra[0] == TP.'e_unique_texts'){
            $this->is_available = true;
            return true;
        }
        return false;
    }

    public function add($ra){
        global $emps;

        $ra['website_ctx'] = $emps->website_ctx;

        $insert_new = true;
        $er = $emps->db->get_row("e_unique_texts", "website_ctx = ".$ra['website_ctx']." and type_code = '".
            $ra['type_code']."' and context_id = ".$ra['context_id']);
        if($er){
            if($er['unique_text'] == $ra['unique_text']){
                $insert_new = false;
            }
        }

        if($insert_new) {

            $update = [];
            $update['SET'] = $ra;

            $emps->db->sql_insert_row("e_unique_texts", $update);
            $id = $emps->db->last_insert();
            return $id;
        }else{
            return $er['id'];
        }
    }

    public function get_next_to_upload($field){
        global $emps;

        $er = $emps->db->get_row("e_unique_texts", "website_ctx = ".$emps->website_ctx." and ".$field.
            " = 0 order by cdt asc limit 1");
        if($er){
            return $er;
        }
        return false;
    }

    public function update_status($id, $field, $value){
        global $emps;

        $update = [];
        $update['SET'] = [$field => $value];
        $emps->db->sql_update_row("e_unique_texts", $update, "id = ".$id);
    }

    public function html_to_plain($html){
        $text = str_replace(array('<p(>| .*>)', '</p>'), array('', '<br /><br />'), $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        return $text;
    }

    public function handle_request($context_id, $type_code, $row){
        global $emps, $smarty;

        $utxt = [];
        $utxt['unique_text'] = $row['html'];
        $utxt['title'] = $row['name'];
        $utxt['type_code'] = $type_code;

        $smarty->assign("utxt", $utxt);
    }
}