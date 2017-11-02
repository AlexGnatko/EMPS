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

    public function row($ra){
        global $emps;

        $ra['website_ctx'] = $emps->website_ctx;
        $er = $emps->db->get_row("e_unique_texts", "website_ctx = ".$ra['website_ctx']." and type_code = '".
            $ra['type_code']."' and context_id = ".$ra['context_id']);

        if($er){
            return $er;
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
        $text = str_replace(array('<p(>| .*>)', '</p>', '<br />', '<br>', '<br/>'), array('', "\r\n\r\n", "\r\n", "\r\n", "\r\n"), $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text);

        $rtxt = "";
        $x = explode("\n", $text);
        foreach($x as $v){
            $v = trim($v);
            if(!$v){
                continue;
            }
            $rtxt .= $v."\r\n\r\n";
        }

        return $rtxt;
    }

    public function handle_request($context_id, $type_code, $row){
        global $emps, $smarty;

        $utxt = [];
        $get_text = $row['unique_text'];
        if(!$get_text){
            $get_text = $row['html'];
        }
        $utxt['unique_text'] = $this->html_to_plain($get_text);
        $utxt['title'] = $row['name'];
        $utxt['type_code'] = $type_code;
        $utxt['context_id'] = $context_id;

        $smarty->assign("utxt", $utxt);

        if($_GET['uniqtxt_add']){
            $this->add($utxt);
            $emps->redirect_elink();
        }


        $urow = $this->row($utxt);
        $smarty->assign("utxt_row", $urow);
        if($urow){
            if($_GET['uniqtxt_upload_now']){
                $emps->no_time_limit();
                $result = file_get_contents(EMPS_SCRIPT_WEB."/service-uniqtxt-upload/".$urow['id']."/");
                $emps->redirect_elink();
            }
            if($_GET['uniqtxt_delete']){
                $id = $urow['id'];
                if($urow['context_id'] == $context_id){
                    $emps->db->query("delete from ".TP."e_unique_texts where id = ".$id);
                }
                $emps->redirect_elink();
            }
        }
    }
}