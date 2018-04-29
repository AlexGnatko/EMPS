<?php
global $items, $smarty;

$this->handle_view_row();

$r = $emps->db->query("select i.* from ".TP."ws_items as i
join ".TP."ws_items_structure as si
on si.item_id = i.id
and si.structure_id = {$this->ref_id}
order by i.ord asc
    ");

$lst = [];

while($ra = $emps->db->fetch_named($r)){
    $ra = $items->explain_item($ra);
    $ra['nlink'] = "/admin-items-detailed/".$ra['id']."/-/info/";
    $lst[] = $ra;
}

$smarty->assign("lst", $lst);