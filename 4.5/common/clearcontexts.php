<?php
$emps->no_smarty = true;

if ($emps->auth->credentials("admin")) {
    if ($key) {
        require_once($emps->common_module('videos/videos.class.php'));
        $videos = new EMPS_Videos;

        $x = explode(",", $key);
        while (list($n, $v) = each($x)) {
            echo "Working on $v... ";
            $ref_type = $v;
            $ref_sub = CURRENT_LANG;
            $r = $emps->db->query("select * from " . TP . "e_contexts where ref_type=$ref_type and ref_sub=$ref_sub");
            while ($ra = $emps->db->fetch_named($r)) {
                $id = $ra['id'];
                $emps->p->delete_context($id);
            }
        }
    }
}
?>