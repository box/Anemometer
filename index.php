<?php
include "SQL/Parser.php";
/*
	* todo: implement exra fields ... somehow<br> or remove extra fields for version 1
	* todo: add reviewed_status to search fields ... tab for extended search?<br>
	* todo: possible report for reviewed (aggregate by reviewd type, reviwed/unreviewed / new  or old, etc)<br>
	* todo: cleanup MySQLReport object<br>
	* todo: document / organise it all
	* maybe make explain / create / etc ajax requests so we don't do requests when nobody looks at it
*/
error_reporting(E_ALL);
if (!get_var('noheader'))
{
	include "views/header.php";
}

$action = 'index';
if (isset($_GET['action']))
{
	$action = $_GET['action'];
}

$conf = array();
require "conf/config.inc.php";

$controller = new WeatherStation($conf);
if (is_callable(array($controller, $action )))
{
	$controller->$action();
}
else
{
	print "Invalid action ($action)";
}
if (!get_var('noheader'))
{
	include "views/footer.php";
}

// @todo validation?
function get_var($name)
{	
	$sources = array($_POST, $_GET, $_SESSION);
	foreach ($sources as $s)
	{
		if (isset($s[$name]))
		{
			return $s[$name];
		}
	}
	return null;
}

function site_url()
{
	return 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}

function alert($string, $level='alert-warning')
{
	print "<div class=\"alert {$level}\">{$string}</div>";
}

function prettyprint($string)
{
	print '<pre class="prettyprint">';
	print $string;
	print "</pre>";
}


function check_mysql_error($result, $mysqli)
{
	if (!$result)
	{
		// @TODO put markup in a view
		die(alert("<strong>".$mysqli->errno."</strong><br>".$mysqli->error, 'alert-error'));
	}
}

class WeatherStation
{
	private $conf;
	private $data_model;
	private $report_obj;
	
	function __construct($conf)
	{
		$this->conf = $conf;
		$this->data_model = new WeatherStationModel($conf);
		session_start();
		
		$datasource = $this->get_var('datasource');
		if (isset($datasource)) {
			$conf  = $this->data_model->get_data_source($datasource);
			// @TODO make db connection handling cleaner between report object and
			// model ... pass one connection object between them
			$this->data_model->set_data_source($datasource);
			$this->data_model->connect_to_datasource();
			
			// create report object  ... try to minimize overlap of responsibilities.
			$this->report_obj = new MySQLTableReport(
				$conf,
				$conf['tables'],
				$this->data_model->get_form_fields('slow_query_log'),
				true,
				$this->data_model->get_report('slow_query_log')
			);
		}
		
		$datasources = $this->data_model->get_data_source_names();
		$path = $this->get_path();
		if (!get_var('noheader'))
		{
			require "views/navbar.php";
		}
	}
	
	public function quicksearch()
	{
		$datasource = $this->get_var('datasource');
		$checksum = get_var('checksum');
		$exists = $this->data_model->checksum_exists($checksum);
		if (!$exists)
		{
			alert("Unknown checksum: {$checksum}");
			return;
		}
		//header("Location: ".site_url()."?action=show_query&datasource={$datasource}&checksum={$checksum}");
		print '<script type="text/javascript">'
		.'window.location = "'.site_url()."?action=show_query&datasource={$datasource}&checksum={$checksum}".'"'
		.'</script>';
	}
	
	public function show_query()
	{
		$checksum = get_var('checksum');
		$exists = $this->data_model->checksum_exists($checksum);
		if (!$exists)
		{
			alert("Unknown checksum: {$checksum}");
			return;
		}
		
		$datasource = $this->get_var('datasource');
		$review_types = $this->data_model->get_review_types();
		$reviewers = $this->data_model->get_reviewers();
		
		$row = $this->data_model->get_query_by_checksum($checksum);
		$current_auth_user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : get_var('current_review_user');
		$sample = $this->data_model->get_query_samples($checksum, 1)->fetch_assoc();
		
		// get explain plan and extra info
		$this->data_model->init_query_explainer($sample);
		$explain_plan = $this->data_model->get_explain_for_sample($sample);		
		$visual_explain = $this->data_model->get_visual_explain($explain_plan);
		$query_advisor = $this->data_model->get_query_advisor($sample['sample']);
		$create_table = $this->data_model->get_create_table($sample['sample']);
		$table_status = $this->data_model->get_table_status($sample['sample']);
		
		require "views/show_query.php";

		// Show the history for this query
		// just set some form fields and call report
		$defaults = $this->data_model->get_report_defaults();
		$_GET['table_fields'] = array_merge(array('date'),  array_filter($defaults['table_fields'], function ($x) { return $x != 'snippet' and $x != 'checksum'; }));
		
		$_GET['dimension-ts_min_start'] = date("Y-m-d H:i:s", strtotime( '-90 day'));
		$_GET['dimension-ts_min_end'] = date("Y-m-d H:i:s");
		$_GET['fact-group'] = 'DATE(ts_min)';
		//$_GET['fact-where'] = "checksum = '$checksum'";
		$_GET['fact-checksum'] = $checksum;
		$_GET['fact-order'] = 'DATE(ts_min) DESC';
		$_GET['fact-limit'] = 90;
		$_GET['action'] = "search";

		$_GET['hide_search_form'] = 'true';
		
		$this->report();
	}
	
