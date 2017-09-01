<?php
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
