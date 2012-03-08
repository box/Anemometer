<script>
	$(function() {
	});
</script>

<div class="row">
	<form action="<?php echo site_url()."?action=report"; ?>" method="GET" class="form-inline">
	<input type="hidden" name="action" value="report">
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

	<div>
		<div id="theplot" style="width:600px; height: 300px;"></div>
		<p>You selected: <span id="selection"></span></p>
		<p><input id="clear_selection" type="button" value="Clear selection" /></p>
	</div>

</div>


<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
//var dataurl = "http://dba1001.ve.box.net:90/weatherstation/index.php?action=api&datasource=Live&dimension-ts_min_start=2012-03-06+21%3A16%3A00&dimension-ts_min_end=2012-03-07+21%3A16%3A00&fact-first_seen=&table_fields%5B%5D=hour_ts&table_fields%5B%5D=Query_time_sum&dimension-hostname_max=&fact-group=hour_ts&fact-order=hour_ts&fact-having=&fact-limit=999&submit=Search&fact-where=&fact-sample=&fact-checksum=&output=json2&noheader=1"
//var dataurl = "http://dba1001.ve.box.net:90/weatherstation/index.php?action=api&datasource=Live&dimension-ts_min_start=2012-03-06+21%3A16%3A00&dimension-ts_min_end=2012-03-07+21%3A16%3A00&fact-first_seen=&table_fields%5B%5D=hour_ts&table_fields%5B%5D=Query_time_sum&table_fields%5B%5D=ts_cnt&dimension-hostname_max=&fact-group=hour_ts&fact-order=hour_ts&fact-having=&fact-limit=999&submit=Search&fact-where=&fact-sample=&fact-checksum=&output=json2&noheader=1"
// url to retrieve JSON from
var dataurl = "<?php echo $ajax_request_url ?>"

// Setup options for the plot
var thefreakingoptions = {
	series: {
		lines: { show: true },
		points: { show: true},
	},
	legend: { noColumns: 2},
	xaxis: { tickDecimals: 0 , tickSize: 1 },
	yaxis: { min: 0 },
	selection: { mode: "x" },
};

// Placeholder for data to plot
var thedamndata = [];

$(document).ready( function ()  {
	// Setup the search widget stuff!
	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$('.combobox').combobox();
	prettyPrint();

	// div to plot within
	var theplot = $("#theplot");

	// Add event handlers to the plot div that allow interactive selection of data
	theplot.bind("plotselected", function (event, ranges) {
		$("#selection").text(ranges.xaxis.from.toFixed(1) + " to " + ranges.xaxis.to.toFixed(1));
		plot = $.plot(theplot, thedamndata, $.extend ( true, {}, thefreakingoptions, {
			xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
		}));
	});
	theplot.bind("plotunselected", function (event) {
		$("#selection").text("");
	});

	// Create the plot!
	var the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);

	// If the clear button is hit, reset the plot with the new values
	$("#clear_selection").click(function () {
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head.clearSelection();
	});


	// Callback function for drawing the graph after data is retrieved from an AJAX call
	function newPlotData(data) {
		console.debug(data);
		//the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, data, thefreakingoptions);
		//the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot($("#theplot"), data, thefreakingoptions);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot($("#theplot"), data);
		console.debug(the_freaking_plot_with_freaking_lasers_on_its_freaking_head);
	}

	$.ajax({
		url: dataurl,
		method: 'GET',
		dataType: 'json',
		success: newPlotData
	});
});
</script>

