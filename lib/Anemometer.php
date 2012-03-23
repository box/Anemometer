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
        
        $this->conf = $conf;
        $this->data_model = new AnemometerModel($conf);        
        $datasource = get_var('datasource');
        if (isset($datasource)) {
            $this->data_model->set_data_source($datasource);
            $this->data_model->connect_to_datasource();
        }

        $this->init_report();
        session_start();
    }
    
    /**
     * main method for getting report results.  This method can be called as an
     * ajax callback and return the raw data in json format, or it can display
     * a table or graph directly.  All other methods that get report results use this
     * either directly or as an ajax call. 
     */
    public function api()
    {
        // special case for optional pivot on hostname
        // mainly used to graph each host as a series
        if (get_var('dimension-pivot-hostname_max') != null)
        {
            $dimension_table = $this->report_obj->get_table_by_alias('dimension');
            $hosts = $this->report_obj->get_distinct_values($dimension_table, 'hostname_max');
            $this->report_obj->set_pivot_values('dimension-pivot-hostname_max', $hosts);
        }

        // process the form data, and get the query result
        $data = array();
        $data['datasource'] = get_var('datasource');
        $data['sql'] = $this->report_obj->query();
        $data['result'] = $this->report_obj->execute();
        $data['columns'] = $this->report_obj->get_column_names();
        $data['permalink'] = site_url() . '?action=report&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();

        // output data in the requested format
        // never display header and footer
        $output_types = array(
            'json' => "report_json",
            'json2' => "report_json2",
            'print_r' => "report_printr",
            'table' => "report_result",
            'graph' => "flot_test"
        );

        $output = get_var('output');
        if (key_exists($output, $output_types))
        {
            $this->load->view($output_types[$output], $data);
        } else {
            $this->load->view($output_types['table'], $data);
        }
    }

    
    /**
     *  Search by using a graph.  A brief search form is shown to allow a graph to
     * be built.  Html table results that corespond to the time range of the graph is
     * displayed below.  Regions can be selected in the graph directly which will
     * update the table results with the new time range. 
     */
    public function graph_search()
    {
        $this->header();
        $this->set_search_defaults('graph_defaults');
        $data['datasource'] = get_var('datasource');

        // get table and hostname data for search form
        $data['tables'] = $this->report_obj->get_tables();
        $dimension_table = $this->report_obj->get_table_by_alias('dimension');
        $data['hosts'] = $this->report_obj->get_distinct_values($dimension_table, 'hostname_max');
        $data['hostname_max'] = get_var('dimension-hostname_max');
        $this->report_obj->set_pivot_values('dimension-pivot-hostname_max', $data['hosts']);

        // get custom fields for search form
        foreach ($data['tables'] as $t) {
            $data['table_fields'][$t] = $this->report_obj->get_table_fields($t);
        }
        $data['custom_fields'] = $this->report_obj->get_custom_fields();

        // process the form data so we can get the ajax url
        $this->report_obj->process_form_data();
        $_GET['table_fields'][] = get_var('plot_field');
        if (get_var('dimension-pivot-hostname_max')) {
            $_GET['dimension-pivot-hostname_max'] = get_var('plot_field');
            $data['dimension_pivot_hostname_max'] = get_var('plot_field');
        }
        
        $data['ajax_request_url'] = site_url() . '?action=api&output=json2&noheader=1&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri(array( 'dimension-ts_min' ));
        $data['graph_permalink'] = site_url() . '?action=graph_search&datasource=' . $data['datasource'] . '&plot_field='.get_var('plot_field').'&'.$this->report_obj->get_search_uri(array( 'dimension-ts_min' ));
        // now go get a url for the table results
        $this->init_report();
        $this->set_search_defaults('report_defaults', array('dimension-ts_min_start', 'dimension-ts_min_end','checksum'));
//        $data['ajax_request_url_table'] = site_url() . '?action=api&output=table&noheader=1&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();
        
        $data['ajax_table_request_url_base'] = site_url() . '?action=api&output=table&noheader=1&datasource=' . $data['datasource']. '&' . $this->report_obj->get_search_uri(array( 'dimension-ts_min' ));
        $data['table_url_time_start_param'] = 'dimension-ts_min_start';
        $data['table_url_time_end_param'] = 'dimension-ts_min_end';
        
        
        // display the page
        $this->load->view("graph_search", $data);
        $this->footer();
    }
    
    /**
     * show the index page where users can select the datasource.  If there's only
     * one, just redirect to the default report
     * 
     */
    public function index()
    {
        $this->header();

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

        // for multiple datasources, choose one
        $this->load->view('index', array('datasources' => $datasources));
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
        $checksum = get_var('checksum');
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
            $data['hosts'] = $this->report_obj->get_distinct_values($data['tables'][1], 'hostname_max');
            $data['hostname_max'] = get_var('dimension-hostname_max');
            $this->report_obj->set_pivot_values('dimension-pivot-hostname_max', $data['hosts']);

            //		$data['fields = $this->report_obj->get_form_fields();
            // @todo remove
            $data['tables'] = $this->report_obj->get_tables();
            foreach ($data['tables'] as $t) {
                $data['table_fields'][$t] = $this->report_obj->get_table_fields($t);
            }
            $data['custom_fields'] = $this->report_obj->get_custom_fields();
            $data['table_fields_selected'] = get_var('table_fields');


            $data['review_types'] = $this->data_model->get_review_types();
            $data['reviewers'] = $this->data_model->get_reviewers();


            $this->load->view("report", $data);
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
        $checksum = get_var('checksum');
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
    
    
    /**
     * Display a specific query from its checksum value
     * 
     */
    public function show_query() {
        $this->header();

        $checksum = get_var('checksum');
        $exists = $this->data_model->checksum_exists($checksum);
        if (!$exists) {
            $this->alert("Unknown checksum: {$checksum}");
            return;
        }

        $data = array();
        $data['checksum'] = $checksum;
        $data['datasource'] = get_var('datasource');

        // query and most recent sample
        $data['row'] = $this->data_model->get_query_by_checksum($checksum);
        $data['sample'] = $this->data_model->get_query_samples($checksum, 1)->fetch_assoc();

        // review info
        $data['review_types'] = $this->data_model->get_review_types();
        $data['reviewers'] = $this->data_model->get_reviewers();
        $data['current_auth_user'] = $this->get_auth_user();

        // get explain plan and extra info
        // TODO convert to ajax calls, just get the url
        $this->data_model->init_query_explainer($data['sample']);
        $data['explain_plan'] = $this->data_model->get_explain_for_sample($data['sample']);
        $data['visual_explain'] = $this->data_model->get_visual_explain($data['explain_plan']);
        $sample = $data['sample']['sample'];
        $data['query_advisor'] = $this->data_model->get_query_advisor($sample);
        $data['create_table'] = $this->data_model->get_create_table($sample);
        $data['table_status'] = $this->data_model->get_table_status($sample);

        // graph
        $this->set_search_defaults('graph_defaults');
        $this->report_obj->process_form_data();
        $_GET['table_fields'][] = get_var('plot_field');
        $_GET['fact-checksum'] = $checksum;
        $data['ajax_request_url'] = site_url() . '?action=api&output=json2&noheader=1&datasource=' . $data['datasource'] . '&' . $this->report_obj->get_search_uri();

        $this->load->view("show_query", $data);

        // Show the history for this query
        // just set some form fields and call report
        // maybe convert to ajax call ... 
        $this->init_report();
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
        $checksum = get_var('checksum');
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
            $_SESSION['current_review_user'] = get_var('reviewed_by');
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
        return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : $_SESSION['current_review_user'];
    }
    
    /**
     * display the web application header
     * @return boolean  return true if the header was actually printed
     */
    private function header() {
        if ($this->header_printed) {
            return false;
        }
        if (is_object($this->data_model))
        {
            $datasources = $this->data_model->get_data_source_names();
            $datasource = get_var('datasource');
        }

        if (!get_var('noheader')) {
            $this->load->view("header");
            $this->load->view("navbar", array( 'datasources' => $datasources, 'datasource' => $datasource ));
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
                            $this->data_model->get_report('slow_query_log')
            );
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
}

?>