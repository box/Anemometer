<?php
require "Loader.php";
require "AnemometerModel.php";
require "MySQLTableReport.php";

/**
 * class Anemometer
 *
 * This is the controller class for the Box Anemometer web application.
 *
 * It is designed to closely resemble Codeigniter, so it can be easily added to
 * a CI installation (untested.)
 *
 * Public method represent controller actions, callable through the index.php
 *
 * @author Gavin Towey <gavin@box.com> and Geoff Anderson <geoff@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 *
 */
class Anemometer {

    private $conf;
    private $data_model;
    private $report_obj;
    private $header_printed = false;
    private $exception_select_fields = array('checksum','sample','DIGEST'); // column names which appear in both fact and dimension tables
    private $timezone_offset;

    /**
     * Constructor.  Pass in the global configuration object
     *
     * @param type $conf
     */
    function __construct($conf)
    {
        $this->load = new Loader();
        if (empty($conf))
        {
            return;
        }

        $timezone = ini_get('date.timezone');
        if (!$timezone)
        {
            $system_timezone = exec('date +%Z');
            date_default_timezone_set($system_timezone);
            $timezone = date_default_timezone_get();
        }
        $this->timezone_offset = timezone_offset_get( new DateTimeZone( $timezone ), new DateTime());

        $this->conf = $conf;
        $this->data_model = new AnemometerModel($conf);
        if (array_key_exists('time_columns', $this->conf))
        {
            $this->time_columns = $this->conf['time_columns'];
        }
        else
        {
            $this->time_columns = array();
        }
        $datasource = get_var('datasource');
        if (isset($datasource)) {
            $this->data_model->set_data_source($datasource);
            $this->data_model->connect_to_datasource();
        }

        $this->init_report();
    }

    /**
     * main method for getting report results.  This method can be called as an
     * ajax callback and return the raw data in json format, or it can display
     * a table or graph directly.  All other methods that get report results use this
     * either directly or as an ajax call.
     */
    public function api()
    {
        $checksum_field_name = $this->data_model->get_field_name('checksum');
        $hostname_field =  $this->data_model->get_field_name('hostname');
        $dimension_table = $this->report_obj->get_table_by_alias('dimension');

        // special case for optional pivot on hostname
        // mainly used to graph each host as a series
        if (get_var('dimension-pivot-'.$hostname_field) != null)
        {
            $hosts = $this->report_obj->get_distinct_values($dimension_table, $hostname_field);
            $this->report_obj->set_pivot_values('dimension-pivot-'.$hostname_field, $hosts);
        } else if (get_var('dimension-pivot-'.$checksum_field_name) != null
                   and get_var("dimension-pivot-{$checksum_field_name}-use-values") != null) {
            $values = explode('|',get_var("dimension-pivot-{$checksum_field_name}-use-values"));
            for ($i=0; $i<count($values); $i++) {
                $values[$i] = $this->translate_checksum($values[$i]);
            }
            $this->report_obj->set_pivot_values("dimension-pivot-{$checksum_field_name}", $values);
            //$_GET["dimension-pivot-{$checksum_field_name}"] = get_var('plot_field');
        }

        // translate the checksum field from possible hex value
        $checksum = $this->translate_checksum(get_var("fact-{$checksum_field_name}"));
        if (isset($checksum))
        {
//            print "setting checksum [$checksum]";
            $_GET["fact-{$checksum_field_name}"] = $checksum;
        }

        // process the form data, and get the query result
        $data = array();
        $data['datasource'] = get_var('datasource');
        try
        {
            $data['sql'] = $this->report_obj->query();
            $data['result'] = $this->report_obj->execute();
        }
        catch (Exception $e)
        {
            $this->alert($e->getMessage(),'alert-error');
            print prettyprint($data['sql']);
        }
        $data['columns'] = $this->report_obj->get_column_names();
        $data['permalink'] = site_url() . '?action=report&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();
        $data['jsonlink']  = site_url() . '?action=api&output=json&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();

        // output data in the requested format
        // never display header and footer
        $output_types = array(
            'json' => "report_json",
            'json2' => "report_json2",
            'print_r' => "report_printr",
            'table' => "report_result"
        );

        $output = get_var('output');
        if (!key_exists($output, $output_types))
        {
            $output = 'table';
        }

        $source_type = $this->data_model->get_source_type();
        if (key_exists('callbacks', $this->conf['reports'][$source_type]) && key_exists($output, $this->conf['reports'][$source_type]['callbacks']))
        {
            $data['callbacks'] =  $this->conf['reports'][$source_type]['callbacks'][$output];
        }

        $this->load->view($output_types[$output], $data);
    }


