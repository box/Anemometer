<?php

$group = get_var('fact-group');
$debug = get_var('debug');
if ($debug)
{
	print "<pre>".$sql."</pre>";
}

$series = array_filter($columns, function ($x) use ($group) { return $x == $group ? false : true; });
$wtfdata = array();
if (count($result))
{
	if (preg_match("/^\d+$/", $result[0][$group]))
	{
		$is_datetime = false;
	}
	else
	{
		$is_datetime = true;
	}
}
foreach ($result as $row)
{
	foreach ($series as $col)
	{
		$date = $row[$group];
		if ($is_datetime)
		{
			$parts = strptime($date, "%Y-%m-%d %H:%M:%S");
			$date =  mktime($parts['tm_hour'], $parts['tm_min'],$parts['tm_sec'], $parts['tm_mon']+1, $parts['tm_mday'], $parts['tm_year']+1900);
		}
		$wtfdata[$col][] = array( $date, $row[$col]);
	}
}

foreach ($series as $col)
{
    // this is intended to capture checksum values when we pivot on checksums
    // and convert them to hex.  Done here because Javascript can't handle large enough
    // int values to convert them w/out loss of precision.
    // this may have some unintended consequences, but it's the easist way to solve the JS
    // limitation for now
    $col_label = $col;
    if (is_numeric($col_label)) {
        $col_label = dec2hex($col_label);
    }
	$finalfingdata[] = array( 'label' => $col_label, 'data' => $wtfdata[$col]);
}
print json_encode($finalfingdata);

?>