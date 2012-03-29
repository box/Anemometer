<?php

require "QueryExplain.php";

/**
 * class AnemometerModel
 *
 * handle getting values from the conf file such as datasources
 * and selecting and updating queries from the query_review table
 *
 *
 * @author Gavin Towey <gavin@box.com> and Geoff Anderson <geoff@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 */
class AnemometerModel {

    private $conf;
    private $datasource_name;
    private $mysqli;
    private $fact_table;
    private $dimension_table;

    /**
     * Constructor.  Initialize the model object
     *
     * @param array $conf   The global config information
     */
    function __construct($conf) {
        $this->conf = $conf;
    }

    /**
     * return the default report action name; usually either report or graph_search
     * @return string       the action name
     */
    public function get_default_report_action() {
        return $this->conf['default_report_action'];
    }

    /**
     * get the default search values for the specified report type
     *
     * @param string $type      The name of the report type
     * @return array    The default values for the search form
     */
    public function get_report_defaults($type = 'report_defaults') {
        return $this->conf[$type];
    }

    /**
     * return the list of review types.  This is a configurable list of short text
     * statuses that can describe the query.
     *
     * @return array    the list of review status types
     */
    public function get_review_types() {
        return $this->conf['review_types'];
    }

    /**
     * Get the list of names for the configured data sources
     * @return array    List of strings that describe the data sources
     */
    public function get_data_source_names() {
        if (is_array($this->conf['datasources'])) {
            return array_keys($this->conf['datasources']);
        }
        return array();
    }

    /**
     * Given a data source name, get the properties for it.
     *
     * @param string $name      The datasource name
     * @return mixed    array of properties, or null if the datasource doesn't exist
     */
    public function get_data_source($name) {
        if (is_array($this->conf['datasources'][$name])) {
            return $this->conf['datasources'][$name];
        }
        return null;
    }

    /**
     * sets the currently active datasource.
     *
     * @param string $name  The name of the datasource
     */
    public function set_data_source($name) {
        $this->datasource_name = $name;
        foreach ($this->conf['datasources'][$name]['tables'] as $key => $alias) {
            if ($alias == 'fact') {
                $this->fact_table = $key;
            } elseif ($alias == 'dimension') {
                $this->dimension_table = $key;
            }
        }
    }

    /**
     * set the current fact and dimension table.  That is the query_review and
     * query_review_history tables.  This is used when we select samples or update
     * a query by its checksum.
     *
     * @param string $fact  The name of the fact table
     * @param string $dimension The name of the dimension table
     */
    public function set_tables($fact, $dimension) {
        $this->fact_table = $fact;
        $this->dimension_table = $dimension;
    }

    /**
     * get the field names for the given report
     *
     * @param string $name  The report name
     * @return array    the table alias and field names
     */
    public function get_form_fields($name) {
        return $this->conf['reports'][$name]['fields'];
    }

    /**
     * get the full config information for the given report.
     *
     * @param string $name      The report name
     * @return array        The configuration information
     */
    public function get_report($name) {
        return $this->conf['reports'][$name];
    }

    /**
     * Return a list of reviewers defined by the config file
     *
     * @return array    The list of reviewers
     */
    public function get_reviewers() {
        return $this->conf['reviewers'];
    }

    /**
     * Query the database and return true if a given checksum exists
     *
     * @param string $checksum      The checksum to check
     * @return boolean      true if it exists, otherwise false
     */
    public function checksum_exists($checksum) {
        $result = $this->mysqli->query("SELECT checksum FROM {$this->fact_table} WHERE checksum='" . $this->mysqli->real_escape_string($checksum) . "'");
        check_mysql_error($result, $this->mysqli);
        if ($result->num_rows) {
            return true;
        }
        return false;
    }

    /**
     * Preform an update query on the given checksum
     *
     * @param string $checksum      The checksum to update
     * @param array $fields         Array of Key => Value pairs to update
     */
    public function update_query($checksum, $fields) {
        $mysqli = $this->mysqli;
        $sql = "UPDATE {$this->fact_table} SET ";
        $sql .= join(
                ',', array_map(
                        function ($x, $y) use ($mysqli) {
                            if ($y == 'NULL') {
                                return "{$x} = NULL";
                            }
                            return "{$x} = \"" . $mysqli->real_escape_string($y) . '"';
                        }, array_keys($fields), array_values($fields)
                )
        );
        $sql .= " WHERE checksum='" . $this->mysqli->real_escape_string($checksum) . "'";
        $res = $this->mysqli->query($sql);
        // @todo ... fix this by making it a local method
        check_mysql_error($res, $this->mysqli);
    }

