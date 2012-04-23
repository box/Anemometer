<?php
/**
 *
 * Global helper functions for the weatherstation app
 * @author Gavin Towey <gavin@box.net>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2012-01-01
 */

// @todo validation?
/**
 * search global request variables $_POST and $_GET in that order and return
 * the first defined value for the given key
 *
 * @param string $name
 * @return mixed    the value of the variable name (if any) or null.
 */
function get_var($name)
{
    $sources = array($_POST, $_GET);
    foreach ($sources as $s)
    {
        if (isset($s[$name]))
        {
            return $s[$name];
        }
    }
    return null;
}

/**
 * return the full URL for the base page of the site.
 *
 * @return string
 */
function site_url()
{
    if (isset($_SERVER['HTTPS']))
    {
	$proto = 'https://';
    }
    else
    {
        $proto = 'http://';
    }
    return $proto . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}


/**
 * wrap html pre tags around the given string, with class="prettyprint"
 *
 * @param string $string
 */
function prettyprint($string)
{
    print '<pre class="prettyprint">';
    print $string;
    print "</pre>";
}


/**
 * die with the error message if the given result handle or mysqli object has an error
 *
 * @param MySQLi_Result $result
 * @param MySQLi $mysqli
 */
function check_mysql_error($result, $mysqli)
{
    if (!$result)
    {
        // @TODO put markup in a view
        die("<div class=\"alert alert-error\"><strong>".$mysqli->errno."</strong><br>".$mysqli->error ."</div>");
    }
}

?>
