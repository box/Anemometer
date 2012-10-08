<div class="row">
	<form action="<?php echo site_url() ?>" method="GET" class="form-inline">
	<input type="hidden" name="action" value="graph_search">
	<input type="hidden" name="datasource" value="<?php echo $datasource; ?>">
	<div class="row">
		<div class="span3">
			From<br>
			<div class="input-append">
				<input type="text" class="span2" name="dimension-ts_min_start" id="dimension-ts_min_start" value="<?php echo get_var('dimension-ts_min_start'); ?>">
				<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
			</div>
		</div>

		<div class="span4">
			To<br>
			<div class="input-append">
				<input type="text" class="span2" name="dimension-ts_min_end" id="dimension-ts_min_end" value="<?php echo get_var('dimension-ts_min_end'); ?>">
				<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
			</div>
		</div>

	<div class="span4">
		Column to plot<br>
		<select name="plot_field" class="span3">
				<optgroup label="Custom Fields">
				<?php foreach ($custom_fields as $f)  { ?>
					<option value="<?php echo $f ?>" <?php if (get_var('plot_field') == $f) { echo "SELECTED"; } ?>><?php echo $f ?></option>
				<?php } ?>
				</optgroup>

				<?php foreach (array_keys($table_fields) as $table)  { ?>
					<optgroup label="<?php echo $table; ?>">
						<?php foreach ($table_fields[$table] as $f)  { ?>
							<option value="<?php echo $f ?>" <?php if (get_var('plot_field') == $f) { echo "SELECTED"; } ?>><?php echo $f ?></option>
						<?php } ?>
					</optgroup>
				<?php } ?>
			</select>
	</div>
	</div>
	<div class="row">
		<div class="span3" >
				Filter By Host<br>
			<select name="dimension-hostname_max" class="span3 combobox">
				<option value=""></option>
				<?php foreach ($hosts as $h) { ?>
					<option value="<?php echo $h ?>" <?php if (isset($hostname_max) AND $hostname_max == $h ) { echo "SELECTED"; } ?>><?php echo $h ?></option>
				<?php } ?>
			</select>
		</div>

		<div class="span4" >
			<input type="checkbox" name="dimension-pivot-hostname_max" value='ts_cnt'<?php echo (isset($dimension_pivot_hostname_max) ? ' CHECKED ' : '') ?>> Show each host as a separate series

		</div>

		<div class="span4">

				<input type="submit" class="btn-primary btn-large" name="submit" value="Search">

		</div>
	</div>

	</form>
</div>
<hr>

<div class="row">
	<div id="theplot" class="span12" style="height: 300px;"></div>
</div>
<div class="row">
	<p>You selected: <span id="selection"></span></p>
	<p><a id="reset_selection" value="Clear selection" class="btn" href="javascript:void(0);"><i class="icon-fire"></i> Reset Selection</a>
		<a id="permalink_btn" class="btn" href="javascript:void(0);"><i class="icon-magnet"></i> Graph Permalink</a></p>
</div>
<hr>
	</div></div>
<span id="report_table"><center><img src="img/ajax-loader.gif"></center></span>

<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
// urls to retrieve data from
var GRAPH_DATA_URL = "<?php echo $ajax_request_url ?>";
var GRAPH_PERMALINK_URL = "<?php echo $graph_permalink; ?>";
var TABLE_BASE_URL = "<?php echo $ajax_table_request_url_base ?>"
var TABLE_URL_TIME_START_PARAM = "<?php echo $table_url_time_start_param ?>"
var TABLE_URL_TIME_END_PARAM = "<?php echo $table_url_time_end_param ?>"

// Setup options for the plot
var FLOT_OPTS = {
	series: {
		lines: { show: true }, // line graphs!
		points: { show: true} // draw individual data points
	},
	legend: { noColumns: 2 },
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" } // any mouse selections should be along x axis
};

// Placeholder for data to plot
var DATA = [];


/**
 * Callback function for drawing the graph after data is retrieved from an AJAX call
 * @param data 	The array of objects containing time series data to plot.
 */
function new_plot_data(data) {
	// flot requires milliseconds, so convert the timestamp from seconds to milliseconds
	for ( var i = 0; i < data.length; i++ )
	{
		for ( var j = 0; j < data[i].data.length; j++ )
		{
			data[i].data[j][0] = data[i].data[j][0] * 1000;
			data[i].data[j][0] = data[i].data[j][0] - (60*60*7*1000);
		}
	}
	var theplot = $("#theplot"); // get the graph div
	DATA = data;
	plot_obj = $.plot(theplot, DATA, FLOT_OPTS);
	setup_selection(theplot);
}

/**
 * Function to left pad a value (needed for date padding)
 * @param pad_this 	the data to pad (this will be converted to a string)
 * @param padding 	a string of what to left pad the data with
 * @param amount 	how much padding to apply.
 */
function left_pad(pad_this, padding, amount)
{
	var s = String(pad_this);
	var padded_str = '';
	if(s.length < amount)
	{
		for ( var i = 1; i < amount; i++)
		{
			padded_str += padding;
		}
		padded_str += pad_this;
	}
	else
	{
		padded_str += pad_this;
	}
	return padded_str;
}

