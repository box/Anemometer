<?php
/**
 * This is the main loader and controller init script for the Anemometer project
 * It just loads the config, creates a controller and invokes it.  See
 * lib/Anemometer.php for the main project code.
 * @author Gavin Towey <gavin@box.com>, Geoffrey Anderson <ganderson@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 **/

// Ensure we're on php 5.3 or newer
if (strnatcmp(phpversion(), '5.3') < 0) {
	print "Anemometer requires PHP 5.3 or newer. You have ".phpversion();
	die();
}

if (!function_exists('bcadd')) {
	print "Anemometer requires the BCMath extension";
	die();
}

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";
require "Anemometer.php";

//do not display any errors
//error_reporting(E_ALL);
ini_set('display_errors', 0);

//for security reason(XSS and SQL Injection)
$action = isset($_GET['action']) ? htmlentities(addslashes(trim($_GET['action']))) : 'index';
if(!in_array($action, array('report', 'api', 'show_query', 'graph_search')))
{
        $action = 'index';
}

//for security reason(XSS and SQL Injection)
$args = array('datasource',
    'dimension-ts_min_start',
    'dimension-ts_min_end',
    'fact-first_seen',
    'dimension-hostname_max',
    'fact-group',
    'table_fields',
    'fact-order',
    'fact-having',
    'fact-limit',
    'fact-where',
    'fact-sample',
    'fact-reviewed_status',
    'fact-checksum',
);

//also for security reason(XSS and SQL Injection)
foreach($args as $arg)
{
    if(isset($_GET["$arg"]))
    {
        $_GET["$arg"] = htmlspecialchars(htmlentities(addslashes(trim($_GET["$arg"]))));
    }
}

$conf = array();
@include "conf/config.inc.php";
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
