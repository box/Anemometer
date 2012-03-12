<?php

/**
 * class MySQLTableReport
 * Generic reporting class.  Given a configuration file, that describes the tables
 * and fields to be searched, and information to connect to a database, take form data
 * and generate an SQL query to run.
 * 
 * The report object is made to address a certain class of reports:  Time series data,
 * to aggregate by some arbitrary column and return the SUM/MIN/MAX/AVG/etc of other
 * columns.
 * 
 * Basic usage:
 * 
 * $report = new MySQLTableReport( $datasource, $tables, $report);
 *  
 * // run the query and get the result and header data
 * $result  = $report->execute($sql);
 * $columns = $report->get_column_names();
 * 
 * // ... display as desired.
 * 
 * 
 * the parameters above have the following forms:
 * 
 * $datasource = array(
 *      'host'  => $host, // the mysql server hostname
 *      'port'  => $port, // optional port number
 *      'user'  => $user, // the user credential
 *      'password' => $pass, // the password for the user
 *      'db'    => $db, // the database name
 * );
 * 
 * $tables = array(
 *      'fact_table_name'       => 'fact',      // table_name => alias
 *      'dimension_table_name'  =>  'dimension', // table_name => alias
 * )
 * 
 * The aliases used for tables are mostly anything you choose *except* that one 
 * table must have the alias 'fact'.  This is considered the root table to which
 * any additional tables can be joined. There can be only one fact table, but you can
 * have any number of dimension tables as long as each have unique aliases
 * 
 * $report = array(
 * 
 *      // the JOIN clause for any dimension tables.  Specify the exact clause used
 *      // for each table alias
 *      'join'      => array(
 *          'dimension'     =>  "USING (id)",       
 *      ),
 * 
 *      // fields for the tables; defined by the table's alias and not the real table name
 *      // this allows the exact same report to be used on tables with the same structure
 *      // but different names
 *      'fields'    => array( 
 *          'fact'  => array(
 *              'name'      => 'clear|where',
 * 
 *              // note that these aren't field names in the table, but we need
 *              // a place to put them for the form processor to handle them
 *              // the filters here will make sure the data gets added to the right
 *              // part of the query, and not the WHERE clause like other fields.
 *              'group'     => 'group',
 *		'order'     => 'order',
 *		'having'    => 'having',
 *		'limit'     => 'limit',
 *          ),
 * 
 *          'dimension' => array(
 *              'date'      => 'date_range|clear|where',
 *              'price'     => 'ge|clear|where'
 *          )
 * 
 *      ), // end fields
 * 
 *      // custom fields are allowed as well     
 *      'custom_fields' => array(
 *              'epoch'     =>  'FROM_UNIXTIME(date)',
 *              'snippet'   =>  'LEFT(info,15)'
 *      )
 * );
 * 
 * 
 * @todo describe the config format in more detail 
 * 
 * @package MySQLTableReport
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-01-01
 * @license contact the author for details and permissions
 * 
 * @todo create a base class
 * @todo create a pear package out of this 
 * @todo abstract aggregate function handling (or move to the config)
 */
class MySQLTableReport {

    private $datasource;
    private $tables;
    private $report;
    private $form_fields;
    private $pivot = array();
    private $form_data_processed = false;
    private $sql;
    
    private $select;
    private $from;
    private $join;
    private $where;
    private $having;
    private $order;
    private $limit;
    private $raw_where;
    
    private static $CONNECT_TIMEOUT = 5;
    

    /**
     * create a new instance, pass configuration information describing the datasource 
     * and the report tables and fields.
     * 
     * 
     * @param array $datasource  Database connection information required :host,user,password,db; optional: port
     * @param array $tables Tables to use for this report.  format is array( 'table_name'   => 'alias' )  there must be at least one "fact" table, and optionally a "dimension" table
     * @param array $report config array describing the table structure and other options
     */
    function __construct($datasource, $tables, $report) {
        $this->datasource = $datasource;
        $this->tables = $tables;
        $this->report = $report;
        $this->form_fields = $report['fields'];

        // try to connect to the database
        if ($datasource != null) {
            $this->connect_to_datasource();
        }
        
        // check for some basic validity; this will throw an exception if there
        // is no fact table defined.
        $this->get_table_by_alias('fact');
        
        $this->init_report();
    }

