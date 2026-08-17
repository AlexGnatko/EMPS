<?php
$emps->no_smarty = true;

/*
 * Logging in by activation key.
 *
 * The key is the only thing standing between a visitor and somebody else's account, and it always
 * comes from pick_activation_key() as 32 hex digits. It is therefore checked for that shape before
 * being used: anything else is refused outright, and what does pass is still escaped, because the
 * value is concatenated into the queries below and a session is issued from whatever they return.
 */
$key = strval($key);

if (!preg_match('/^[0-9a-fA-F]{32}$/', $key)) {
    $emps->redirect_page("/badkey/");
    exit;
}

$e_key = $emps->db->sql_escape($key);

$row = $emps->db->get_row("e_actkeys", "pin = '{$e_key}'");
if ($row) {
    $emps->db->query("delete from " . TP . "e_actkeys where pin = '{$e_key}'");
    $ra = $emps->db->get_row("e_users", "id = " . intval($row['user_id']));
    if (!$ra || $ra['status'] != 1) {
        $emps->redirect_page("/badkey/");
        exit;
    } else {
        $r = $emps->auth->create_session($ra['username'], "", 1);
        if (!$r) {
            $emps->redirect_page("/badkey/");
            exit;
        } else {
            $emps->auth->clear_activations($row['user_id']);
            $emps->redirect_page("/profile/");
            exit;
        }
    }
} else {
    $emps->redirect_page("/badkey/");
    exit;
}