    /**
     * given a checksum, return the full database row from the fact table for it.
     *
     * @param string $checksum      The checksum to retrieve
     * @return mixed        The row of data, or null
     */
    public function get_query_by_checksum($checksum) {
        $result = $this->mysqli->query("SELECT * FROM {$this->fact_table} WHERE checksum={$checksum}");
        check_mysql_error($result, $this->mysqli);
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        return null;
    }

    /**
     * Retrieve query samples from the history table, ordered from most recent
     *
     * @param string $checksum      The checksum to look up
     * @param int $limit            The number of sample to get (default 1)
     * @param int $offset           The starting record number
     * @return MySQLi_Result        The result handle
     */
    public function get_query_samples($checksum, $limit = 1, $offset = 0) {
        $sql = "SELECT ts_min, ts_max, hostname_max, sample FROM {$this->dimension_table} WHERE checksum=$checksum ORDER BY ts_max DESC LIMIT {$limit} OFFSET {$offset}";
        return $this->mysqli->query($sql);
    }

    /**
     * Try to connect to the datasource,  throw an exception on failure
     * @throws Exception
     */
    public function connect_to_datasource() {
        $ds = $this->conf['datasources'][$this->datasource_name];
        //print "{$this->datasource_name}<br>";
        //print_r($ds);
        $this->mysqli = new mysqli($ds['host'], $ds['user'], $ds['password'], $ds['db'], $ds['port']);
        if ($this->mysqli->connect_errno) {
            throw new Exception($this->mysqli->connect_error);
        }
    }

    /**
     * Create a new query explainer object for the given query sample
     *
     * @param array $sample     The qeury sample
     */
    public function init_query_explainer(array $sample) {
        try
        {
            $this->explainer = new QueryExplain($this->conf['plugins']['explain'], $sample);
        }
        catch (Exception $e)
        {
            // ignore it
        }

    }

    /**
     * try to get the explain plan for a query
     *
     * @param array $sample     The query sample row data
     * @return mixed        Either a string with the explain plan, an error message or null
     */
    public function get_explain_for_sample(array $sample) {
        if (!isset($this->conf['plugins']['explain']) or !is_callable($this->conf['plugins']['explain'])) {
            return null;
        }

        if (!isset($this->explainer)) {
            return null;
        }

        return $this->explainer->explain($sample);
    }

    /**
     * Open a two-way communication with an external script.  Used to send
     * data to the program on STDIN and collect output on STDOUT.
     *
     * @param string $script        The script to invoke
     * @param string $input         The input to send to the script on STDIN
     * @return string   The output from the script
     */
    private function exec_external_script($script, $input) {
        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
        );

        $process = proc_open($script, $descriptorspec, $pipes, "/tmp");
        if (is_resource($process)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $ret_val = proc_close($process);
            return $result;
        }
        return null;
    }

    /**
     * invoke pt-visual-explain and get its output
     *
     * @param string $explain_plan      The explain plan to feed the script
     * @return string       The visual explain output
     */
    public function get_visual_explain($explain_plan) {
        if (!isset($this->conf['plugins']['visual_explain'])) {
            return null;
        }

        if (!file_exists($this->conf['plugins']['visual_explain'])) {
            return "can't find visual explain at " . $this->conf['plugins']['visual_explain'];
        }
        return $this->exec_external_script($this->conf['plugins']['visual_explain'], $explain_plan);
    }

    /**
     * invoke pt-query-advisor and get its output
     *
     * @param string $query  The query to feed the script
     * @return string       The script output
     */
    public function get_query_advisor($query) {
        if (!isset($this->conf['plugins']['query_advisor'])) {
            return null;
        }

        if (!file_exists($this->conf['plugins']['query_advisor'])) {
            return "can't find query advisor at " . $this->conf['plugins']['query_advisor'];
        }

        return $this->exec_external_script($this->conf['plugins']['query_advisor'], $query);
    }

    /**
     * Get the create table definitions for the query
     * @param string $query     the query to process
     * @return string   The create table statements
     */
    public function get_create_table($query) {
        if (!isset($this->conf['plugins']['show_create']) or !$this->conf['plugins']['show_create']) {
            return null;
        }

        if (!isset($this->explainer)) {
            return null;
        }

        return $this->explainer->get_create($query);
    }

    /**
     * Get the table status info for the given query
     * @param string $query     The query to process
     * @return string       The table status info
     */
    public function get_table_status($query) {
        if (!isset($this->conf['plugins']['show_status']) or !$this->conf['plugins']['show_status']) {
            return null;
        }

        if (!isset($this->explainer)) {
            return null;
        }

        return $this->explainer->get_table_status($query);
    }

}

?>
