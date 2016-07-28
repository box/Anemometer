<?php
/**
 * This is the configuration file for the Anemometer application.  All of your
 * environment specific settings will go here, and there should not be any need
 * edit other files.
 *
 * @author Gavin Towey <gavin@box.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2012-01-01
 *
 **/

/**
 * Datasources are a combination of database server, database name and the tables
 * used for reporting.  This enables you to set up collection to different review
 * and review_history tables to keep data separate.
 *
 * For instance, you might want a different datasource for your development environment
 * and your production environment, and keep them on different servers.  Or
 * you might want to log different applications into different tables, and have
 * something like app1_query_review and app2_query_review.
 *
 * The array of tables *must* include the review and review_history table (with
 * whatever name you gave them when collecting the data.)  The review table must
 * be defined as 'fact' and the history table must be defined as 'dimension'.
 * These are table aliases that are used when building the search form and queries
 * against them.
 *
 * You can add as many datasources as you wish.  If you only define one, then
 * the index page with no arguments will automatically take you to the report page.
 * If there are more than one, then you will be given a page to choose which
 * one you want to report on.
 *
 */
 
foreach(glob("conf/datasource_*.inc.php") as $datasource) {
	require_once($datasource);
}

/**
 * If you're using Anemometer with MySQL 5.6's performance schema,
 * then use this datasource
 *
$conf['datasources']['mysql56'] = array(
	'host'	=> 'localhost',
	'port'	=> 3306,
	'db'	=> 'performance_schema',
	'user'	=> 'root',
	'password' => '',
	'tables' => array(
		'events_statements_summary_by_digest' => 'fact',
	),
	'source_type' => 'performance_schema'
);
*/

/**
 *  if you're collecting history form the performance_schema table and
 *  saving it, then use this datasource which will allow you to see graphs
 *  and query details.  By using an additional process to save performance schema
 *  data, you can insert it into a table structure similar to that used by
 *  pt-query-digest.
$conf['datasources']['localhost_history'] = array(
	'host'	=> 'localhost',
	'port'	=> 3306,
	'db'	=> 'slow_query_log',
	'user'	=> 'root',
	'password' => '',
	'tables' => array(
		'events_statements' => 'fact',
		'events_statements_history' => 'dimension'
	),
	'source_type' => 'performance_schema_history'
);
**/


/**
 * This setting defines which report interface you get first when selecting a
 * datasource.  There are two possible values: report and graph_search
 *
 * 'report' will take you to the more verbose search form that displays
 * results as an html table.
 *
 * 'graph_search' will take you to the search form which displays results as an
 * interactive graph, and lets you select ranges in that graph.  The queries from
 * the selected time range will be displayed as a table below.
 **/
$conf['default_report_action'] = 'report';

/**
 * Set the reviewers list to the names of all people who might review queries.
 * This allows you to better track who has evaluated a query.
 *
 * These names will be displayed near the comments section on detail page when you
 * select a query.
 *
 * review_types can be configured with whichever values you want.  A basic list has
 * been provided.  These will also show up on the query detail page, and the status
 * can be set when you review a query.
 **/
$conf['reviewers'] = array( 'dba1','dba2');
$conf['review_types'] = array( 'good', 'bad', 'ticket-created', 'needs-fix', 'fixed', 'needs-analysis', 'review-again');

/**
 * These are default values for reports.  You can choose which column headings you
 * wish to see by default, the date range for the report, and other values as well.
 * Take care when changing values here, since you can create some pretty strange results.
 * It's best to keep a copy of the original values so you can change them back if needed.
 *
 * There are three reports here:  report, history and graph
 *
 * report_defaults contains the settings for the basic html table search.
 *
 * history_defaults contains the settings for the query history shown at the bottom
 * of the query details page.
 *
 * graph_defaults contains the settings for the interactive  graph search page
 * where you can select specific time ranges from a graph.
 **/
$conf['history_defaults'] = array(
	'output'		=> 'table',
	'fact-group'	=> 'date',
	'fact-order'	=> 'date DESC',
	'fact-limit' => '90',
	'dimension-ts_min_start' => date("Y-m-d H:i:s", strtotime( '-90 day')),
	'dimension-ts_min_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array('date', 'index_ratio','query_time_avg','rows_sent_avg','ts_cnt','Query_time_sum','Lock_time_sum','Rows_sent_sum','Rows_examined_sum','Tmp_table_sum','Filesort_sum','Full_scan_sum')
);