    /**
     * Search by using a graph.  A brief search form is shown to allow a graph to
     * be built. Html table results that correspond to the time range of the graph is
     * displayed below. Regions can be selected in the graph directly which will
     * update the table results with the new time range.
     */
    public function graph_search()
    {
        $this->header();
        $this->set_search_defaults('graph_defaults');

        $data = $this->setup_data_for_graph_search();

        // display the page
        $this->load->view("graph_search", $data);
        $this->footer();
    }

    private function setup_data_for_graph_search($data=null)
    {
        if (!isset($data))
        {
            $data = array();
        }

        $data['datasource'] = get_var('datasource');

        // to maintain backwards config file compatability, we try to guess
        // the time field being used for this report
        $time = $this->get_time_field_from_report_defaults('graph_defaults');
        if (!isset($time))
        {
            $time = $this->data_model->get_field_name('time');
        }
        $data['time_field_name'] = $time;
        $data['hostname_field_name'] = $this->data_model->get_field_name('hostname');
        $data['checksum_field_name'] = $this->data_model->get_field_name('checksum');

        // get table and hostname data for search form
        $data['tables'] = $this->report_obj->get_tables();
        $dimension_table = $this->report_obj->get_table_by_alias('dimension');
        $data['hosts'] = $this->report_obj->get_distinct_values($dimension_table, $data['hostname_field_name']);
        // check
        $data[$data['hostname_field_name']] = get_var($data['hostname_field_name']);
        $this->report_obj->set_pivot_values('dimension-pivot-'.$data['hostname_field_name'], $data['hosts']);

        // get custom fields for search form
        foreach ($data['tables'] as $t) {
            $data['table_fields'][$t] = $this->report_obj->get_table_fields($t);
        }
        $data['custom_fields'] = $this->report_obj->get_custom_fields();

        // process the form data so we can get the ajax url
        $this->report_obj->process_form_data();
        $_GET['table_fields'][] = get_var('plot_field');
        if (get_var('dimension-pivot-'.$data['hostname_field_name'])) {
            $_GET['dimension-pivot-'.$data['hostname_field_name']] = get_var('plot_field');
            $data['dimension_pivot_hostname_max'] = get_var('plot_field');
        }
        if (get_var('dimension-pivot-'.$data['checksum_field_name'])) {
            $_GET['dimension-pivot-'.$data['checksum_field_name']] = get_var('plot_field');
            $data['dimension_pivot_checksum'] = get_var('plot_field');
        }

        $data['ajax_request_url'] = site_url() . '?action=api&output=json2&noheader=1&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri(array( 'dimension-'.$time));
        $data['graph_permalink'] = site_url() . '?action=graph_search&datasource=' . $data['datasource'] . '&plot_field='.get_var('plot_field').'&'.$this->report_obj->get_search_uri(array( 'dimension-'.$time ));
        $data['show_query_base_url'] = site_url() . '?action=show_query&datasource=' . $data['datasource'];
        // now go get a url for the table results
        $this->init_report();
        $this->set_search_defaults('report_defaults', array('dimension-'.$time.'_start', 'dimension-'.$time.'_end', $data['checksum_field_name']));

        $_GET['fact-order'] = get_var('plot_field') . ' DESC';
        $data['ajax_table_request_url_base'] = site_url() . '?action=api&output=table&noheader=1&datasource=' . $data['datasource']. '&' . $this->report_obj->get_search_uri(array( 'dimension-'.$data['time_field_name'], 'dimension-ts_min'));
        $data['table_url_time_start_param'] = 'dimension-'.$data['time_field_name'].'_start';
        $data['table_url_time_end_param'] = 'dimension-'.$data['time_field_name'].'_end';
        $data['timezone_offset'] = $this->timezone_offset;

        return $data;
    }

