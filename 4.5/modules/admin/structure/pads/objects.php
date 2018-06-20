<?php
global $smarty;

$this->handle_view_row();

$r = $emps->db->query("select i.* from ".TP.$this->items_table_name." as i
join ".TP.$this->link_table_name." as si
on si.item_id = i.id
and si.structure_id = {$this->ref_id}
order by i.ord asc
limit 1000
    ");

$lst = [];

while($ra = $emps->db->fetch_named($r)){
    $ra = $this->items->explain_item($ra);
    $ra['nlink'] = "/{$this->items_editor_pp}/".$ra['id']."/-/info/";
    $lst[] = $ra;
}

$smarty->assign("lst", $lst);