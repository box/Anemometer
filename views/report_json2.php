<?php

$group = get_var('fact-group');
$series = array_filter($columns, function ($x) use ($group) { return $x == $group ? false : true; });
$wtfdata = array();
foreach ($result as $row)
{
	foreach ($series as $col)
	{
		$wtfdata[$col][] = array( $row[$group], $row[$col]);
	}
}

foreach ($series as $col)
{
	$finalfingdata[] = array( 'label' => $col, 'data' => $wtfdata[$col]);
}
print json_encode($finalfingdata);

?>