    /**
     * reset internal variables
     *  
     */
    private function init_report()
    {
        $this->select = array();
        $this->from   = array();
        $this->join   = array();
        $this->where  = array();
        $this->group  = null;
        $this->having = null;
        $this->order  = null;
        $this->limit  = null;
        $this->raw_where = null;
        $this->form_data_processed = false;
    }
    
    /**
     * pivot operations require some setup -- this defines the list of values to turn into 
     * additional columns when we ask the report to pivot a column.
     * 
     * @param string $col_name  the name of the column to pivot
     * @param array $values the list of values
     */
    public function set_pivot_values($col_name, array $values) {
        $this->pivot[$col_name] = $values;
    }

    /**
     * return the list of values for a given pivot column
     * 
     * @param string $col_name  the name of the pivot column
     * @return array    the list of values defined by set_pivot_values 
     */
    public function get_pivot_values($col_name) {
        return $this->pivot[$col_name];
    }

    /**
     * make a connection to the database, die with an error if this doesn't work
     * 
     * @todo add a timeout 
     */
    private function connect_to_datasource() {
        $ds = $this->datasource;
        $this->mysqli = new mysqli();
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::$CONNECT_TIMEOUT);
        $this->mysqli->real_connect($ds['host'], $ds['user'], $ds['password'], $ds['db'], $ds['port']);
        
