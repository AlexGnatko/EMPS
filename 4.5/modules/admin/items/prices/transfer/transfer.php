<?php

if($emps->auth->credentials("admin")):
    $emps->page_property("ited", 1);

    require_once $emps->page_file_name('_items,items.class','controller');

    $items = new EMPS_Items;

    if($_POST['post_select_item']){
        $_SESSION['transfers_price_editor_item'] = $_POST['item_id'];
    }

    $transfers_node = $emps->db->get_row("ws_structure", "url = '__transfers'");

    if($transfers_node){
        $nodes = $items->child_nodes($transfers_node['id']);

        foreach($nodes as $n => $node){
            $items->list_join=" join ".TP."ws_items_structure as wis on wis.item_id = i.id
                 join ".TP."ws_structure as str
                 on str.id=wis.structure_id and str.id in ({$node['id']})";
            $items->list_order=" order by str.ord asc, i.cdt asc";
            $lst = $items->list_items(10000);
            $node['lst'] = $lst;
            $nodes[$n] = $node;
        }

        $smarty->assign("nodes", $nodes);
    }

    if(isset($_SESSION['transfers_price_editor_item'])){
        $selected_item_id = $_SESSION['transfers_price_editor_item'];
        $smarty->assign("current_item_id", $selected_item_id);

        $item = $items->load_item($selected_item_id);
        $smarty->assign("prices", json_decode($item['transfer_prices'], true));

    }

    if($_POST['post_save_prices'] && $selected_item_id > 0){
        $data = $_POST;
        unset($data['post_save_prices']);

        foreach($data as $n => $v){
            $data[$n] = intval($v);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $nr = ['transfer_prices' => $json];

        $context_id = $emps->p->get_context(DT_WS_ITEM, 1, $selected_item_id);

        $emps->p->save_properties($nr, $context_id, "transfer_prices:t");
        $emps->redirect_elink(); exit;
    }

    ;

else:
    $emps->deny_access("AdminNeeded");
endif;