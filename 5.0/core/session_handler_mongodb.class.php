<?php

// The session handler based on $emps->db (SQL abstraction interface)

class EMPS_SessionHandler implements SessionHandlerInterface
{
    public $last_id;

    public $last_data;

    public $last_result;

    public function open($savePath, $sessionName)
    {
        // just open... we're a database-driven session handler

        return true;
    }

    public function close()
    {
        // just close

        return true;
    }

    public function read($id)
    {
        global $emps;

        if (!trim($id)) {
            return false;
        }

        $this->last_id = $id;

        $rv = "";

        $params = [];
        $params['query'] = ['sess_id' => $id];
        $row = $emps->db->get_row("emps_php_sessions", $params);

        if(!$row){
            $doc = [];
            $doc['sess_id'] = $id;
            $doc['ip'] = $_SERVER['REMOTE_ADDR'];
            $doc['browser_id'] = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
            $params = array();
            $params['doc'] = $doc;
            $emps->db->insert("emps_php_sessions", $params);
        }else{
            if ($row['dt'] < (time() - 10 * 60)) {
                $nr = [];
                $nr['dt'] = time();

                $browser_id = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
                if ($browser_id != $row['browser_id']) {
                    $nr['browser_id'] = $browser_id;
                }

                $params = array();
                $params['query'] = array("_id" => $emps->db->oid($row['_id']));
                $params['update'] = array('$set' => $nr);
                $emps->db->update_one("emps_php_sessions", $params);
            }
            $this->last_result = $row;
            $rv = $row['data'];
        }

        $this->last_data = $rv;

        return $rv;
    }

    public function write($id, $data)
    {
        global $emps;

        if (!trim($id)) {
            return false;
        }

        $update = true;
        if ($data == $this->last_data) {
            $update = false;
        }

        if ($update) {
            $doc = array();
            $doc['data'] = $data;
            $doc['browser_id'] = $emps->ensure_browser($_SERVER['HTTP_USER_AGENT']);
            $doc['ip'] = $_SERVER['REMOTE_ADDR'];

            $params = [];
            $params['query'] = ['sess_id' => $id];
            $row = $emps->db->get_row("e_php_sessions", $params);
            if ($row) {
                $params = array();
                $params['query'] = array("_id" => $emps->db->oid($row['_id']));
                $params['update'] = array('$set' => $doc);
                $emps->db->update_one("emps_php_sessions", $params);
            } else {
                $doc['sess_id'] = $id;
                $params = array();
                $params['doc'] = $doc;
                $emps->db->insert("emps_php_sessions", $params);
            }
        }

        return true;
    }

    public function destroy($id)
    {
        global $emps;

        $params = [];
        $params['query'] = ['sess_id' => $id];

        $emps->db->delete_one("emps_php_sessions", $params);

        return true;
    }

    public function gc($maxlifetime)
    {
        // Garbage collection will be done by a service script,
        // so that clients never have to wait for it.

        return true;
    }
}

