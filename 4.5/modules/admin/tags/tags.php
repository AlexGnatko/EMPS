<?php

function handle_value($field, $id)
{
    global $emps, $SET;

    unset($_REQUEST[$field]);

    $row = $emps->db->get_row("e_topics", "id = " . $id);

    if ($row) {
        $value = $_POST[$field][$id];
        if ($row[$field] != $value) {
            $SET = array();
            $SET[$field] = $value;
            $emps->db->sql_update("e_topics", "id = " . $id);
        }
    }
}

function count_posts($topic_id)
{
    global $emps;

    $r = $emps->db->query("select count(*) from " . TP . "e_posts_topics where topic_id = " . $topic_id);
    $ra = $emps->db->fetch_row($r);

    return $ra[0];
}

if ($emps->auth->credentials("admin")):
    $emps->page_property("ited", 1);

    if ($_GET['kill']) {
        $id = intval($_GET['kill']);

        $emps->db->query("delete from " . TP . "e_posts_topics where topic_id = " . $id);
        $emps->db->query("delete from " . TP . "e_topics where id = " . $id);

        $emps->redirect_elink();
        exit();
    }

    if ($_POST['post_values']) {
        foreach ($_POST['item'] as $n => $v) {
            $id = intval($n);
            handle_value("name", $id);
        }

        $emps->redirect_elink();
        exit();
    }

    $perpage = 15;

    $start = intval($start);

    $addjoin = "";

    $r = $emps->db->query("select SQL_CALC_FOUND_ROWS i.* from " . TP . "e_topics as i $addjoin order by name asc limit $start, $perpage");

    $pages = $emps->count_pages($emps->db->found_rows());
    $smarty->assign("pages", $pages);

    $lst = array();

    while ($ra = $emps->db->fetch_named($r)) {

        $ra['count'] = count_posts($ra['id']);
        $lst[] = $ra;
    }

    $smarty->assign("lst", $lst);

else:
    $emps->deny_access("AdminNeeded");
endif;
