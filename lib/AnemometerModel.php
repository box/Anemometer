<?php
require "QueryExplain.php";


class AnemometerModel
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
	
	public function get_default_report_action()
	{
		return $this->conf['default_report_action'];
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
			throw new Exception($this->mysqli->connect_error);
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

?>