$conf['report_defaults'] = array(
	'fact-group'	=> 'checksum',
	'fact-order'	=> 'Query_time_sum DESC',
	'fact-limit' => '20',
	'dimension-ts_min_start' => date("Y-m-d H:i:s", strtotime( '-1 day')),
	'dimension-ts_min_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array('checksum','snippet', 'index_ratio','query_time_avg','rows_sent_avg','ts_cnt','Query_time_sum','Lock_time_sum','Rows_sent_sum','Rows_examined_sum','Tmp_table_sum','Filesort_sum','Full_scan_sum'),
	'dimension-pivot-hostname_max' => null
);

$conf['graph_defaults'] = array(
	'fact-group'	=> 'minute_ts',
	'fact-order'	=> 'minute_ts',
	'fact-limit' => '',
	'dimension-ts_min_start' => date("Y-m-d H:i:s", strtotime( '-7 day')),
	'dimension-ts_min_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array('minute_ts'),
	// hack ... fix is to make query builder select the group and order fields,
	// then table fields only has to contain the plot_field
	'plot_field' => 'Query_time_sum',
);

// these are the default values for mysql 5.6 performance schema datasources
$conf['report_defaults']['performance_schema'] = array(
	'fact-order'	=> 'SUM_TIMER_WAIT DESC',
	'fact-limit' => '20',
	'fact-group' => 'DIGEST',
	'table_fields' => array( 'DIGEST', 'snippet', 'index_ratio', 'COUNT_STAR', 'SUM_TIMER_WAIT', 'SUM_LOCK_TIME','SUM_ROWS_AFFECTED','SUM_ROWS_SENT','SUM_ROWS_EXAMINED','SUM_CREATED_TMP_TABLES','SUM_SORT_SCAN','SUM_NO_INDEX_USED' )
);

// these are the default values for mysql 5.6 performance schema datasources
$conf['history_defaults']['performance_schema'] = array(
	'fact-order'	=> 'SUM_TIMER_WAIT DESC',
	'fact-limit' => '20',
	'fact-group' => 'DIGEST',
	'table_fields' => array( 'DIGEST', 'index_ratio', 'COUNT_STAR', 'SUM_LOCK_TIME','SUM_ROWS_AFFECTED','SUM_ROWS_SENT','SUM_ROWS_EXAMINED','SUM_CREATED_TMP_TABLES','SUM_SORT_SCAN','SUM_NO_INDEX_USED' )
);

// these are the default values for using performance schema to save your own
// query history in a table structure similar to percona's pt-query-digest format
$conf['report_defaults']['performance_schema_history'] = array(
	'fact-group'	=> 'DIGEST',
	'fact-order'	=> 'SUM_TIMER_WAIT DESC',
	'fact-limit' => '20',
	'dimension-FIRST_SEEN_start' => date("Y-m-d H:i:s", strtotime( '-1 day')),
	'dimension-FIRST_SEEN_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array( 'DIGEST', 'snippet', 'index_ratio', 'COUNT_STAR', 'SUM_LOCK_TIME','SUM_ROWS_AFFECTED','SUM_ROWS_SENT','SUM_ROWS_EXAMINED','SUM_CREATED_TMP_TABLES','SUM_SORT_SCAN','SUM_NO_INDEX_USED' )
);

$conf['graph_defaults']['performance_schema_history'] = array(
	'fact-group'	=> 'minute_ts',
	'fact-order'	=> 'minute_ts',
	'fact-limit' => '',
	'dimension-FIRST_SEEN_start' => date("Y-m-d H:i:s", strtotime( '-7 day')),
	'dimension-FIRST_SEEN_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array('minute_ts'),
	// hack ... fix is to make query builder select the group and order fields,
	// then table fields only has to contain the plot_field
	'plot_field' => 'SUM_TIMER_WAIT',
	'dimension-pivot-hostname_max' => null
);

