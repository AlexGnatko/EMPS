<?php

// The session handler based on Cassandra

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
        $this->last_id = $id;

        $rv = "";

        $r = $emps->cas->query("select * from php_sessions where id = '" . $id . "'");
        if (count($r['rows']) == 0) {
            $emps->cas->query("insert into php_sessions (id, ping, data) values ('" . $id . "', dateof(now()), '') using TTL " . EMPS_SESSION_COOKIE_LIFETIME);
        } else {
            $row = $r['rows'][0];
            $this->last_result = $row;
            $rv = (string)$row['data'];
        }

        $this->last_data = $rv;

        return @$rv;
    }

    public function write($id, $data)
    {
        global $emps;

        $update = true;
        if ($data == $this->last_data) {
            $update = false;
        }

        if ($update) {
            $data = str_replace("'", "''", $data);
            $emps->cas->query("insert into php_sessions (id, ping, data) values ('" . $id . "', dateof(now()), '" . $data . "') using TTL " . EMPS_SESSION_COOKIE_LIFETIME);
        }

        return true;
    }

    public function destroy($id)
    {
        global $emps;

        $emps->cas->query("delete from php_sessions where id = '" . $id . "'");

        return true;
    }

    public function gc($maxlifetime)
    {
        // garbage collection is dealt with by Cassandra (using TTL)

        return true;
    }
}

?>