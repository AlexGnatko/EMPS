<?php

class EMPS_Auth_Common
{
    public $AUTH_R = -4;
    public $USER_ID;

    public function login_error($code)
    {
        $_SESSION['login']['error'] = $code;
    }

    public function no_login_error()
    {
        unset($_SESSION['login']['error']);
    }

    public function form_fullname($ra)
    {
        $ra['fullname'] = $ra['firstname'] . " " . $ra['lastname'];
        return $ra;
    }

    public function handle_logon()
    {
        global $emps, $smarty;

        $this->no_login_error();

        if (isset($_POST['post_login'])) {
            if ($_POST['post_login'] == 1) {
                $this->AUTH_R = $this->create_session($_POST['login_username'], $_POST['login_password'], 0);
                $emps->redirect_elink();
                exit();
            }
        }

        $this->check_session();

        if (isset($_GET['logout'])) {
            if ($_GET['logout'] == 1) {
                $this->close_session();
            }
        }

        if ($this->credentials("users")) {
            $_SESSION['login']['status'] = 1;
        } else {
            $_SESSION['login']['status'] = 0;
        }

        if (isset($smarty)) {
            if (is_array($_SESSION['login'])) {
                if (!isset($this->login)) {
                    $this->login = array();
                }
                $this->login = array_merge($this->login, $_SESSION['login']);
            }
            $smarty->assign("login", $this->login);
        }
    }
}

