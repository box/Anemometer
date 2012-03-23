<?php
set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";
require "Anemometer.php";
/*
	* todo: implement exra fields ... somehow<br> or remove extra fields for version 1
	* todo: add reviewed_status to search fields ... tab for extended search?<br>
	* todo: possible report for reviewed (aggregate by reviewd type, reviwed/unreviewed / new  or old, etc)<br>
	* todo: cleanup MySQLReport object<br>
	* todo: document / organise it all
	* maybe make explain / create / etc ajax requests so we don't do requests when nobody looks at it
*/
error_reporting(E_ALL);
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

$conf = array();
include "conf/config.inc.php";
if (empty($conf))
{
	$action = 'noconfig';
}

$controller = new Anemometer($conf);
if (is_callable(array($controller, $action )))
{
	$controller->$action();
}
else
{
	print "Invalid action ($action)";
}

?>