	public function upd_query()
	{
		$checksum = get_var('checksum');
		$valid_actions = array( 'Review', 'Review and Update Comments', 'Update Comments', 'Clear Review' );
		$submit = get_var('submit');
		
		if (!in_array($submit, $valid_actions))
		{
			alert('<strong>Error</strong> Invalid form action' . $submit, 'alert-error');
			return;
		}
		
		
		$fields_to_change = array();
		if ($submit == 'Review' || $submit == 'Review and Update Comments')
		{
			$fields_to_change['reviewed_by'] = get_var('reviewed_by');
			$fields_to_change['reviewed_on'] = date('Y-m-d H:i:s');
			$fields_to_change['reviewed_status'] = get_var('reviewed_status');
			$_SESSION['current_review_user'] = get_var('reviewed_by');
		}
		
		if ($submit == 'Review and Update Comments' || $submit == 'Update Comments')
		{
			$fields_to_change['comments'] = get_var('comments');
		}
		
		if ($submit == 'Clear Review')
		{
			$fields_to_change['reviewed_by'] = 'NULL';
			$fields_to_change['reviewed_on'] = 'NULL';
			$fields_to_change['reviewed_status'] = 'NULL';
		}
		
		$this->data_model->update_query($checksum, $fields_to_change);
		$this->show_query();
	}
	
	public function samples()
	{
		$datasource = $this->get_var('datasource');
		$checksum = get_var('checksum');
		$records_per_page = get_var('rpp');
		$start = get_var('start') | 0;
		$rpp = get_var('rpp');
		
		$samples = $this->data_model->get_query_samples($checksum, $records_per_page+1, $start);
		$num_rows = $samples->num_rows;
		// sort by?  filter by?
		// display progress bar and next / back
		// show explain
		
		require "views/samples.php";
		
	}
	
	private function get_path()
	{
		$path = array();
		foreach (array('datasource') as $item)
		{
			$path[] = $this->get_var($item);
		}
		return $path;
	}
	private function get_var($name)
	{
		return get_var($name);
	}
	
	public function index()
	{
		$datasources = $this->data_model->get_data_source_names();
		
		if (count($datasources) == 0)
		{
			alert("No Datasources defined.  Edit config.inc.php", 'alert-error');
			return;
		}
		elseif (count($datasources) == 1)
		{
			// for one datasource, just display the report
			$_GET['ds'] = $datasources[0];
			$this->report();
			return;
		}
		
		// for multiple datasources, choose one
		require "views/index.php";
	}
	
	private function set_defaults_for_report()
	{
		$defaults = $this->data_model->get_report_defaults();		
		foreach ($defaults as $key => $value)
		{
			if (get_var($key) == null)
			{
				$_GET[$key] = $value;
			}
		}
	}
	
	public function report()
	{
		$this->set_defaults_for_report();
		
		$hide_form = get_var('hide_search_form');
		$datasource = $this->get_var('datasource');
		
		// bad ... 
		$tables = $this->report_obj->get_tables();
		$hosts = $this->report_obj->get_distinct_values($tables[1], 'hostname_max');
		$hostname_max = get_var('dimension-hostname_max');
		
//		$fields = $this->report_obj->get_form_fields();
		$tables = $this->report_obj->get_tables();
		foreach ($tables as $t)
		{
			$table_fields[$t] = $this->report_obj->get_table_fields($t);
		}
		$custom_fields = $this->report_obj->get_custom_fields();
		$table_fields_selected = get_var('table_fields');
		
		$fields['table_fields'] = array();
		$this->report_obj->process_form_data();
		$sql = $this->report_obj->query();
		
		$review_types = $this->data_model->get_review_types();
		$reviewers = $this->data_model->get_reviewers();
		
		if (!isset($hide_form)) {
			require "views/report.php";
		}
		
		$permalink =  site_url() . '?action=report&datasource='.$datasource. '&'.$this->report_obj->get_search_uri();
		$result = $this->report_obj->execute($sql);
		$columns = $this->report_obj->get_column_names();
		//prettyprint(print_r($_SERVER, true));
		
		if (get_var('output') == 'graph')
		{
			require "views/flot_test.php";
		}
		else
		{
			require "views/report_result.php";
		}
	}
	