$conf['history_defaults']['performance_schema_history'] = array(
	'output'		=> 'table',
	'fact-group'	=> 'date',
	'fact-order'	=> 'date DESC',
	'fact-limit' => '90',
	'dimension-FIRST_SEEN_start' => date("Y-m-d H:i:s", strtotime( '-90 day')),
	'dimension-FIRST_SEEN_end'	=> date("Y-m-d H:i:s"),
	'table_fields' => array( 'date', 'snippet', 'index_ratio', 'COUNT_STAR', 'SUM_LOCK_TIME','SUM_ROWS_AFFECTED','SUM_ROWS_SENT','SUM_ROWS_EXAMINED','SUM_CREATED_TMP_TABLES','SUM_SORT_SCAN','SUM_NO_INDEX_USED' )
);
/**
 * Plugins are optional extra information that can be displayed, but often
 * relies on information specific to your system, and has to be set manually.
 *
 * This includes the explain plan information, percona toolkit plugins like
 * visual explain and query advisor output, and the table structure and status
 * information.
 *
 * To get the explain plan information, this application needs a way to figure
 * out exactly where the original query came from, so it can connect to the database
 * and run EXPLAIN SELECT ... ;  There is not enough information collected by
 * the query digest to always know this information, so a callback function is
 * used to help provide the extra information.
 *
 * This callback function is passed the full row from the history table, this gives
 * it access to the full query sample collected, and fields such as hostname_max
 * if they were defined in your _history table and collected.
 *
 * To get the explain plan, the callback function must return an array that looks like
 * the following:
 *
 * array(
	'host'		=> $host,
	'port'		=> $port,
	'db'		=> $database_name,
	'user'		=> $username,
	'password'	=> $password
   );
 *
 * If the callback cannot return that information, then the application will
 * not display the EXPLAIN plan.
 *
 * A sample callback has been provided below, you will at least need to fill
 * in the username and password fields, and it might work for most users.
 *
 *
 * pt-visual-explain and pt-query-advisor:
 * For these plugins, you simply need to provide the full path to those scripts
 * on your system.  They will be called when appropriate.
 *
 * SHOW TABLE STATUS and SHOW CREATE TABLE:
 * Simply set these to true if you want the information to be displayed.  They
 * will use the same connection information extracted by the explain callback
 * plugin.  If a valid database connection can't be made from the result of the
 * explain plugin, then these sections will not be displayed.
 *
 */
$conf['plugins'] = array(

	'visual_explain' => '/usr/bin/pt-visual-explain',
#	percona toolkit has removed query advisor
#	'query_advisor'	=> '/usr/bin/pt-query-advisor',

	'show_create'	=> true,
	'show_status'	=> true,

	'explain'	=>	function ($sample) {
		$conn = array();

		if (!array_key_exists('hostname_max',$sample) or strlen($sample['hostname_max']) < 5)
		{
			return;
		}

		$pos = strpos($sample['hostname_max'], ':');
		if ($pos === false)
		{
			$conn['port'] = 3306;
			$conn['host'] = $sample['hostname_max'];
		}
		else
		{
			$parts = preg_split("/:/", $sample['hostname_max']);
			$conn['host'] = $parts[0];
			$conn['port'] = $parts[1];
		}

		$conn['db'] = 'mysql';
		if ($sample['db_max'] != '')
		{
			$conn['db'] = $sample['db_max'];
		}

		$conn['user'] = 'root';
		$conn['password'] = '';

		return $conn;
	},
);

/**
 * This is configuration information for how the actual sql queries are built
 * from the form values.  It shouldn't be necessary to modify any of these
 * values, and you can possibly break reporting functionality by doing so.
 *
 * For more information, see the phpDoc or comments in the MySQLTableReport class.
 */
