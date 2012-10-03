<?php
/**
 * class QueryExplain
 * rough utility class to get query explain plan and extract table names from
 * abitrary sql so we can run show create table on them.
 *
 * This class needs a user defined method to find the database connection info
 * from the query and a bit of other data stored with the query_review_history table
 *
 * The class is used as follows:
 *
 * $result = $mysqli->query("SELECT sample, hostname_max, database_max FROM query_review_history WHERE checksum=1");
 * $row = $result->fetch_assoc();
 *
 * $explainer = new QueryExplain($callback, $row);
 * print $explainer->explain();
 *
 * where $callback might look like:
 * $callback = function(array $sample)
 * {
 *      return array(
 *          'host'  => $sample['hostname_max'],
 *          'db'    => $sample['database_max'],
 *          'user'  => 'username',
 *          'password' => 'password',
 *      );
 * }
 *
 * The callback funtion will always take one array argument, and it needs to return
 * and array with the following keys defined: host,db,user,password; and optionally: port
 *
 * Because the object takes data associated with one query to return the db connection
 * info, that means a QueryExplain object will always only be valid for one query,
 * and will maintain its own connection
 *
 * Sharing connection objects would be advantageous if you want to explain many queries
 * which could be from the same database; but that's not needed for it's current use case.
 *
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 *
 */

require "QueryTableParser.php";
require_once "QueryRewrite.php";

class QueryExplain {

    private $get_connection_func;
    private $mysqli;
    private $conf;
    private $query;

    private static $CONNECT_TIMEOUT = 1;


    /**
     * Constructor.  See class documentation for explaination of the parameters
     *
     * @param callback $get_connection_func     The callback function
     * @param array $sample     array of information about the query
     * @throws Exception  if a database connection cannot be made
     */
    function __construct($get_connection_func, $sample) {
        $this->get_connection_func = $get_connection_func;
        if (!is_callable($this->get_connection_func)) {
            return "func not callable:\n" . print_r($this->get_connection_func, true);
        }
        $this->conf = call_user_func($this->get_connection_func, $sample);
        $this->connect();
        $this->query = $sample['sample'];
    }

    /**
     * Try to parse the real table names out of a sql query
     *
     * @return array the list of tables in the query
     */
    public function get_tables_from_query() {
        $parser = new QueryTableParser();
        return $parser->parse($this->query);
    }

    /**
     * Extract the table names from a query, and return the result of
     * SHOW CREATE TABLE tablename;
     *
     * @return string  the create table statements, or an error message
     */
    public function get_create() {
        if (!isset($this->mysqli)) {
            return null;
        }


        $tables = $this->get_tables_from_query($this->query);
        if (!is_array($tables)) {
            return $tables;
        }
        $create_tables = array();
        foreach ($tables as $table) {
            $result = $this->mysqli->query("SHOW CREATE TABLE {$table}");
            if (is_object($result) and $row = $result->fetch_array()) {
                $create_tables[] = $row[1];
            }
        }

        return join("\n\n", $create_tables);
    }

    /**
     * Extract the table names and the return the result of
     * SHOW TABLE STATUS LIKE 'tablename' for each table;
     *
     * @return null
     */
    public function get_table_status() {
        if (!isset($this->mysqli)) {
            return null;
        }

        $tables = $this->get_tables_from_query($this->query);
        $table_status = array();
        foreach ($tables as $table) {
            $sql = "SHOW TABLE STATUS LIKE '{$table}'";
            $result = $this->mysqli->query($sql);
            if (is_object($result) and $row = $result->fetch_assoc()) {
                $str = '';
                foreach ($row as $key => $value) {
                    $str .= sprintf("%20s : %s\n", $key, $value);
                }
                $table_status[] = $str;
            }
        }
        return join("\n\n", $table_status);
    }