/**
 * convert a date object to an ANSI-compliant date string (e.g. YYYY-mm-dd HH:MM:ss)
 * @param d 	the javascript Date object
 */
function to_sql_date(d)
{
	// put the year together in the form of YYYY-MM-DD
	ansi_date = d.getFullYear() + '-' + left_pad(d.getMonth()+1, '0', 2) + '-' + left_pad(d.getDate(), '0', 2);
	ansi_date += ' ';
	// put the time together as HH:MM:ss and append to the year
	ansi_date += left_pad(d.getHours(), '0', 2) + ':' + left_pad(d.getMinutes(), '0', 2) + ':' + left_pad(d.getSeconds(), '0', 2);
	return ansi_date;
}

/**
 * Register an event listener on the div with the flot graph so selection events
 * from the mouse can be registered for "zoom in" functionality.
 * @param theplot 	a JQuery object of the div containing the flot graph.
*/
function setup_selection(theplot) {
	theplot.bind("plotselected", function (event, ranges) {
		var plot = $.plot(theplot, DATA, $.extend ( true, {}, FLOT_OPTS, {
			xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
		}));

		// need a date object to shove timestamp into for conversion to ANSI-type date string
		d = new Date();

		// get start datetime for selected fields
		d.setTime(Math.floor(ranges.xaxis.from + (60*60*7*1000)));
		start_time = to_sql_date(d);

		// get end datetime for selected fields
		d.setTime(Math.floor(ranges.xaxis.to + (60*60*7*1000)));
		end_time = to_sql_date(d);

		// construct a url with the new time frame the graph is focused on to populate the table on the page.
		var new_url_start_end_params = '&' + escape(TABLE_URL_TIME_START_PARAM) + '=' + escape(start_time) + '&' + escape(TABLE_URL_TIME_END_PARAM)  + '=' + escape(end_time);
		var new_table_data_url = TABLE_BASE_URL + new_url_start_end_params;

		// Plop a shiny loading spinner in place of the table until the AJAX call finishes :)
		$('#report_table').html('<center><img src="img/ajax-loader.gif"></center>');

		// Update the permalink button
		$('#permalink_btn').attr('href', GRAPH_PERMALINK_URL + new_url_start_end_params);

		// Get the data for the table and re-populate it!
		$.ajax({
			url: new_table_data_url,
			method: 'GET',
			dataType: 'html',
			success: show_table_data
		});

		// Throw the selected time values just under the graph for clarity
		$('#selection').text(start_time + " to " + end_time);
		$('#dimension-ts_min_start').val(start_time);
		$('#dimension-ts_min_end').val(end_time);
	});

	theplot.bind("plotunselected", function (event) {
		$("#selection").text("");
	});
}

/**
 * Insert new table data into the appropriate div on the page
 * @param data	HTML to insert at the report_table dive on the page
 */
function show_table_data(data) {
	var report_table = $('#report_table');
	report_table.html(data);
	prettyPrint();
}



$(document).ready( function ()  {
	// Setup the search widget stuff!
	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$('.combobox').combobox();
	prettyPrint();

	// div to insert the flot graph in
	var theplot = $("#theplot");

	// initialize the empty flot graph
	var plot_obj = $.plot(theplot, DATA, FLOT_OPTS);

	// Store references to the initial start/end times for graph resets.
	var initial_start_time = $('#dimension-ts_min_start').val();
	var initial_end_time = $('#dimension-ts_min_end').val();

	// URL to get data for the table using the base time values on the page. This is also used to 'reset' the table.
	var url_start_end_params = '&' + escape(TABLE_URL_TIME_START_PARAM) + '=' + escape(initial_start_time) + '&' + escape(TABLE_URL_TIME_END_PARAM)  + '=' + escape(initial_end_time);
	var table_url_now = TABLE_BASE_URL + url_start_end_params;

	// Set the permlink button to have a link to the initial graph plot.
	$('#permalink_btn').attr('href', GRAPH_PERMALINK_URL + url_start_end_params);

	// If the clear button is hit, reset the plot with the new values
	$("#reset_selection").click(function () {
		plot_obj = $.plot($("#theplot"), DATA, FLOT_OPTS);
		plot_obj.clearSelection();

		$('#report_table').html('<center><img src="img/ajax-loader.gif"></center>');
		$.ajax({
			url: table_url_now,
			method: 'GET',
			dataType: 'html',
			success: show_table_data
		});
		$("#selection").text('');
		$('#dimension-ts_min_start').val(initial_start_time);
		$('#dimension-ts_min_end').val(initial_end_time);
		$('#permalink_btn').attr('href', GRAPH_PERMALINK_URL + url_start_end_params);
	});

	// kick off the initial AJAX call to get the data to plot for the graph
	$.ajax({
		url: GRAPH_DATA_URL + url_start_end_params,
		method: 'GET',
		dataType: 'json',
		success: new_plot_data
	});

	// kick off the initial AJAX call to get the data for the table below the graph
	$.ajax({
		url: table_url_now,
		method: 'GET',
		dataType: 'html',
		success: show_table_data
	})
});
</script>