    /**
     * show the index page where users can select the datasource.  If there's only
     * one, just redirect to the default report
     *
     */
    public function index()
    {

        $datasources = $this->data_model->get_data_source_names();

        if (count($datasources) == 0) {
            $this->alert("No Datasources defined.  Edit config.inc.php", 'alert-error');
            return;
        } elseif (count($datasources) == 1) {
            // for one datasource, just display the report
            $datasource = $datasources[0];
            $action = $this->data_model->get_default_report_action();
            header("Location: " . site_url() . "?action={$action}&datasource={$datasource}");
            return;
        }

        $this->header();

        // for multiple datasources, choose one
        $this->load->view('index', array('datasources' => $datasources, 'datasource' => get_var('datasource')));
        $this->footer();
    }

    public function noconfig()
    {
        $this->header();
        $this->load->view("noconfig");
        $this->footer();
    }

    /**
     * Search for a checksum value.  Redirect to show_query if it's found
     * or display an error message
     *
     */
    public function quicksearch() {
        $datasource = get_var('datasource');
        $checksum = $this->translate_checksum(get_var('checksum'));
        $exists = $this->data_model->checksum_exists($checksum);
        if (!$exists) {
            $this->alert("Unknown checksum: {$checksum}");
            return;
        }
        header("Location: " . site_url() . "?action=show_query&datasource={$datasource}&checksum={$checksum}");
        return;
    }

    /**
     * Display the search form, and the report results (by default as a html table)
     *
     */
    public function report() {
        $this->header();

        $this->set_search_defaults('report_defaults');
        $hide_form = get_var('hide_search_form');

        if (!isset($hide_form)) {
            $data = array();

            $data['datasource'] = get_var('datasource');
            $data['tables'] = $this->report_obj->get_tables();

            if ('performance_schema' != $this->data_model->get_source_type())
            {
                $fieldname = $this->data_model->get_field_name('hostname');
                $data['hosts'] = $this->report_obj->get_distinct_values($data['tables'][1], $fieldname);
                $data[$fieldname] = get_var('dimension-'.$fieldname);
                $this->report_obj->set_pivot_values('dimension-pivot-'.$fieldname, $data['hosts']);
            }

            // $data['fields = $this->report_obj->get_form_fields();
            // @todo remove
            $data['tables'] = $this->report_obj->get_tables();
            foreach ($data['tables'] as $t) {
                $data['table_fields'][$t] = $this->report_obj->get_table_fields($t);
            }
            $data['table_aliases'] = $this->data_model->get_table_aliases();
            $data['custom_fields'] = $this->report_obj->get_custom_fields();
            $data['table_fields_selected'] = get_var('table_fields');
            $data['exception_select_fields'] = $this->exception_select_fields;


            $data['review_types'] = $this->data_model->get_review_types();
            $data['reviewers'] = $this->data_model->get_reviewers();

            $this->display_report_form($data);
        }

        // just call the api to process and display report
        $this->api();
        $this->footer();
    }


    /**
     * Show query samples for a specific checksum
     *
     */
    public function samples() {
        $this->header();

        $datasource = get_var('datasource');
        $checksum = $this->translate_checksum(get_var('checksum'));
        $start = get_var('start') | 0;
        $rpp = get_var('rpp');

        $samples = $this->data_model->get_query_samples($checksum, $rpp + 1, $start);
        $num_rows = $samples->num_rows;
        // sort by?  filter by?
        // display progress bar and next / back
        // show explain

        $this->load->view('samples', array(
            'datasource' => $datasource,
            'checksum' => $checksum,
            'start' => $start,
            'rpp' => $rpp,
            'samples' => $samples,
            'num_rows' => $num_rows
        ));
        $this->footer();
    }