        if ($this->mysqli->connect_errno) {
            throw new Exception($this->mysqli->connect_error);
        }
    }

    /**
     * returns the list of table names, not the aliases
     * 
     * @return array
     */
    public function get_tables() {
        return array_keys($this->tables);
    }
    
    /**
     * gets the concrete name of a table for the given alias
     * 
     * @param string $alias     The alias name to fetch the table for
     * @return string   The real table name
     * @throws Exception if the alias doesn't exist
     */
    public function get_table_by_alias($alias)
    {
        foreach ($this->tables as $table_name => $a)
        {
            if ($a == $alias)
            {
                return $table_name;
            }
        }
        throw new Exception("No alias {$alias}");
    }

    /**
     * return the list of form fields defined by the configuration parameters used
     *  to construct this object.  Field names are prefixed by the table *alias*
     * 
     * so if the configuration section looked like :
     * 'fields' => array(
     *      'fact'  => array(
     *          'checksum'  => '...',
     *      ),
     *      'dimension' => array(
     *          'hostname'  => '...',
     *      ),
     * 
     * The result would be an array with the values ('fact-checksum', 'dimension-hostname').
     * 
     * These are the form field names that will be checked to build the search parameters.
     * 
     * @return array the form fields 
     */
    public function get_form_fields() {
        $fields = array();
        $tables = $this->tables;
        foreach ($this->form_fields as $alias => $values) {
            $fields = array_merge($fields, array_map(
                            function ($x) use ($alias) {
                                return $alias . "-{$x}";
                            }, array_keys($values)
                    )
            );
        }
        return $fields;
    }

    /**
     * returns a list of custom fields names.
     * Custom fields are additional columns that can be used in the SELECT clause,
     * but not as WHERE or other conditions.  They are defined in the configuration
     * used to create the object
     * 
     * @return array the custom field list 
     */
    public function get_custom_fields() {
        return array_keys($this->report['custom_fields']);
    }

    /**
     * select the field names for the report tables from the database.
     * 
     * @param string $table_name optional table name.  If none is provided,  all tables defined in the report will be queried.
     * @return array the list of columns defined in the database tables. 
     */
    public function get_table_fields($table_name = null) {
        // now find all colums from the tables
        if (isset($table_name)) {
            $tables = array(&$table_name);
        } else {
            $tables = array();
            $count = count($this->tables);
            $t = array_keys($this->tables);
            for ($i = 0; $i < $count; $i++) {
                $tables[] = & $t[$i];
            }
        }

        $values = array_merge(array(str_repeat('s', count($tables) + 1), &$this->datasource['db']), $tables);

        $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME IN (" . join(',', array_map(function ($x) {
                                    return '?';
                                }, $tables)) . ")";

        $stmt = $this->mysqli->prepare($sql);
        call_user_func_array(array($stmt, 'bind_param'), $values);
        $stmt->execute();
        $stmt->bind_result($table_name, $col_name);

        $columns = array();
        while ($stmt->fetch()) {
            $columns[] = "{$col_name}";
        }

        return $columns;
    }

    /**
     * given a table and column, find all the unique values.  This is a utility
     * method often used when building dropdown lists on a search form, or getting
     * values for pivot operations.
     * 
     * @param string $table the table name
     * @param string $colname the column name
     * @return array  the list of unique values  
     */
    public function get_distinct_values($table, $colname) {
        //print "getting distinct $colname from $table<br>";
        $result = $this->mysqli->query("SELECT DISTINCT `{$colname}` FROM `{$table}`");
        $values = array();
        while ($row = $result->fetch_array()) {
            $values[] = $row[0];
        }
        return $values;
    }

    /**
     * return an associate array with form_field_name => value for all fields.
     * 
     * @return array  the array of field names and values 
     */
    public function get_form_field_values() {
        $fields = $this->get_form_fields();
        $return = array();
        foreach ($fields as $f) {
            $return[$f] = get_var($f);
        }
        return $return;
    }

    /**
     * add a column to the select field list
     * @param string $field the field name
     * @param string $alias the field alias
     * @param string $aggregate how to optionally aggregate values in this column
     * @return \MySQLTableReport 
     */
    public function select($field, $alias, $aggregate) {
        $this->select[] = array($field, $alias, $aggregate);
        return $this;
    }

    /**
     * define the primary table to select from
     * 
     * @param array $table  The table to select from; the format is array(table_name, alias) 
     * @return \MySQLTableReport 
     */
    public function from(array $table) {
        $this->from = $table;
        return $this;
    }

    /**
     * add a table to the JOIN clause
     * 
     * @param array $table  The table to join; the format is array(table_name, alias)
     * @return \MySQLTableReport 
     */
    public function join(array $table) {
        $this->join[] = $table;
        return $this;
    }

    /**
     * add a condition to the WHERE clause.
     * 
     * @param string $key   the full column name including table alias
     * @param string $var_name  the name of the form variable for this column
     * @param string $value the form value
     * @param string $op    the conditional operator to use; default =
     * @return \MySQLTableReport 
     */
    public function where($key, $var_name, $value, $op = null) {
        if ($op == null) {
            $op = '=';
        }
        $this->where[] = array($key, $value, $op);
        return $this;
    }

    /**
     * set the GROUP BY expression
     * 
     * @param string $col_name      the name of the form field as a column
     * @param string $var_name        the form variable name
     * @param string $expression    the group by expression
     * @return \MySQLTableReport 
     */
    public function group($col_name, $var_name, $expression) {
        //print "called group with $expression<br>";
        $this->group = $expression;
        return $this;
    }

    /**
     * set the ORDER BY clause
     * 
     * @param string $col_name      the name of the form field as a column
     * @param string $var_name        the form variable name
     * @param string $expression    the order by expression
     * @return \MySQLTableReport 
     */
    public function order($key, $field, $expression) {
        $this->order = $expression;
        return $this;
    }

    /**
     * set the LIMIT clause
     * 
     * @param string $col_name      the name of the form field as a column
     * @param string $var_name        the form variable name
     * @param string $expression    the limit expression
     * @return \MySQLTableReport 
     */
    public function limit($key, $field, $expression) {
        $this->limit = $expression;
        return $this;
    }

    /**
     * set the HAVING clause
     * 
     * @param string $col_name      the name of the form field as a column
     * @param string $var_name      the form variable name
     * @param string $expression    the havin expression
     * @return \MySQLTableReport 
     */
    public function having($key, $field, $expression) {
        $this->having = $expression;
        return $this;
    }

    /**
     * raw_where is an unprocessed string that is added to the WHERE clause
     * 
     * @param string $key   ignored
     * @param string $field ignored
     * @param string $expression    The raw WHERE expression
     */
    public function raw_where($key, $field, $expression) {
        $this->extra_where = $expression;
    }

    /**
     * preform a pivot on a column.  Get the unique list of values
     * and return  them as a conditional aggregate expression to be added to the
     * select clause.  Right now only one aggregate type is supported: SUM
     * Also a little black magic is used to get the column name from the synthetic 
     * column name needed here.  The column in the form and config should be called:
     * pivot-{$column_name} and it's value should be the column to return when the expression is true.
     * 
     * For example, if you have a hostname column with a count of signups, and you want to 
     * pivot on the hostname and return the aggregate signups for each host as its own column,
     * then the form field should look like:
     * 
     * <input type="checkbox" name="dimension-pivot-hostname" value="signups" />  Count signups per-host
     * 
     * The report object config would look like:
     * 
     * 'fields' => array(
     *      'dimension' => array(
     *          'pivot-hostname'    => 'pivot|select',
     *      )
     * )
     * 
     * Then remember to set the unique list of value for this pivot operation:
     * 
     * $report = new MySQLTableReport( ... );
     * $hosts = $report->get_distinct_values('dimension','hostname');
     * $report->set_pivot_values('dimension-pivot-hostname', $hosts);
     * 
     * 
     * @param string $col_name      The name of the pivot column
     * @param string $var_name      The field variable name
     * @param string $expression    The column to return in the IF($col_name}='value' ... ) expression
     * @return \MySQLTableReport|string 
     */
    public function pivot($col_name, $var_name, $expression) {
        if (!isset($expression)) {
            return $this;
        }

        //print "in pivot values ($col_name) ($var_name) ($expression)<br>\n";
        $columns = array();
        if (!isset($expression)) {
            $expression = '1';
        }
        $col_name = preg_replace("/pivot-/", "", $col_name);
        $values = $this->get_pivot_values($var_name);
        //print_r($values);
        foreach ($values as $v) {
            $columns[] = array("IF({$col_name}='" . addslashes($v) . "',{$expression},0)", $v, 'SUM');
        }
        return $columns;
    }

    /**
     * look for a range of date values for the given column, and return
     * values to be added to the WHERE clause
     * 
     * This is used when you have a column like "invoice_date" in the report,
     * but what you really want to search for is a range of dates bewteen a given 
     * start and end date.
     * 
     * To do that, create form fields with _start and _end added to the name,
     * and pass the column to this processor.  It will search for the appropriate 
     * form fields and build the range.
     * 
     * <input type="text" name="dimension-invoice_date_start">
     * <input type="text" name="dimension-invoice_date_end">
     * 
     * The config settings would look like:
     * 
     * 'fields' => array(
     *      'dimension' => array(
     *          'invoice_date'  =>  'date_range|clear|where',
     *      )
     * )
     * 
     * 
     * @param string $col_name  the base column name
     * @param string $var_name  the base field variable name
     * @param string $expression    ignored
     * @return array        the list of expressions to pass to the where function 
     */
    public function date_range($col_name, $var_name, $expression) {
        return array(
            array($col_name, "{$var_name}_start", get_var("{$var_name}_start"), '>='),
            array($col_name, "{$var_name}_end", get_var("{$var_name}_end"), '<=')
        );
    }

    /**
     * Remove blank strings as values in form fields.
     * 
     * Most cases, the forms you create can have empty fields which mean those
     * conditions should be omitted from the WHERE clause.  However, the form
     * will send and empty string.  When you want an empty field to be removed from 
     * the WHERE clause, pass it through the clear filter first.
     * 
     * 'fields' => array(
     *      'dimension' => array(
     *          'hostname'  =>  'clear|where', // if hostname is blank, do not include it in the query.
     *      )
     * )
     * 
     * @param string $col_name      the name of the form field as a column
     * @param string $var_name      the form variable name
     * @param string $expression    the value of the field
     * @return \MySQLTableReport 
     */
    public function clear($col_name, $var_name, $expression, $op = null) {
        if ($expression == '') {
            $expression = null;
        }
        return array(array($col_name, $var_name, $expression, $op));
    }

    /**
     * apply a "greater than or equal to" operator to a WHERE condition, instead
     * of the default equality matching
     * 
     * By default a configuration section like this would produce equality matching:
     * 
     * 'fields' => array(
     *      'dimension' => array(
     *          'price'  =>  'clear|where', // generates SQL such as: WHERE price = <some value>
     *      )
     * )
     * 
     * If you need a range of values, include the appropriate operator as a filter:
     * 
     * 'fields' => array(
     *      'dimension' => array(
     *          'price'  =>  'clear|ge|where', // generates SQL such as: WHERE price >= <some value>
     *      )
     * )
     * 
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function ge($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, $expression, '>='));
    }

    /**
     * less than or equal to: see documentation for ge()
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function le($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, $expression, '<='));
    }

    /**
     * greater than: see documentation for ge()
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function gt($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, $expression, '>'));
    }

    /**
     * less than: see documentation for ge()
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function lt($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, $expression, '<'));
    }

    /**
     * not equals: see documentation for ge()
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function ne($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, $expression, '!='));
    }

    /**
     * like: see documentation for ge()
     * @param string $col_name  The column name
     * @param string $var_name  The field variable name
     * @param string $expression    The field value
     * @return array    condition to pass to next filter
     */
    public function like($col_name, $var_name, $expression) {
        return array(array($col_name, $var_name, isset($expression) ? "%{$expression}%" : null, 'LIKE'));
    }

    /**
     * given a column name, try to guess the aggregate function name.  For now this
     * expects columns with a format like
     * colname_cnt
     * colname_max
     * colname_avg
     * 
     * It checks the last letters after an underscore and returns an aggregate function that most
     * closely matches.  Supported types are:
     *  _sum _cnt = SUM
     *  _avg, _median = AVG
     *  _min, _95, _stddev = MIN
     * _max = MAX
     * 
     * 
     * @todo make this a plugin / configurable
     * @param type $name
     * @return null|string 
     */
    private function get_column_aggregate_function($name) {
        if (!preg_match("/_([^_]+)$/", $name, $regs)) {
            return null;
        }

        switch ($regs[1]) {
            case 'sum':
            case 'cnt':
                return 'SUM';
            case 'avg':
            case 'median':
                return 'AVG';
            case 'min':
            case '95':
            case 'stddev':
                return 'MIN';
            case 'max':
                return 'MAX';
            default:
                return null;
        }
    }

    /**
     * Read all form data and process values. This will be called automatically
     * by query() and execute() methods.
     * 
     */
    public function process_form_data() {
        if ($this->form_data_processed) {
            return;
        }

        $values = $this->get_form_field_values();
        $fields = $this->get_form_fields();

        // SELECT
        $select = array();
        foreach (get_var('table_fields') as $f) {
            if (isset($this->report['custom_fields'][$f])) {
                $select[$f] = array($this->report['custom_fields'][$f], $f, null);
            } else {
                $select[$f] = array($f, null, $this->get_column_aggregate_function($f));
            }
        }

        foreach ($select as $field => $spec) {
            $this->select($spec[0], $spec[1], $spec[2]);
        }


        // FROM
        $count = 0;
        foreach ($this->tables as $table => $alias) {
            if ($count == 0) {
                $this->from(array($table, $alias));
            } else {
                $this->join(array($table, $alias, $this->report['join'][$alias]));
            }
            $count++;
        }

        // WHERE
        foreach ($this->tables as $table => $alias) {
            foreach ($this->form_fields[$alias] as $field => $config) {
                $var_name = "{$alias}-{$field}";
                $col_name = "{$alias}.{$field}";
                $functions = preg_split("/\|/", $config);
                $args = array(array($col_name, $var_name, $values[$var_name]));
                //print "checking fields {$var_name}=". $args[0][1]. "<br>";
                //print_r($functions);
                foreach ($functions as $func) {
                    $next_args = array();
                    foreach ($args as $arg) {
                        //print "calling $func<br>\n";
                        //prettyprint(print_r($arg,true));
                        $array_result = call_user_func_array(array($this, $func), $arg);
                        if (is_array($array_result)) {
                            $next_args = array_merge($next_args, $array_result);
                        }
                    }
                    // set up result args for next function
                    // if the function returns an object === $this, then we can't continue
                    $args = $next_args;
                    if (is_object($args)) {
                        break;
                    }
                }
            }
        }
        $this->form_data_processed = true;
    }

    /**
     * removes all where conditions where the value of the expression is null.
     */
    private function filter_where() {
        if (is_array($this->where)) {
            $this->where = array_filter(
                    $this->where, function ($y) {
                        if (isset($y[1])) {
                            return true;
                        }
                    }
            );
        }
    }

    /**
     * generate the SQL query and return it as a string.
     * 
     * @return string  the SQL query build by this report object 
     */
    public function query() {
        $this->process_form_data();

        if (isset($this->sql)) {
            return $this->sql;
        }


        // SELECT
        $sql = "SELECT ";
        if (!isset($this->select) OR count($this->select) == 0) {
            $sql .= "*";
        } else {
            // select values are array( col_name, alias_name, aggregate function)
            $sql .= join(
                            ",\n  ", array_map(
                                    function ($k) {

                                        if (isset($k[2])) {
                                            // aggregate function on the column
                                            return sprintf("%s(%s) AS `%s`", $k[2], $k[0], isset($k[1]) ? $k[1] : $k[0]);
                                        }
                                        // non aggregate column
                                        return "" . $k[0] . "" . ( isset($k[1]) ? ' AS `' . $k[1] . '`' : '');
                                    }, array_values($this->select)
                            )
                    ) . "\n";
        }

        // FROM
        $sql .= " FROM `" . $this->from[0] . "` AS `" . $this->from[1] . "` \n";

        // JOIN
        for ($i = 0; $i < count($this->join); $i++) {
            $key = $this->join[$i][0];
            $alias = $this->join[$i][1];
            $on = $this->join[$i][2];
            $sql .= " JOIN `{$key}` AS `{$alias}` {$on} \n";
        }

        // WHERE
        $this->filter_where();
        if (count($this->where) >= 0) {
            $sql .= " WHERE " . join("\n  AND ", array_map(
                                    function ($x) {
                                        return $x[0] . ' ' . $x[2] . ' "' . $x[1] . '"';
                                    }, $this->where
                            )
                    ) . "\n";
        }


        // EXTRA TEXT INPUT FOR WHERE
        if (isset($this->extra_where) and $this->extra_where != '') {
            if (count($this->where)) {
                $sql .= " AND (" . $this->extra_where . ") ";
            } else {
                $sql .= " WHERE (" . $this->extra_where . ") ";
            }
        }

        // GROUP / ORDER / HAVING / LIMIT
        $aditional_clauses = array(
            'GROUP BY' => $this->group,
            'ORDER BY' => $this->order,
            'HAVING' => $this->having,
            'LIMIT' => $this->limit
        );
        foreach ($aditional_clauses as $clause => $value) {
            if (isset($value) and $value != '') {
                $sql .= " {$clause} {$value} \n";
            }
        }
        //print $sql;
        $this->sql = $sql;
        return $sql;
    }

    /**
     * retuns a list of all column names.  These will be exactly the same as
     * the columns returned by the query.
     * 
     * @return srray    the list of column names 
     */
    public function get_column_names() {
        return array_map(function ($k) {
                            return $k[1] != '' ? $k[1] : $k[0];
                        }, array_values($this->select));
    }

    /**
     * Execute the generated query on the configured databse and return
     * a result handle
     * 
     * @param string $sql   optional sql to execute.
     * @return array    array that contains the result set
     * @throws Exception if there is an error executing the query
     */
    public function execute($sql = null) {
        if (!isset($sql)) {
            $sql = $this->query();
        }

        $result = $this->mysqli->query($sql);
        $this->check_mysql_error($result);

        $result_data = array();
        while ($row = $result->fetch_assoc()) {
            $result_data[] = $row;
        }
        $result->free();
        return $result_data;
    }

    /**
     * check the result of a mysqli query and throw and excepton if there was an error
     * 
     * @param MySQLi_Result $result  handle to the result set
     * @throws Exception if there was a query error
     */
    private function check_mysql_error($result)
    {
        if ($this->mysqli->errno or !$result)
        {
            throw new Exception($this->mysqli->error." (".$this->mysqli->errno.")");
        }
    }

    /**
     * return a urlencoded string of parameters that were used in this report.
     * 
     * @return string   The url string
     */
    public function get_search_uri() {
        $this->process_form_data();
        
        $run_funcs = array('date_range');
        $params = array();
        // loop through the tables and fields
        foreach ($this->tables as $table => $alias) {
            foreach ($this->report['fields'][$alias] as $field => $def) {
                $handled = false;
                $var_name = "{$alias}-{$field}";
                $col_name = "{$alias}.{$field}";
                $value = get_var($var_name);

                // we have to execute some of the functions here because
                // the actual form fields may differ from the defined field name
                // such as in the case of date ranges, where the report actually looks
                // for field_start and field_end for the values.
                $funcs = preg_split("/\|/", $def);
                foreach ($funcs as $f) {
                    if (in_array($f, $run_funcs)) {
                        $args = $this->$f($col_name, $var_name, $value);
                        foreach ($args as $arg) {
                            $value = $arg[2];
                            if (isset($value) and $value != '') {
                                $params[] = $arg[1] . '=' . urlencode($value);
                            }
                            $handled = true;
                        }
                    }
                }

                if ($handled) {
                    continue;
                }

                if (isset($value) and $value != '') {
                    $params[] = $var_name . '=' . urlencode($value);
                }
            }
        }

        // add table_fields -- the multi-select which defines which columns the report
        // should display
        $table_fields = get_var('table_fields');
        foreach ($table_fields as $t) {
            $params[] = "table_fields%5B%5D={$t}";
        }

        // done, join all params together
        return join("&", $params);
    }
}

?>