<?php

class EMPS_NG_PickDescribe
{
    public $table_name;
    public $id;

    public function parse_request()
    {
        global $emps, $key, $start;
        $x = explode("|", $key, 2);
        $this->table_name = $emps->db->sql_escape($x[0]);
        $this->id = $start;
    }

    public function handle_row($row)
    {
        return $row;
    }

    public function handle_request()
    {
        global $emps;
        $this->parse_request();

        $id = intval($this->id);
        $row = $emps->db->get_row($this->table_name, "id = " . $id);

        if ($this->table_name == "e_users") {
            $row['display_name'] = $row['id'] . ": " . $row['username'] . " / " . $row['fullname'];
        } else {
            $row['display_name'] = $row['id'] . ": " . $row['name'];
        }

        $row = $this->handle_row($row);

        $response = array();
        $response['code'] = "OK";
        $response['display'] = $row['display_name'];

        echo json_encode($response);
    }
}


$emps->no_smarty = true;

header("Content-Type: application/json; charset=utf-8");

$fn = $emps->page_file_name('_pick/ng/describe,project', 'controller');
if (file_exists($fn)) {
    require_once $fn;
}

if (!isset($pick)) {
    $pick = new EMPS_NG_PickDescribe;
}

$emps->no_smarty = true;
$pick->handle_request();