	public function api()
	{
		$this->report_obj->process_form_data();
		$sql = $this->report_obj->query();
		
		$data = array();
		$result = $this->report_obj->execute($sql);
		$columns = $this->report_obj->get_column_names();
		
		$output_types = array(
							  'json' => "views/report_json.php",
							  'json2' => "views/report_json2.php",
							  'print_r' => "views/report_printr.php",
							  'table' => "views/report_result.php",
							  'graph' => "views/flot_test.php"
		);
		
		$output = get_var('output');
		if (key_exists($output, $output_types))
		{
			require $output_types[$output];
		}
		else
		{
			require $output_types['table'];
		}
		
		//$permalink =  site_url() . '?action=report&datasource='.$datasource. '&'.$this->report_obj->get_search_uri();
	}
	
	
	private function set_search_defaults($type='report_defaults')
	{
		$defaults = $this->data_model->get_report_defaults($type);		
		foreach ($defaults as $key => $value)
		{
			if (get_var($key) == null)
			{
				$_GET[$key] = $value;
			}
		}
	}
	
	public function graph_search()
	{
		$this->set_search_defaults('graph_defaults');
		$datasource = $this->get_var('datasource');
		
		$tables = $this->report_obj->get_tables();
		$hosts = $this->report_obj->get_distinct_values($tables[1], 'hostname_max');
		$hostname_max = get_var('dimension-hostname_max');
		
		$tables = $this->report_obj->get_tables();
		foreach ($tables as $t)
		{
			$table_fields[$t] = $this->report_obj->get_table_fields($t);
		}
		$custom_fields = $this->report_obj->get_custom_fields();
		
		$this->report_obj->process_form_data();
		$_GET['table_fields'][] = get_var('plot_field');
		$ajax_request_url =  site_url() . '?action=api&output=json2&noheader=1&datasource='.$datasource. '&'.$this->report_obj->get_search_uri();
		
		require "views/graph_search.php";	
	}
	
	function __destruct()
	{
	}
}


class WeatherStationModel
{
	private $conf;
	private $datasource_name;
	private $mysqli;
	private $fact_table;
	private $dimension_table;
	
	
	function __construct($conf)
	{
		$this->conf = $conf;
	}
	
	public function get_report_defaults($type='report_defaults')
	{
		return $this->conf[$type];
	}
	
	public function get_review_types()
	{
		return $this->conf['review_types'];
	}
	
	public function get_data_source_names()
	{
		if (is_array($this->conf['datasources']))
		{
			return array_keys($this->conf['datasources']);
		}
		return array();
	}
	
	public function get_data_source($name)
	{
		if (is_array($this->conf['datasources'][$name]))
		{
			return $this->conf['datasources'][$name];
		}
		return null;
	}
	
	public function set_data_source($name)
	{
		$this->datasource_name = $name;
		foreach ($this->conf['datasources'][$name]['tables'] as $key => $alias)
		{
			if ($alias == 'fact')
			{
				$this->fact_table = $key;
			}
			elseif ($alias == 'dimension')
			{
				$this->dimension_table = $key;
			}
		}
	}
	
	public function set_tables($fact, $dimension)
	{
		$this->fact_table = $fact;
		$this->dimension_table = $dimension;
	}
	
	public function get_tables($name)
	{
		return $this->conf['reports'][$name]['tables'];
	}
	
	public function get_form_fields($name)
	{
		return $this->conf['reports'][$name]['fields'];
	}
	
	public function get_report($name)
	{
		return $this->conf['reports'][$name];
	}
	
	public function get_reviewers()
	{
		return $this->conf['reviewers'];
	}
	
	public function checksum_exists($checksum)
	{
		$result = $this->mysqli->query("SELECT checksum FROM {$this->fact_table} WHERE checksum='".$this->mysqli->real_escape_string($checksum)."'");
		check_mysql_error($result,$this->mysqli);
		if ($result->num_rows)
		{
			return true;
		}
		return false;
	}
	