    /**
     * If the given query is a SELECT statement, return the explain plan
     *
     * @return null|string The explain plan, or an error message
     */
    public function explain() {
        if (!isset($this->mysqli)) {
            return null;
        }
		
        try {
            $result = $this->explain_query($this->query);
			
            if ($this->mysqli->errno) {
                return $this->mysqli->error . " (" . $this->mysqli->errno . ")";
            }

            if (!$result) {
                return "unknown error getting explain plan\n";
            }
			
            return $this->result_as_table($result);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Attempt to connect to the connection info returned from the callback function
     * used to construct the object.
     *
     * @return boolean  true if successful
     * @throws Exception    throws errors on connect to the database
     */
    private function connect() {
        $required = array('host', 'user', 'password', 'db');
        foreach ($required as $r) {
            if (!isset($this->conf[$r])) {
                throw new Exception("Missing field {$r}");
            }
        }
		
        try {
            $this->mysqli = new mysqli();
            $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::$CONNECT_TIMEOUT);
            $this->mysqli->real_connect(
                    $this->conf['host'], $this->conf['user'], $this->conf['password'], $this->conf['db'], $this->conf['port']
            );
        } catch (Exception $e) {
            throw new Exception(
                    sprintf("Timeout connecting to mysql on %s:%s", $this->conf['host'], $this->conf['port'])
            );
        }

        if ($this->mysqli->connect_errno || !$this->mysqli) {
            throw new Exception("Connection error: " . $this->mysqli->connect_error . "(" . $this->mysqli->connect_errno . ")");
        }

        return true;
    }

    /**
     * Execute EXPLAIN $query and return the result
     * @return MySQLi_Result    the result handle
     */
    private function explain_query() {
		$Query = new QueryRewrite($this->query);
		$explain = $Query->asExplain();
		
		if (is_null($explain))
			return null;
		
        return $this->mysqli->query($explain);
    }

    /**
     * given a mysqli result handle, format a string to look like the mysql cli
     * type tables
     * @param   {MySQLi_Result}     $result     The result set handle
     * @return {string}     The formatted result set string
     * */
    function result_as_table($result) {
        $sizes = array();
        $values = array();
        $columns = array();

        while ($row = $result->fetch_assoc()) {
            foreach ($row as $col_name => $value) {
                $len = strlen($value);
                if ($len > $sizes[$col_name]) {
                    $sizes[$col_name] = $len;
                }

                $columns[$col_name] = $col_name;
            }
            $values[] = $row;
        }

        foreach ($columns as $col => $count) {
            $len = strlen($col);
            if ($len > $sizes[$col]) {
                $sizes[$col] = $len;
            }
        }

        $column_order = array_keys($columns);

        $table = self::make_rule($sizes, $column_order);
        $table .= self::make_row($sizes, $columns, $column_order);
        $table .= self::make_rule($sizes, $column_order);

        foreach ($values as $row) {
            //		print_r(array_values($row));
            $table .= self::make_row($sizes, $row, $column_order);
            $table .= self::make_rule($sizes, $column_order);
        }

        return $table;
    }

    /**
     * utility method for result_as_table
     */
    private static function make_row(array $sizes, array $values, array $order) {
        $row_start = '|';
        $row_end = '|';
        $col_sep = '|';
        $col_pad = ' ';

        $new_values = array();
        foreach ($order as $col) {
            $value = $values[$col];
            $size = $sizes[$col];
            $new_values[] = $col_pad . str_pad($value, $size, $col_pad, STR_PAD_RIGHT) . $col_pad;
        }

        return $row_start . join($col_sep, $new_values) . $row_end . "\n";
    }

    /**
     * utility method for result_as_table
     * */
    private static function make_rule(array $sizes, array $order) {
        $rule_fill = '-';
        $rule_sep = '+';
        $new_values = array();
        foreach ($order as $col) {
            $new_values[] = str_repeat($rule_fill, $sizes[$col] + 2);
        }
        return $rule_sep . join($rule_sep, $new_values) . $rule_sep . "\n";
    }

}

?>