    private function translate_checksum($checksum)
    {
	if (!in_array($this->data_model->get_source_type(), array('slow_query_log','default')))
	{
		return $checksum;
	}

        if (preg_match('/^[0-9]+$/', $checksum))
        {
            return $checksum;
        }
        else if (preg_match('/^[0-9A-Fa-f]+$/', $checksum))
        {
            return $this->bchexdec($checksum);
        }
        else if (strlen($checksum) == 0)
        {
            return null;
        }
        else
        {
            throw new Exception("Invalid query checksum");
        }
    }

    /**
     * Display a specific query from its checksum value
     *
     */
    public function show_query() {
        $this->header();
        $output = 'table';
        $checksum = $this->translate_checksum(get_var('checksum'));
        $exists = $this->data_model->checksum_exists($checksum);
        if (!$exists) {
            $this->alert("Unknown checksum: {$checksum}");
            return;
        }

        $data['datasource'] = get_var('datasource');
        $sample_field_name = $this->data_model->get_field_name('sample');
        $checksum_field_name = $this->data_model->get_field_name('checksum');
        $fingerprint_field_name = $this->data_model->get_field_name('fingerprint');

        // query and most recent sample
        $row = $this->data_model->get_query_by_checksum($checksum);
        $source_type = $this->data_model->get_source_type();
        $data['source_type'] = $source_type;

        $callbacks = $this->data_model->get_callbacks($source_type, $output);
        if (isset($callbacks))
        {
            foreach ($callbacks as $fxname => $fx) {
                if (array_key_exists($fxname, $row)) {
                    $result = $fx($row[$fxname]);
                    $row[$fxname] = $result[0];
                }
            }
        }

        $data['checksum'] = $row[$checksum_field_name];
        $_GET['checksum'] = $row[$checksum_field_name];

        $data['row'] = $row;

        $data['sample'] = $this->data_model->get_query_samples($checksum, 1)->fetch_assoc();
        $sample = $data['sample'][$sample_field_name];

        // review info
        $data['review_types'] = $this->data_model->get_review_types();
        $data['reviewers'] = $this->data_model->get_reviewers();
        $data['current_auth_user'] = $this->get_auth_user();


        $data['show_samples'] = true;
        if ($source_type == 'performance_schema_history')
        {
            $data['show_samples'] = false;
            $data['row']['fingerprint'] = $sample;
        }
        else
        {
            try
            {
                // unfortunately there is a "catchable" fatal error which isn't
                // really catchable
                $this->data_model->init_query_explainer($data['sample']);
            }
            catch ( Exception $e )
            {
                $data['explain_plan_error'] = $e->getMessage();
            }
            $data['explain_plan'] = $this->data_model->get_explain_for_sample($data['sample']);
            $data['visual_explain'] = $this->data_model->get_visual_explain($data['explain_plan']);
            $data['query_advisor'] = $this->data_model->get_query_advisor($sample);
            $data['create_table'] = $this->data_model->get_create_table($sample);
            $data['table_status'] = $this->data_model->get_table_status($sample);
        }

        // graph
        $data['time_field_name'] = $time = $this->data_model->get_field_name('time');
        $data['hostname_field_name'] = $this->data_model->get_field_name('hostname');
        $data['checksum_field_name'] = $this->data_model->get_field_name('checksum');

        $data['timezone_offset'] = $this->timezone_offset;

        $data['tables'] = $this->report_obj->get_tables();
        if ($source_type == 'slow_query_log')
        {
            $dimension_table = $this->report_obj->get_table_by_alias('dimension');
            $data['hosts'] = $this->report_obj->get_distinct_values($dimension_table, $data['hostname_field_name']);
            // check
            $data[$data['hostname_field_name']] = get_var($data['hostname_field_name']);
            $this->report_obj->set_pivot_values('dimension-pivot-'.$data['hostname_field_name'], $data['hosts']);
        }

        // get custom fields for search form
        foreach ($data['tables'] as $t) {
            $data['table_fields'][$t] = $this->report_obj->get_table_fields($t);
        }
        $data['custom_fields'] = $this->report_obj->get_custom_fields();

        $this->set_search_defaults('graph_defaults');
        $this->report_obj->process_form_data();
        $_GET['table_fields'][] = get_var('plot_field');
        if (get_var('dimension-pivot-'.$data['hostname_field_name'])) {
            $_GET['dimension-pivot-'.$data['hostname_field_name']] = get_var('plot_field');
            $data['dimension_pivot_hostname_max'] = get_var('plot_field');
        }
        if (get_var('dimension-pivot-'.$data['checksum_field_name'])) {
            $_GET['dimension-pivot-'.$data['checksum_field_name']] = get_var('plot_field');
            $data['dimension_pivot_checksum'] = get_var('plot_field');
        }
        //$data = $this->setup_data_for_graph_search($data);

        $_GET['table_fields'][] = get_var('plot_field');
        $_GET['fact-checksum'] = get_var('checksum');
        $_GET['fact-DIGEST'] = get_var('checksum');
        $data['ajax_request_url'] = site_url() . '?action=api&output=json2&noheader=1&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();

        $data['sample_field_name'] = $this->data_model->get_field_name('sample');
        $data['hostname_field_name'] =$this->data_model->get_field_name('hostname');
        $data['time_field_name'] =$this->data_model->get_field_name('time');
        $data['fingerprint_field_name'] = $fingerprint_field_name;

        $view = "show_query";
        if ($source_type == 'performance_schema')
        {
            $view = "show_query_perf_schema";
        }
        $this->load->view($view, $data);

        // Show the history for this query
        // just set some form fields and call report
        // maybe convert to ajax call ...
        $this->init_report();
        $this->clear_all_time_values();
        $this->set_search_defaults('history_defaults', array());
        $_GET['fact-checksum'] = $checksum;
        //print_r($_GET);
        $this->api();
        $this->footer();
    }