	public function update_query($checksum, $fields)
	{
		$mysqli = $this->mysqli;
		$sql = "UPDATE {$this->fact_table} SET ";
		$sql .= join(
			 ',',
			array_map(
				function ($x, $y)  use ($mysqli) {
					if ($y == 'NULL')
					{
						return "{$x} = NULL";
					}
					return "{$x} = \"".$mysqli->real_escape_string($y).'"';
				},
				array_keys($fields), array_values($fields)
			)
		);
		$sql .= " WHERE checksum='".$this->mysqli->real_escape_string($checksum)."'";
		$res = $this->mysqli->query($sql);
		check_mysql_error($res, $this->mysqli);
	}
	
	public function get_query_by_checksum($checksum)
	{
		$result = $this->mysqli->query("SELECT * FROM {$this->fact_table} WHERE checksum={$checksum}");
		check_mysql_error($result, $this->mysqli);
		if ($row = $result->fetch_assoc())
		{
			return $row;
		}
		return null;
	}
	
	public function get_query_samples($checksum, $limit=1, $offset=0)
	{
		$sql = "SELECT ts_min, ts_max, hostname_max, sample FROM {$this->dimension_table} WHERE checksum=$checksum ORDER BY ts_max DESC LIMIT {$limit} OFFSET {$offset}";
		return $this->mysqli->query($sql);
	}
	
	public function connect_to_datasource()
	{
		$ds = $this->conf['datasources'][$this->datasource_name];
		//print "{$this->datasource_name}<br>";
		//print_r($ds);
		$this->mysqli = new mysqli($ds['host'],$ds['user'],$ds['password'],$ds['db'], $ds['port']);
		if ($this->mysqli->connect_errno)
		{
			die(alert($this->mysqli->connect_error));
		}
	}
	
	public function init_query_explainer(array $sample)
	{
		$this->explainer = new QueryExplain($this->conf['plugins']['explain'],$sample);
	}
	
	public function get_explain_for_sample(array $sample)
	{
		if (!isset($this->conf['plugins']['explain']) or ! is_callable($this->conf['plugins']['explain']))
		{
			return null;
		}
		
		
		return $this->explainer->explain($sample);
	}
	
	public function exec_external_script($script, $input)
	{
		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
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
	
	public function get_visual_explain($explain_plan)
	{
		if (!isset($this->conf['plugins']['visual_explain']))
		{
			return null;
		}
		
		if (!file_exists($this->conf['plugins']['visual_explain']))
		{
			return "can't find visual explain at ". $this->conf['plugins']['visual_explain'];
		}
		return $this->exec_external_script($this->conf['plugins']['visual_explain'], $explain_plan);
	}
	
	public function get_query_advisor($explain_plan)
	{
		if (!isset($this->conf['plugins']['query_advisor']))
		{
			return null;
		}
		
		if (!file_exists($this->conf['plugins']['query_advisor']))
		{
			return "can't find query advisor at ". $this->conf['plugins']['query_advisor'];
		}
		
		return $this->exec_external_script($this->conf['plugins']['query_advisor'], $explain_plan);
	}
	
	public function get_create_table($query)
	{
		if (!isset($this->conf['plugins']['show_create']) or !$this->conf['plugins']['show_create'])
		{
			return null;
		}
		
		if(!isset($this->explainer))
		{
			return null;
		}
		
		return $this->explainer->get_create($query);
	}
	
	public function get_table_status($query)
	{
		if (!isset($this->conf['plugins']['show_status']) or !$this->conf['plugins']['show_status'])
		{
			return null;
		}
		
		if(!isset($this->explainer))
		{
			return null;
		}
		
		return $this->explainer->get_table_status($query);
		
		
	}
}

abstract class Report
{
	private $datasource;
	
	function __construct($datasource) {
		$this->datasource = $datasource;
	}
	
	abstract function get_form_fields();
	abstract function get_form_field_values();	
}

class MySQLTableReport  extends Report
{
	function __construct($datasource, $tables, $form_fields, $auto_discover, $report)
	{
		$this->datasource = $datasource;
		$this->tables = $tables;
		$this->form_fields = $form_fields;
		$this->auto_discover_fields = true;
		$this->report = $report;
		
		if ($datasource != null)
		{
			$this->connect_to_datasource();
		}
	}
	
