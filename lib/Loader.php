<?php
/**
 * class to mimic codigniter's syntax for loading views from the controller
 *
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 */
class Loader
{

    /**
     * Finds and displays the given view, and makes the values in $data available to it.
     * The name of the view is passed in without the leading "views/" directory or the trailing
     * ".php" extension.  So loading a view with $this->view("myview"); would look for a file
     * called "views/myview.php"
     *
     * the data is made available my taking the keys of $data, and assigning them to
     * a locally scoped variable of the same name.  array( 'title' => 'The Title' )
     *  would be available in the view as $title.
     *
     * @param string $view_name The name of the view to load
     * @param type $data array of values to make available to the view
     */
    public function view($view_name, $data = null)
    {
        // make local variables out of array keys
        if (is_array($data))
        {
            foreach($data as $key => $value)
            {
                ${$key} = $value;
            }
        }

        // find and include the view
        $view_name = "views/{$view_name}.php";
        if (file_exists($view_name))
        {
            include $view_name;
        }
        else
        {
            print "<div class=\"alert alert-error\"><strong>Error</strong> {$view_name} not found</div>";
        }
    }

}

?>