$conf['reports']['slow_query_log'] = array(
	// joins
	'join'	=> array (
		'dimension'	=> 'USING (`checksum`)'
	),

	// form fields
	// these are the fields that the report object looks for to build the query
	// they are defined by the table *alias* and not the name.
	// The names are defined by the datasource
	'fields'	=> array(
		'fact' => array(
			'group'		=> 'group',
			'order'		=> 'order',
			'having'	=> 'having',
			'limit'		=> 'limit',
			'first_seen'=> 'clear|reldate|ge|where',
			'where'		=>	'raw_where',
			'sample'	=> 'clear|like|where',
			'checksum'	=>	'clear|where',
			'reviewed_status' => 'clear|where',

		),

		'dimension' => array(
			'extra_fields' 	=> 	'where',
			'hostname_max'	=> 'clear|where',
			'ts_min'	=>	'date_range|reldate|clear|where',
			'pivot-hostname_max' => 'clear|pivot|select',
			'pivot-checksum' => 'clear|pivot|select',
		),
	),
	// custom fields
	'custom_fields'	=> array(
		'checksum' => 'checksum',
		'date'	=> 'DATE(ts_min)',
		'hour'	=> 'substring(ts_min,1,13)',
		'hour_ts'	=> 'round(unix_timestamp(substring(ts_min,1,13)))',
		'minute_ts'     => 'round(unix_timestamp(substring(ts_min,1,16)))',
		'minute'        => 'substring(ts_min,1,16)',
		'snippet' => 'LEFT(dimension.sample,20)',
		'index_ratio' =>'ROUND(SUM(Rows_examined_sum)/SUM(rows_sent_sum),2)',
		'query_time_avg' => 'SUM(Query_time_sum) / SUM(ts_cnt)',
		'rows_sent_avg' => 'ROUND(SUM(Rows_sent_sum)/SUM(ts_cnt),0)',
	),

	'callbacks'     => array(
		'table' => array(
			'date'  => function ($x) { $type=''; if ( date('N',strtotime($x)) >= 6) { $type = 'weekend'; } return array($x,$type); },
			'checksum' => function ($x) { return array(dec2hex($x), ''); }
		)
	)

);

$conf['reports']['performance_schema'] = array(
	// form fields
	'fields'	=> array(
		'fact' => array(
			//'group'		=> 'group',
			'order'		=> 'order',
			'having'	=> 'having',
			'limit'		=> 'limit',
			'first_seen'	=> 'date_range|reldate|clear|where',
			'where'		=> 'raw_where',
			'DIGEST'	=> 'clear|where',
			'DIGEST_TEXT' => 'clear|like|where',
		        'group'		=> 'group',
		),
	),
	// custom fields
	'custom_fields'	=> array(
		'snippet' => 'LEFT(fact.DIGEST_TEXT,20)',
		'index_ratio' =>'ROUND(SUM_ROWS_EXAMINED/SUM_ROWS_SENT,2)',
		'rows_sent_avg' => 'ROUND(SUM_ROWS_SENT/COUNT_STAR,0)',

	),

	'special_field_names' => array(
		'time'	 	=> 'FIRST_SEEN',
		'checksum'	=> 'DIGEST',
		'sample'	=> 'DIGEST_TEXT',
		'fingerprint'   => 'DIGEST_TEXT',
	),
);

$conf['reports']['performance_schema_history'] = array(
	// joins
	'join'	=> array (
		'dimension'	=> 'USING (`DIGEST`)'
	),

	// form fields
	'fields'	=> array(
		'fact' => array(
			'group'		=> 'group',
			'order'		=> 'order',
			'having'	=> 'having',
			'limit'		=> 'limit',
			'first_seen'=> 'clear|reldate|ge|where',
			'where'		=>	'raw_where',
			'DIGEST_TEXT'	=> 'clear|like|where',
			'DIGEST'	=>	'clear|where',
			'reviewed_status' => 'clear|where',

		),

		'dimension' => array(
			'extra_fields' 	=> 	'where',
			'hostname'	=> 'clear|where',
			'FIRST_SEEN'	=>	'date_range|reldate|clear|where',
			'pivot-hostname' => 'clear|pivot|select',
		),
	),
	// custom fields
	'custom_fields'	=> array(
		'date'	=> 'DATE(fact.FIRST_SEEN)',
		'snippet' => 'LEFT(fact.DIGEST_TEXT,20)',
		'index_ratio' =>'ROUND(SUM_ROWS_EXAMINED/SUM_ROWS_SENT,2)',
		'rows_sent_avg' => 'ROUND(SUM_ROWS_SENT/COUNT_STAR,0)',
		'hour'	=> 'substring(dimension.FIRST_SEEN,1,13)',
		'hour_ts'	=> 'round(unix_timestamp(substring(dimension.FIRST_SEEN,1,13)))',
		'minute_ts'     => 'round(unix_timestamp(substring(dimension.FIRST_SEEN,1,16)))',
		'minute'        => 'substring(dimension.FIRST_SEEN,1,16)',
	),

	'special_field_names' => array(
		'time'	 	=> 'FIRST_SEEN',
		'checksum'	=> 'DIGEST',
		'hostname'	=> 'hostname',
		'sample'	=> 'DIGEST_TEXT'
	),
);

/**
 * end of configuration settings
 */
?>