	private function connect_to_datasource()
	{
		$ds = $this->datasource;
		$this->mysqli = new mysqli($ds['host'],$ds['user'],$ds['password'],$ds['db'], $ds['port']);
		if ($this->mysqli->connect_errno)
		{
			die($this->mysqli->connect_error);
		}
	}
	
	public function get_tables()
	{
		return array_keys($this->tables);
	}
	
	public function get_form_fields()
	{
		$fields = array();
		$tables = $this->tables;
		foreach ($this->form_fields as $alias => $values)
		{
			$fields = array_merge($fields,
				array_map(
						  function ($x) use ($alias) { return $alias."-{$x}"; },
						  array_keys($values)
				)
			);
		}
		return $fields;
	}
	
	public function get_custom_fields()
	{
		return array_keys($this->report['custom_fields']);
	}
	
	public function get_table_fields($table_name=null)
	{
		// now find all colums from the tables
		if (isset($table_name))
		{
			$tables = array( &$table_name );
		}
		else
		{
			$tables = array();
			$count =  count($this->tables);
			$t = array_keys($this->tables);
			for ($i=0; $i< $count; $i++)
			{
				$tables[] = & $t[$i];
			}	
		}
		
		$values = array_merge( array(str_repeat('s', count($tables)+1), &$this->datasource['db']), $tables);
		//print_r($values);
		$sql = "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME IN (".join(',',array_map(function ($x) { return '?'; }, $tables)).")";
		
		$stmt = $this->mysqli->prepare($sql);
		call_user_func_array(array($stmt, 'bind_param'), $values);
		$stmt->execute();
		$stmt->bind_result($table_name, $col_name);
		
		$columns = array();
		while($stmt->fetch())
		{
			//print "found column $table_name $col_name<br/>";
			$columns[] = "{$col_name}";
		}
		
		return $columns;
	}
	
	public function get_distinct_values($table, $colname)
	{
		//print "getting distinct $colname from $table<br>";
		$result = $this->mysqli->query("SELECT DISTINCT `{$colname}` FROM `{$table}`");
		$values = array();
		while ($row = $result->fetch_array())
		{
			$values[] = $row[0];
		}
		return $values;
	}
	
	public function get_form_field_values()
	{
		$fields = $this->get_form_fields();
		$return = array();
		foreach ($fields as $f)
		{
			$return[$f] = get_var($f);
		}
		return $return;
	}
	
	public function select(array $fields)
	{
		$this->select = $fields;
		return $this;
	}
	
	public function from($table)
	{
		$this->from = $table;
		return $this;
	}
	
	public function join(array $table)
	{
		$this->join[] = $table;
		return $this;
	}
	
	public function where($key, $var_name, $value, $op=null)
	{
		if ($op == null)
		{
			$op = '=';
		}
		//print "adding $key with $op $value <br>\n";
		$this->where[] = array($key, $value, $op);
		return $this;
	}
	
	public function group($col_name, $var_name, $expression)
	{
		//print "called group with $expression<br>";
		$this->group = $expression;
		return $this;
	}
	
	public function order($key, $field, $expression)
	{
		$this->order = $expression;
		return $this;
	}
	
	public function limit($key, $field, $expression)
	{
		$this->limit = $expression;
		return $this;
	}
	
	public function having($key, $field, $expression)
	{
		$this->having = $value;
		return $this;
	}
	
	public function raw_where($key, $field, $expression)
	{
		$this->extra_where = $expression;
	}
	
	public function date_range($col_name, $var_name, $expression)
	{
		return array(
			array( $col_name, "{$var_name}_start" , get_var("{$var_name}_start"), '>='),
			array( $col_name, "{$var_name}_end", get_var("{$var_name}_end"), '<=')
		);
	}
	
	public function clear($col_name, $var_name, $expression, $op=null)
	{
		if ($expression == '')
		{
			$expression = null;
		}
		return array( array($col_name, $var_name, $expression, $op) );
	}
	