    /**
     * Update the review and comments for a query by its checksum
     *
     */
    public function upd_query() {
        $checksum = $this->translate_checksum(get_var('checksum'));
        $valid_actions = array('Review', 'Review and Update Comments', 'Update Comments', 'Clear Review');
        $submit = get_var('submit');

        if (!in_array($submit, $valid_actions)) {
            alert('<strong>Error</strong> Invalid form action' . $submit, 'alert-error');
            return;
        }


        $fields_to_change = array();
        if ($submit == 'Review' || $submit == 'Review and Update Comments') {
            $fields_to_change['reviewed_by'] = get_var('reviewed_by');
            $fields_to_change['reviewed_on'] = date('Y-m-d H:i:s');
            $fields_to_change['reviewed_status'] = get_var('reviewed_status');
            session_start();
            $_SESSION['current_review_user'] = get_var('reviewed_by');
            session_write_close();
        }

        if ($submit == 'Review and Update Comments' || $submit == 'Update Comments') {
            $fields_to_change['comments'] = get_var('comments');
        }

        if ($submit == 'Clear Review') {
            $fields_to_change['reviewed_by'] = 'NULL';
            $fields_to_change['reviewed_on'] = 'NULL';
            $fields_to_change['reviewed_status'] = 'NULL';
        }

        $this->data_model->update_query($checksum, $fields_to_change);
        $datasource = get_var('datasource');
        header("Location: " . site_url() . "?action=show_query&datasource={$datasource}&checksum={$checksum}");
    }

    /**
     * display a message in a formatted div element
     *
     * @param string $string    The message to display
     * @param string $level     The div class to use (default alert-warning)
     */
    private function alert($string, $level = 'alert-warning') {
        $this->header();
        print "<div class=\"alert {$level}\">{$string}</div>";
    }

