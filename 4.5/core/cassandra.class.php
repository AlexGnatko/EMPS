<?php
require_once 'cassandra/Cassandra.php';
require_once 'cassandra/Types.php';

use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Transport\TSocket;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TBufferedTransport;

use cassandra\CassandraClient;
use cassandra\Column;
use cassandra\ColumnPath;
use cassandra\ColumnParent;
use cassandra\SuperColumn;
use cassandra\ColumnOrSuperColumn;
use cassandra\ConsistencyLevel;
use cassandra\Compression;
use cassandra\Mutation;
use cassandra\SliceRange;
use cassandra\SlicePredicate;

use phpcassa\UUID;
use phpcassa\Schema\DataType\LongType;

class EMPS_Cassandra
{
    public $client;
    public $keyspace;
    public $operational = false;

    public $default_consistency, $consistency;
    public $default_compression, $compression;

    public $transport;

    private $types = array(
        "BytesType" => array("ascii" => true),
        "LongType" => array("bigint" => true),
        "IntegerType" => array("varint" => true),
        "Int32Type" => array("int" => true),
        "FloatType" => array("float" => true),
        "DoubleType" => array("double" => true),
        "AsciiType" => array("ascii" => true),
        "UTF8Type" => array("text" => true, "varchar" => true),
        "TimeUUIDType" => array("timeuuid" => true),
        "LexicalUUIDType" => array("uuid" => true),
        "UUIDType" => array("uuid" => true),
        "DateType" => array("timestamp" => true),
        "CounterColumnType" => array("counter" => true),
        "TimestampType" => array("timestamp" => true),
    );

    public function connect()
    {
        global $emps, $emps_cassandra_config;

        try {
            $port = $emps_cassandra_config['port'];
            if (!$port) {
                $port = 9160;
            }
            $socket = new TSocket($emps_cassandra_config['host'], $port);
            $this->transport = new TFramedTransport($socket);
            $protocol = new TBinaryProtocolAccelerated($this->transport);
            $this->client = new CassandraClient($protocol);
            $this->transport->open();

            $this->keyspace = $emps_cassandra_config['keyspace'];

            $this->compression = $this->default_compression = Compression::NONE;
            $this->consistency = $this->default_consistency = ConsistencyLevel::ONE;

            $this->client->set_keyspace($this->keyspace);

        } catch (TException $tx) {
            print 'TException: ' . $tx->why . ' Error: ' . $tx->getMessage() . "\n";
            echo $emps->traceback($tx);
            exit();
        } catch (cassandra\InvalidRequestException $tx) {
            print 'TException: ' . $tx->why . ' Error: ' . $tx->getMessage() . "\n";
            echo $emps->traceback($tx);
            exit();
        }

        $this->operational = true;

        unset($emps_cassandra_config);
    }

    public function disconnect()
    {
        $this->transport->close();
    }

    public function __construct()
    {
        $this->connect();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function transform_value($raw_value, $types, $name)
    {
        $type = $types[$name];

        if ($type == 'org.apache.cassandra.db.marshal.LongType') {
            $value = LongType::unpack($raw_value);
            return $value;
        }

        return $raw_value;
    }

    public function query($q)
    {
        global $emps;
        try {
            $r = $this->client->execute_cql3_query($q, $this->compression, $this->consistency);
            if ($r->type == 1) {
                $result = array();
                $result['raw'] = $r;
                $rows = array();

                foreach ($r->rows as $row) {
                    $nrow = array();
                    foreach ($row->columns as $col) {
                        $raw_value = $col->value;

                        $value = $this->transform_value($raw_value, $r->schema->value_types, $col->name);

                        $nrow[$col->name] = $value;
                    }
                    $rows[] = $nrow;
                }

                $result['rows'] = $rows;

                return $result;
            }
        } catch (TException $tx) {
            print 'TException: ' . $tx->why . ' Error: ' . $tx->getMessage() . "\n";
            echo $emps->traceback($tx);
            exit();
        }
        return false;
    }

    public function get_count($r)
    {
        $count = $r['rows'][0]['count'];
        return $count;
    }

    public function same_type($type, $long_type)
    {
        $x = explode(".", $long_type);
        $lt = $x[count($x) - 1];
//		echo "Is $lt the same as ".$type."?\r\n";
        $types = $this->types[$lt];
        if (isset($types[$type])) {
            return true;
        }
        return false;
    }

    public function ensure_structure($table)
    {
        $q = "SELECT * from system.schema_columnfamilies where keyspace_name='" . $this->keyspace . "' and columnfamily_name='" . $table['name'] . "'";
        $r = $this->query($q);
        if (count($r['rows']) == 0) {
            // no such table

            $q = "create table " . $table['name'] . " (\r\n";
            foreach ($table['columns'] as $col) {
                $x = explode("|", $col);
                $name = trim($x[0]);
                $type = trim($x[1]);
                $extra = trim($x[2]);
                $comma = ",";
                $q .= $name . " " . $type . " " . $extra . $comma . "\r\n";
            }
            if (isset($table['primary_key'])) {
                $q .= "primary key (" . $table['primary_key'] . ")\r\n";
            }
            $q .= ")";

            $r = $this->query($q);
            echo "Table created: " . $table['name'] . "\r\n";

        } else {
            // table exists
            echo "Table already exists: " . $table['name'] . "\r\n";
            $q = "SELECT * from system.schema_columns where keyspace_name='" . $this->keyspace . "' and columnfamily_name='" . $table['name'] . "'";
            $r = $this->query($q);
            $existing = array();
            foreach ($r['rows'] as $col) {
                $existing[$col['column_name']] = $col;
            }
            foreach ($table['columns'] as $col) {
                $x = explode("|", $col);
                $name = trim($x[0]);
                $type = trim($x[1]);
                $extra = trim($x[2]);
                if (!isset($existing[$name])) {
                    echo "Column $name does not exist, creating...\r\n";
                    $this->query("alter table " . $table['name'] . " add " . $name . " " . $type . " " . $extra);
                } else {
                    $col = $existing[$name];
                    if (!$this->same_type($type, $col['validator'])) {
                        echo "Changing column $name type...\r\n";
                        $this->query("alter table " . $table['name'] . " alter " . $name . " type " . $type);
                    }
                }
            }
        }

        $this->ensure_indexes($table);
    }

    public function ensure_indexes($table)
    {
        if (!isset($table['index'])) {
            return false;
        }
        $q = "SELECT * from system.schema_columns where keyspace_name='" . $this->keyspace . "' and columnfamily_name='" . $table['name'] . "'";
        $r = $this->query($q);
        foreach ($r['rows'] as $col) {
            if ($col['index_name']) {
                $existing[$col['column_name']] = $col;
            }
        }
        foreach ($table['index'] as $idx) {
            $x = explode("|", $idx);
            $name = trim($x[0]);
            if (!isset($existing[$name])) {
                echo "Adding index to " . $name . "...\r\n";
                $index_name = $table['name'] . "_" . $name;
                $this->query("create index " . $index_name . " on " . $table['name'] . " (" . $name . ")");
            }
        }
    }
}

?>