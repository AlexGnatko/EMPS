<?php

header("Content-Type: text/plain; charset=utf-8");

$emps->no_smarty = true;

$emps->no_time_limit();

function collect_indices($r)
{
    global $emps;

    $lst = array();
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[] = $ra;
    }

    return $lst;
}

function collect_columns($r)
{
    global $emps;

    $lst = array();
    while ($ra = $emps->db->fetch_named($r)) {
        $lst[$ra['Field']] = $ra;
    }

    return $lst;
}

function sync_indices($dest_table, $src_table, $didx, $sidx)
{
    global $emps;

    foreach ($sidx as $sc) {
        reset($didx);
        $exists = false;
        foreach ($didx as $dc) {
            if ($dc['Table'] == $dest_table && $sc['Table'] == $src_table) {
                if ($dc['Column_name'] == $sc['Column_name']) {
                    if (($dc['Key_name'] == $sc['Key_name']) && ($dc['Non_unique'] == $sc['Non_unique'])) {
                        $exists = true;
                    }
                }
            }
        }
        if (!$exists) {
            $column = $sc['Column_name'];
            $index = $sc['Key_name'];

            echo $column . ": create index\r\n";
            if ($sc['Key_name'] == 'PRIMARY') {
                $emps->db->query("alter table `$dest_table` add primary key (`$column`)");
            } elseif ($sc['Index_type'] == 'FULLTEXT') {
                $emps->db->query("alter table `$dest_table` add fulltext key `$index` (`$column`)");
            } else {
                $emps->db->query("alter table `$dest_table` add key `$index` (`$column`)");
            }

        }
    }
}

function get_auto($column)
{
    if ($column['Extra'] == 'auto_increment') {
        return "auto_increment";
    }
    return '';
}

function get_not_null($column)
{
    if ($column['Null'] == 'YES') {
        return "null";
    }
    return "not null default 0";
}

function sync_structure($dest_table, $src_table, $dest, $src)
{
    global $emps;

    foreach ($src as $sc) {
        $field = $sc['Field'];
        if ($dest[$field]) {
            // field exists

            $di = $dest[$field];

            echo "DI:\r\n";
            var_dump($di);
            echo "SC:\r\n";
            var_dump($sc);

            echo "\r\n";

            if ($di['Type'] != $sc['Type']) {

                echo $field . ": type change: " . $di['Type'] . " => " . $sc['Type'] . "\r\n";
                $null = get_not_null($sc);
                $auto = get_auto($sc);
                $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $null . " " . $auto;
                $emps->db->query($query);
                echo $query("\r\n");
            } else {
                if ($di['Null'] != $sc['Null']) {
                    echo $field . ": null change: " . $di['Null'] . " => " . $sc['Null'] . "\r\n";
                    $null = get_not_null($sc);
                    $auto = get_auto($sc);
                    $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $null . " " . $auto;
                    $emps->db->query($query);
                    echo $query("\r\n");
                } else {
                    if (($di['Extra'] != $sc['Extra']) && ($sc['Extra'] != "NULL")) {
                        echo $field . ": extra change: " . $di['Extra'] . " => " . $sc['Extra'] . "\r\n";
                        $auto = get_auto($sc);
                        $query = "alter table `$dest_table` modify `$field` " . $sc['Type'] . " " . $auto;
                        $emps->db->query($query);
                        echo $query("\r\n");
                    }
                }
            }

            if ($di['Default'] != $sc['Default']) {
                echo $field . ": default change: '" . $di['Default'] . "' => '" . $sc['Default'] . "'\r\n";
                if ($sc['Default'] == 'NULL') {
                    $query = "alter table `$dest_table` alter column `$field` drop default ";
                } else {
                    $query = "alter table `$dest_table` alter column `$field` set default '" . $emps->db->sql_escape($sc['Default']) . "'";
                }
                $emps->db->query($query);
                echo $query("\r\n");
            }
        } else {
            // add field
            echo $field . ": add field\r\n";
            $null = get_not_null($sc);
            $auto = get_auto($sc);
            $query = "alter table `$dest_table` add column `$field` " . $sc['Type'] . " " . $null . " " . $auto;
            $emps->db->query($query);
            echo $query("\r\n");
        }
    }
}

if ($emps->auth->credentials("admin") || $emps->is_empty_database() || true) {
    if ($key) {
        $name = "_" . $key . "/sql,module.sql";
        $file_name = $emps->page_file_name($name, 'inc');
        $sql = file_get_contents($file_name);
    } else {
        if ($_GET['sql']) {
            $sql = file_get_contents($emps->page_file_name($_GET['sql'], 'inc'));
        } else {
            $sql = file_get_contents($emps->common_module('config/sql/emps.sql'));
            $fn = $emps->common_module('config/sql/project.sql');
            if ($fn) {
                $sql .= file_get_contents($fn);
            }
        }
    }

    $x = explode("-- table", $sql);
    foreach ($x as $code) {
        if (!stristr($code, "create temporary table")) {
            continue;
        }
        $fx = explode("`", $code, 2);
        $tn = explode("`", $fx[1], 2);
        $table_name = trim($tn[0]);
        $fx = explode("temp_", $table_name, 2);

        $rt_name = TP . $fx[1];
        echo "Table `$table_name` (`$rt_name`):\r\n";

        $r = $emps->db->query("show tables like '$rt_name'");
        $ra = $emps->db->fetch_named($r);
        if ($ra) {
            // table exists, synchronize
            $emps->db->query($code);
            echo $emps->db->sql_error();

            $query = "show columns from `$table_name`";
            $r = $emps->db->query($query);
            $lst_t = collect_columns($r);

            $query = "show columns from `$rt_name`";
            $r = $emps->db->query($query);
            $lst_r = collect_columns($r);

            $query = "show index from `$table_name`";
            $r = $emps->db->query($query);
            $idx_t = collect_indices($r);

            $query = "show index from `$rt_name`";
            $r = $emps->db->query($query);
            $idx_r = collect_indices($r);

            sync_structure($rt_name, $table_name, $lst_r, $lst_t);
            sync_indices($rt_name, $table_name, $idx_r, $idx_t);

        } else {
            // table does not exist
            // simply cook the $code and execute

            $code = str_replace($table_name, $rt_name, $code);
            $code = str_ireplace("temporary", "", $code);
            $emps->db->query($code);
//			echo $code;
            $emps->db->sql_error();
            echo "CREATED the table.\r\n";
        }
    }
}

