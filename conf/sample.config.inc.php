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
$conf['datasources']['localhost'] = array(
	'host'	=> 'localhost',
	'port'	=> 3306,
	'db'	=> 'slow_query_log',
	'user'	=> 'root',
	'password' => '',
	'tables' => array(
		'global_query_review' => 'fact',
		'global_query_review_history' => 'dimension'
	),
	'source_type' => 'slow_query_log'
);

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
	'table_fields' => array( 'DIGEST', 'snippet', 'index_ratio', 'COUNT_STAR', 'SUM_LOCK_TIME','SUM_ROWS_AFFECTED','SUM_ROWS_SENT','SUM_ROWS_EXAMINED','SUM_CREATED_TMP_TABLES','SUM_SORT_SCAN','SUM_NO_INDEX_USED' )
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
	'query_advisor'	=> '/usr/bin/pt-query-advisor',

	'show_create'	=> true,
	'show_status'	=> true,

	'explain'	=>	function ($sample) {
		$conn = array();

		if (strlen($sample['hostname_max']) < 5)
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
		),
	),
	// custom fields
	'custom_fields'	=> array(
		'checksum' => 'checksum',
		'date'	=> 'DATE(ts_min)',
		'hour'	=> 'substring(ts_min,1,13)',
		'hour_ts'	=> 'unix_timestamp(substring(ts_min,1,13))',
		'minute_ts'     => 'unix_timestamp(substring(ts_min,1,16))',
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
		),
	),
	// custom fields
	'custom_fields'	=> array(
		'snippet' => 'LEFT(fact.DIGEST_TEXT,20)',
		'index_ratio' =>'ROUND(SUM_ROWS_EXAMINED/SUM_ROWS_SENT,2)',
		'rows_sent_avg' => 'ROUND(SUM_ROWS_SENT/COUNT_STAR,0)',

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
		'hour_ts'	=> 'unix_timestamp(substring(dimension.FIRST_SEEN,1,13))',
		'minute_ts'     => 'unix_timestamp(substring(dimension.FIRST_SEEN,1,16))',
		'minute'        => 'substring(dimension.FIRST_SEEN,1,16)',
	),

	'special_field_names' => array(
		'time'	 	=> 'FIRST_SEEN',
		'checksum'	=> 'DIGEST',
		'hostname'	=> 'hostname',
		'sample'	=> 'DIGEST_TEXT'
	),
);

$conf['advisor_rules'] = array(
    'ALI.001' => 'Note: Aliasing without the AS keyword. Explicitly using the AS keyword in column or table aliases, such as "tbl AS alias," is more readable than implicit aliases such as "tbl alias".',
    'ALI.002' => 'Warn: Aliasing the \'*\' wildcard. Aliasing a column wildcard, such as "SELECT tbl.* col1, col2" probably indicates a bug in your SQL. You probably meant for the query to retrieve col1, but instead it renames the last column in the *-wildcarded list.',
    'ALI.003' => 'Note: Aliasing without renaming. The table or column\'s alias is the same as its real name, and the alias just makes the query harder to read.',
    'ARG.001' => 'Warn: Argument with leading wildcard. An argument has a leading wildcard character, such as "%foo". The predicate with this argument is not sargable and cannot use an index if one exists.',
    'ARG.002' => 'Note: LIKE without a wildcard. A LIKE pattern that does not include a wildcard is potentially a bug in the SQL.',
    'CLA.001' => 'Warn: SELECT without WHERE. The SELECT statement has no WHERE clause.',
    'CLA.002' => 'Note: ORDER BY RAND(). ORDER BY RAND() is a very inefficient way to retrieve a random row from the results.',
    'CLA.003' => 'Note: LIMIT with OFFSET. Paginating a result set with LIMIT and OFFSET is O(n^2) complexity, and will cause performance problems as the data grows larger.',
    'CLA.004' => 'Note: Ordinal in the GROUP BY clause. Using a number in the GROUP BY clause, instead of an expression or column name, can cause problems if the query is changed.',
    'CLA.005' => 'Warn: ORDER BY constant column.',
    'CLA.006' => 'Warn: GROUP BY or ORDER BY different tables will force a temp table and filesort.',
    'CLA.007' => 'Warn: ORDER BY different directions prevents index from being used. All tables in the ORDER BY clause must be either ASC or DESC, else MySQL cannot use an index.',
    'COL.001' => 'Note: SELECT *. Selecting all columns with the * wildcard will cause the query\'s meaning and behavior to change if the table\'s schema changes, and might cause the query to retrieve too much data.',
    'COL.002' => 'Note: Blind INSERT. The INSERT or REPLACE query doesn\'t specify the columns explicitly, so the query\'s behavior will change if the table\'s schema changes; use "INSERT INTO tbl(col1, col2) VALUES..." instead.',
    'LIT.001' => 'Warn: Storing an IP address as characters. The string literal looks like an IP address, but is not an argument to INET_ATON(), indicating that the data is stored as characters instead of as integers. It is more efficient to store IP addresses as integers.',
    'LIT.002' => 'Warn: Unquoted date/time literal. A query such as "WHERE col<2010-02-12" is valid SQL but is probably a bug; the literal should be quoted.',
    'KWR.001' => 'Note: SQL_CALC_FOUND_ROWS is inefficient. SQL_CALC_FOUND_ROWS can cause performance problems because it does not scale well; use alternative strategies to build functionality such as paginated result screens.',
    'JOI.001' => 'Crit: Mixing comma and ANSI joins. Mixing comma joins and ANSI joins is confusing to humans, and the behavior differs between some MySQL versions.',
    'JOI.002' => 'Crit: A table is joined twice. The same table appears at least twice in the FROM clause.',
    'JOI.003' => 'Warn: Reference to outer table column in WHERE clause prevents OUTER JOIN, implicitly converts to INNER JOIN.',
    'JOI.004' => 'Warn: Exclusion join uses wrong column in WHERE. The exclusion join (LEFT OUTER JOIN with a WHERE clause that is satisfied only if there is no row in the right-hand table) seems to use the wrong column in the WHERE clause. A query such as "... FROM l LEFT OUTER JOIN r ON l.l=r.r WHERE r.z IS NULL" probably ought to list r.r in the WHERE IS NULL clause.',
    'RES.001' => 'Warn: Non-deterministic GROUP BY. The SQL retrieves columns that are neither in an aggregate function nor the GROUP BY expression, so these values will be non-deterministic in the result.',
    'RES.002' => 'Warn: LIMIT without ORDER BY. LIMIT without ORDER BY causes non-deterministic results, depending on the query execution plan.',
    'STA.001' => 'Note: != is non-standard. Use the <> operator to test for inequality.',
    'SUB.001' => 'Crit: IN() and NOT IN() subqueries are poorly optimized. MySQL executes the subquery as a dependent subquery for each row in the outer query. This is a frequent cause of serious performance problems. This might change version 6.0 of MySQL, but for versions 5.1 and older, the query should be rewritten as a JOIN or a LEFT OUTER JOIN, respectively.',
);
/**
 * end of configuration settings
 */
?>