	public function ge($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, $expression, '>=') );
	}
	public function le($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, $expression, '<=') );
	}
	public function gt($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, $expression, '>') );
	}
	public function lt($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, $expression, '<') );
	}
	public function ne($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, $expression, '!=') );
	}
	
	public function like($col_name, $var_name, $expression)
	{
		return array( array($col_name, $var_name, isset($expression) ? "%{$expression}%" : null, 'LIKE') );
	}
	
	public function get_column_aggregate_function($name)
	{
		if (!preg_match("/_([^_]+)$/", $name, $regs))
		{
			return null;
		}
		
		switch ($regs[1])
		{
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
	public function process_form_data()
	{
		$values = $this->get_form_field_values();
		$fields = $this->get_form_fields();
		
		// SELECT
		$select = array();
		foreach (get_var('table_fields') as $f)
		{
			if (isset($this->report['custom_fields'][$f]))
			{
				$select[$f] = array($this->report['custom_fields'][$f], $f, null );
			}
			else
			{
				$select[$f] = array( $f, null , $this->get_column_aggregate_function($f) );
			}
			
		}
		$this->select($select);
		
		// FROM
		$count = 0;
		foreach ($this->tables as $table => $alias)
		{
			if ($count == 0 )
			{
				$this->from(array($table, $alias));
			}
			else
			{
				$this->join(array($table, $alias, $this->report['join'][$alias]));
			}
			$count++;
		}
		
		// WHERE
		foreach ($this->tables as $table => $alias)
		{
			foreach ($this->form_fields[$alias] as $field => $config)
			{
				$var_name = "{$alias}-{$field}";
				$col_name = "{$alias}.{$field}";
				$functions = preg_split("/\|/", $config );
				$args = array( array( $col_name, $var_name, $values[$var_name]) );
				//print "checking fields {$var_name}=". $args[0][1]. "<br>";
				//print_r($functions);
				foreach ($functions as $func)
				{
					$next_args = array();
					foreach ($args as $arg)
					{
						//print "calling $func<br>\n";
						//prettyprint(print_r($arg,true));
						$next_args = array_merge($next_args, call_user_func_array(array($this, $func), $arg));
					}
					// set up result args for next function
					// if the function returns an object === $this, then we can't continue
					$args = $next_args;
					if (is_object($args))
					{
						break;
					}
				}
			}
		}
	}
	
	private function filter_where()
	{
		if (is_array($this->where))
		{
			$this->where = array_filter( 
				$this->where,
				function ($y) { if (isset($y[1])) { return true; } }
			);
		}
	}
	public function query()
	{
		// SELECT
		$sql = "SELECT ";
		if (!isset($this->select) OR count($this->select) == 0 )
		{
			$sql .= "*";
		}
		else
		{
			// select values are array( col_name, alias_name, aggregate function)
			$sql .= join(
				",\n  ",
				array_map(
					function ($k) {
						
						if (isset($k[2]))
						{
							// aggregate function on the column
							return sprintf("%s(`%s`) AS `%s`", $k[2], $k[0], isset($k[1]) ? $k[1] : $k[0]);
						}
						// non aggregate column
						return "".$k[0]."". ( isset($k[1]) ? ' AS `'.$k[1].'`' : '');
					},
					array_values($this->select)
				)
			) . "\n";
		}
		
		// FROM
		$sql .= " FROM `".$this->from[0]."` AS `".$this->from[1]."` \n";
		
		// JOIN
		for ($i=0; $i<count($this->join); $i++ )
		{
			$key = $this->join[$i][0];
			$alias = $this->join[$i][1];
			$on = $this->join[$i][2];
			// @TODO  .... *must remove this hack*
			$sql .= " JOIN `{$key}` AS `{$alias}` {$on} \n";
		}
		
		// WHERE
		$this->filter_where();
		//prettyprint(print_r($this->where,true));
		if (count($this->where) >= 0)
		{
			$sql .= " WHERE ". join("\n  AND ",
				array_map(
					function ($x) {  return $x[0].' '.$x[2].' "'.$x[1].'"'; },
					$this->where
				)
			). "\n";
			
		}
		
		
		// EXTRA TEXT INPUT FOR WHERE
		if (isset($this->extra_where) and $this->extra_where != '')
		{
			if (count($this->where))
			{
				$sql .= " AND (" . $this->extra_where .") ";
			}
			else
			{
				$sql .= " WHERE (" . $this->extra_where .") ";
			}
			
		}		
		
		// GROUP / ORDER / HAVING / LIMIT
		$aditional_clauses = array(
			'GROUP BY' => $this->group,
			'ORDER BY' => $this->order,
			'HAVING' => $this->having,
			'LIMIT' => $this->limit
		);
		foreach ($aditional_clauses as $clause => $value)
		{
			if (isset($value) and $value != '')
			{
				$sql .= " {$clause} {$value} \n";
			}
			
		}
		return $sql;
	}
	
	public function get_column_names()
	{
		return array_map(function ($k) { return $k[1] != '' ? $k[1] : $k[0] ; }, array_values($this->select));
	}
	
	public function execute($sql)
	{
		$result =  $this->mysqli->query($sql);
		check_mysql_error($result, $this->mysqli);
		$result_data = array();
		while ($row = $result->fetch_assoc())
		{
			$result_data[] = $row;
		}
		return $result_data;
	}
	
	public function get_search_uri()
	{
		$run_funcs = array('date_range');
		$params = array();
		foreach ($this->tables as $table => $alias)
		{
			foreach ($this->report['fields'][$alias] as $field => $def)
			{
				$handled = false;
				$var_name = "{$alias}-{$field}";
				$col_name = "{$alias}.{$field}";
				$value = get_var($var_name);
			
				$funcs = preg_split("/\|/", $def);
				foreach ($funcs as $f)
				{
					if (in_array($f, $run_funcs))
					{
						//print "handled $var_name: $f<br>";
						$args = $this->$f($col_name, $var_name, $value);
						foreach ($args as $arg)
						{
							$value = $arg[2];
							if (isset($value) and $value != '')
							{
								$params[] = $arg[1] .'='. urlencode($value);
							}
							$handled = true;
						}
					}
				}
				
				if ($handled)
				{
					continue;
				}
				
				if (isset($value) and $value != '')
				{
					$params[] = $var_name .'='. urlencode($value);
				}
				
			}
		}
		
		$table_fields = get_var('table_fields');
		foreach ($table_fields as $t)
		{
			$params[] = "table_fields%5B%5D={$t}";
		}
		
		return join("&", $params);
	}

	// hmm, this isn't quite fully formed	
	public function prepare_data_for_format($data, $type='html_table')
	{
		$columns = $this->output[$type];
		for ($i=0; $i < count($data); $i++)
		{
			foreach ($columns as $c => $func)
			{
				$data[$i][$c] = $this->$func($c, $i, $data[$i][$c]);
			}
		}
	}
	

	
}

class QueryExplain
{
	private $get_connection_func;
	private $mysqli;
	private $conf;
	
	function __construct($get_connection_func, $sample)
	{
		$this->get_connection_func = $get_connection_func;
		if (!is_callable($this->get_connection_func))
		{
			return "func not callable:\n".print_r($this->get_connection_func, true);	
		}
				
		try
		{
			$this->conf = call_user_func($this->get_connection_func, $sample);
			$this->connect();
		}
		catch (Exception $e)
		{
			$this->mysqli = null;
			return $e->getMessage();
		}
	}
	
	public function get_tables_from_query($query)
	{
		$parser = new QueryTableParser();
		return  $parser->parse($query);
	}
	
	public function get_create($query)
	{
		if (!isset($this->mysqli))
		{
			return null;
		}
		
		$tables = $this->get_tables_from_query($query);
		if (!is_array($tables))
		{
			return $tables;
		}
		$create_tables = array();
		foreach ($tables as $table)
		{
			$result = $this->mysqli->query("SHOW CREATE TABLE {$table}");
			if (is_object($result) and $row = $result->fetch_array())
			{
				$create_tables[] = $row[1];
			}
		}
			
		//return print_r($tree, true);
		return join("\n\n",  $create_tables );
	}
	
	public function get_table_status($query)
	{
		if (!isset($this->mysqli))
		{
			return null;
		}

		$tables = $this->get_tables_from_query($query);
		$table_status = array();
		foreach($tables as $table)
		{
			$sql = "SHOW TABLE STATUS LIKE '{$table}'";
			$result = $this->mysqli->query($sql);
			if (is_object($result) and $row = $result->fetch_assoc())
			{
				$str = '';
				foreach ($row as $key => $value)
				{
					$str .= sprintf("%20s : %s\n", $key, $value);
				}
				$table_status[] = $str;
			}
		}
		return join("\n\n", $table_status);
	}

	
	public function explain($sample)
	{
		if (!isset($this->mysqli))
		{
			return null;
		}
		
		if (!preg_match("/^\s*SELECT/i", $sample['sample']))
		{
			return null;
		}
		
		try
		{
			$result = $this->explain_query($sample['sample']);
			if ($this->mysqli->mysql_errno)
			{
				return $this->mysqli->mysql_error . " (".$this->mysqli->mysql_errno.")";
			}
			
			if (!$result)
			{
				return "unknown error getting explain plan\n";
			}
			return $this->result_as_table($result);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	private function connect()
	{
		$required = array('host','user','password','db');
		foreach ($required as $r)
		{
			if (!isset($this->conf[$r]))
			{
				throw new Exception("Missing field {$r}");
			}
		}
				
		try {
            $this->mysqli = new mysqli();
            $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
			$this->mysqli->real_connect(
				$this->conf['host'],
				$this->conf['user'],
				$this->conf['password'],
				$this->conf['db'],
				$this->conf['port']
			);
        }
        catch (Exception $e)
        {
            throw new MySQLException(
                    sprintf("Timeout connecting to mysql on %s:%s", $this->conf['host'], $this->conf['port'] )
            );

        }
			
		if ( $this->mysqli->connect_errno || !$this->mysqli)
		{
			throw new Exception("Connection error: " . $this->mysqli->connect_error. "(".$this->mysqli->connect_errno.")");
		}
		
		return true;
	}
	
	private function explain_query($query)
	{
		return $this->mysqli->query("EXPLAIN ".$query);
	}
	
	/**
     * given a mysqli result handle, format a string to look like the mysql cli
     * type tables
     * @param   {MySQLi_Result}     $result     The result set handle
     * @return {string}     The formatted result set string
     **/
    function result_as_table($result)
    {   
        $sizes = array();
        $values = array();
        $columns = array();
        
        while ($row = $result->fetch_assoc())
        {
            foreach ( $row as $col_name => $value)
            {
                $len = strlen($value);
                if ($len > $sizes[$col_name])
                {
                    $sizes[$col_name] = $len;
                }
                
                $columns[$col_name] = $col_name;
            }
            $values[] = $row;
        }
        
        foreach ($columns as $col => $count)
        {
            $len = strlen($col);
            if ($len > $sizes[$col])
            {
                $sizes[$col] = $len;
            }
        }
        
        $column_order = array_keys($columns);
        
        $table  = self::make_rule($sizes, $column_order);
        $table .= self::make_row($sizes, $columns, $column_order);
        $table .= self::make_rule($sizes, $column_order);
        
        foreach ($values as $row)
        {
    //		print_r(array_values($row));
            $table .= self::make_row($sizes, $row, $column_order);
            $table .= self::make_rule($sizes, $column_order);
        }
        
        return $table;
    }
    
    /**
     * utility method for result_as_table
     */
    private static function make_row(array $sizes, array $values, array $order)
    {
        $row_start = '|';
        $row_end = '|';
        $col_sep = '|';
        $col_pad = ' ';
        
        $new_values = array();
        foreach ($order as $col)
        {
            $value = $values[$col];
            $size = $sizes[$col];
            $new_values[] = $col_pad. str_pad($value, $size, $col_pad, STR_PAD_RIGHT) . $col_pad;
        }
        
        return $row_start . join($col_sep, $new_values) . $row_end ."\n";
    }
        
    /**
     * utility method for result_as_table
     **/
    private static function make_rule(array $sizes, array $order )
    {
        $rule_fill = '-';
        $rule_sep = '+';
        $new_values = array();
        foreach ($order as $col)
        {
            $new_values[] = str_repeat($rule_fill, $sizes[$col]+2);
        }
        return $rule_sep . join($rule_sep, $new_values) . $rule_sep ."\n";
    }	
}

class QueryTableParser
{
	public $pos;
	public $query;
	public $len;
	public $table_tokens = array(
		'from',
		'join',
		'update',
		'into',
	);
	public function parse($query)
	{
		$this->query = preg_replace("/\s+/s"," ", $query);
		$this->pos = 0;
		$this->len = strlen($this->query);
		//print "<pre>";
		//print "parsing {$this->query}; length {$this->len}\n";
		
		
		$tables = array();
		while ($this->has_next_token())
		{
			$token = $this->get_next_token();
			//print "--> found $token\n";
			
			if (in_array(strtolower($token), $this->table_tokens))
			{
				
				$table = $this->get_next_token();
				
				if (preg_match("/\w+/", $table))
				{
					$table = str_replace('`','',$table);
					$tables[$table] ++;
				}
				
			}
		}
		//print "</pre>";
		
		return array_keys($tables);
	}
	
	private function has_next_token()
	{
		// at end 
		if ($this->pos >= $this->len)
		{
			return false;
		}
		return true;
	}
	
	private function get_next_token()
	{
		// get the pos of the next token boundary
		$pos = strpos($this->query, " ", $this->pos);
		//print "get next token {$this->pos} {$this->len} {$pos}\n";
		if ($pos === false)
		{
			$pos = $this->len;
		}
		
		// found next boundary
		$start = $this->pos;
		$len = $pos - $start;
		$this->pos = $pos+1;
		return substr($this->query, $start, $len);
	}
	
	
	
}
?>