    private function display_report_form($data)
    {
        $data['hostname_field_name'] = $this->data_model->get_field_name('hostname');
        $data['checksum_field_name'] = $this->data_model->get_field_name('checksum');
        $data['time_field_name'] = $this->data_model->get_field_name('time');
        $data['sample_field_name'] = $this->data_model->get_field_name('sample');
        if (!is_object($this->data_model))
        {
            throw new Exception("No datasource defined");
        }

        $source_type = $this->data_model->get_source_type();
        switch ($source_type)
        {
            case 'performance_schema':
                $this->load->view("report-performance_schema", $data);
                break;
            default:
            $this->load->view("report", $data);
            break;
        }
    }

    /**
     * display the global web application footer
     */
    private function footer() {
        $this->load->view("footer");
    }

    /**
     * return the current username.  First from any .htaccess login if set, or
     * from the session if possible.
     */
    private function get_auth_user() {
        session_start();
        if (array_key_exists('PHP_AUTH_USER', $_SERVER))
        {
            return $_SERVER['PHP_AUTH_USER'];

        }
        else if (array_key_exists('current_review_user', $_SESSION))
        {
            return $_SESSION['current_review_user'];
        }

        session_write_close();
        return null;
    }

    /**
     * display the web application header
     * @return boolean  return true if the header was actually printed
     */
    private function header() {
        if ($this->header_printed) {
            return false;
        }
        $datasources = null;
        $datasource = null;
        $source_type = null;
        if (is_object($this->data_model))
        {
            $datasources = $this->data_model->get_data_source_names();
            $datasource = get_var('datasource');
            $source_type = $this->data_model->get_source_type();
        }

        if (!get_var('noheader')) {
            $this->load->view("header");
            $this->load->view("navbar", array( 'datasources' => $datasources, 'datasource' => $datasource, 'source_type' => $source_type ));
        }

        $this->header_printed = true;
    }

    /**
     * sets up or resets the report object
     *
     */
    private function init_report() {
        $datasource = get_var('datasource');

        if (isset($datasource)) {
            $conf = $this->data_model->get_data_source($datasource);

            // create report object  ... try to minimize overlap of responsibilities.
            $this->report_obj = new MySQLTableReport(
                            $conf,
                            $conf['tables'],
                            $this->data_model->get_report($conf['source_type'])
            );
            $this->report_obj->set_non_aggregate_columns($this->time_columns);
        }
    }

    /**
     * set default values for the search form
     *
     * @param type $type
     * @param type $override
     */
    private function set_search_defaults($type = 'report_defaults', $override = null) {
        $defaults = $this->data_model->get_report_defaults($type);
        foreach ($defaults as $key => $value) {
            // if there is no value set, or we have a list of overrides and this is not one of them
            if (get_var($key) == null or (is_array($override) and !in_array($key, $override))) {
                $_GET[$key] = $value;
            }
        }
    }

    private function clear_all_time_values()
    {
        foreach ($this->time_columns as $col)
        {
            $start = "dimension-{$col}_start";
            $end = "dimension-{$col}_end";
            foreach (array($start, $end) as $form_field_name)
            {
                if (array_key_exists($form_field_name, $_GET))
                {
                    unset($_GET[$form_field_name]);
                }
            }
        }
    }

    private function get_time_field_from_report_defaults($type)
    {
        $defaults = $this->data_model->get_report_defaults($type);
        foreach ($this->time_columns as $col)
        {
            $start = "dimension-{$col}_start";
            $end = "dimension-{$col}_end";

            if (array_key_exists($start, $defaults) or array_key_exists($end, $defaults))
            {
                return $col;
            }
        }
        return null;
    }

    private function bchexdec($hex) {
        static $hexdec = array(
            "0" => 0,
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5,
            "6" => 6,
            "7" => 7,
            "8" => 8,
            "9" => 9,
            "A" => 10,
            "B" => 11,
            "C" => 12,
            "D" => 13,
            "E" => 14,
            "F" => 15
        );

        $dec = 0;

        for ($i = strlen($hex) - 1, $e = 1; $i >= 0; $i--, $e = bcmul($e, 16, 0)) {
            $factor = $hexdec[$hex[$i]];
            $dec = bcadd($dec, bcmul($factor, $e, 0), 0);
        }

        return $dec;
    }
}

?>
