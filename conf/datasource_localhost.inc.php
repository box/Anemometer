<?php
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

