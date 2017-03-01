<?php
$emps->no_smarty = true;

if (!$key) {
    $key = "empsrootpwd";
}

$user = $emps->db->get_row("e_users", "id = 1 and username = 'root'");
if (!$user) {
    $SET = array();
    $SET['username'] = 'root';
    $SET['password'] = md5($key);
    $SET['fullname'] = 'Root Admin';
    $SET['status'] = 1;
    $emps->db->sql_insert("e_users");
    $user_id = $emps->db->last_insert();
    $emps->auth->add_to_group_context($user_id, 'root', $emps->default_ctx);
    $emps->auth->add_to_group_context($user_id, 'admin', $emps->default_ctx);

    $context_id = $emps->p->get_context(DT_USER, 1, $user_id);
    $emps->p->save_properties(array("firstname" => "Root", "lastname" => "Admin"), $context_id, P_USER);
    echo "User 'root' has been created.";
} else {
    if (!$emps->auth->user_credentials_context($user['id'], 'root', $emps->default_ctx)) {
        $emps->auth->add_to_group_context($user['id'], 'root', $emps->default_ctx);
        echo "User '" . $user['username'] . "' has been given root credentials.";
    } else {
        echo "User 'root' exists. No action needed.";
    }
}
?>