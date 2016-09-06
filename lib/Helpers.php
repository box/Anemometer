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
    return $_SERVER['SCRIPT_NAME'];
}


/**
 * wrap html pre tags around the given string, with class="prettyprint"
 *
 * @param string $string
 * @return string Formatted html
 */
function prettyprint($string)
{
    $return = '<pre class="prettyprint">';
    $return .= "\n$string\n";
    $return .= "</pre>";
	return $return;
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


/**
 * Flatten a multiple dim array into a single list
 *
 * @param array $array
 * @return array
 */

function flatten_array($array)
{
    $return = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $value = flatten_array($value);
            $return = array_merge($return, $value);
        }
        else
            $return[] = $value;
    }
    return $return;
}

function dec2hex($str)
{
	$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
	// Result value
	$hexval = '';
	// The quotient of each division operation
	$quotient = $str;
	$divisor = $str;
	// The ending condition
	$flag = true;
	while($flag)
	{
		$len = strlen($divisor);
		$pos = 1;
		$quotient = 0;
		// Take the first two digits as temp divisor and advance by 1 each iteration
		$div = substr($divisor, 0, 2);
		$remainder = $div[0];
		while($pos < $len)
		{
			// Calculate the next div
			$div = $remainder == 0 ? $divisor[$pos] : $remainder.$divisor[$pos];
			$remainder = $div % 16;
			$quotient = $quotient.floor($div/16);
			$pos++;
		}
		// Recast the divisor as string to make the $divisor[$pos] work
		$quotient = trim_left_zeros($quotient);
		$divisor = "$quotient";
		$hexval = $hex[$remainder].$hexval;
		// If the divisor is smaller than 15 then end the iteration
		if (strlen($divisor)<=2)
		{
			if ($divisor<15)
			{
				$flag = false;
			}
		}
	}
	$hexval = $hex[$quotient].$hexval;
	$hexval = trim_left_zeros($hexval);
	// Pad zeros (only because we are using this function for 64-bit integers)
	//$hexval = str_repeat('0', 16-strlen($hexval)).$hexval;
	return $hexval;
}

function trim_left_zeros($str)
{
	$str = ltrim($str, '0');
	if (empty($str))
	{
		$str = '0';
	}
	return $str;
}
