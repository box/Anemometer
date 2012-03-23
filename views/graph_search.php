<script>
	$(function() {
	});
</script>

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
	<p><a id="clear_selection" value="Clear selection" class="btn" href="#"><i class="icon-fire"></i> Reset Selection</a>
		<a class="btn" href="<?php echo $graph_permalink; ?>"><i class="icon-magnet"></i> Graph Permalink</a></p>
</div>
<hr>
	</div></div>
<span id="report_table"><center><img src="img/ajax-loader.gif"></center></div>

<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
// url to retrieve JSON from
var graph_data_url = "<?php echo $ajax_request_url ?>";
var table_base_url = "<?php echo $ajax_table_request_url_base ?>"
var table_url_time_start_param = "<?php echo $table_url_time_start_param ?>"
var table_url_time_end_param = "<?php echo $table_url_time_end_param ?>"

// Setup options for the plot
var flot_opts = {
	series: {
		lines: { show: true },
		points: { show: true},
	},
	legend: { noColumns: 2 },
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" },
};

// Placeholder for data to plot
var thedamndata = [];


// Callback function for drawing the graph after data is retrieved from an AJAX call
function newPlotData(data) {
	// convert the timestamp from seconds to milliseconds
	for ( var i = 0; i < data.length; i++ )
	{
		for ( var j = 0; j < data[i].data.length; j++ )
		{
			data[i].data[j][0] = data[i].data[j][0] * 1000;
			data[i].data[j][0] = data[i].data[j][0] - (60*60*7*1000);
		}
	}
	var theplot = $("#theplot");
	thedamndata = data;
	the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, flot_opts);
	setupSelection(theplot);
}

function leftPadThisThingYo(padThis, padding, amount)
{
	s = String(padThis);
	returnStr = '';
	if(s.length < amount)
	{
		for ( var i = 1; i < amount; i++)
		{
			returnStr += padding;
		}
		returnStr += padThis;
	}
	else
	{
		returnStr += padThis;
	}
	return returnStr;
}

function getMeAGoodDamnDate(d)
{
	thedate = d.getFullYear();
	thedate += '-';
	thedate += leftPadThisThingYo(d.getMonth()+1, '0', 2);
	thedate += '-';
	thedate += leftPadThisThingYo(d.getDate(), '0', 2);
	thedate += ' ';
	thedate += leftPadThisThingYo(d.getHours(), '0', 2);
	thedate += ':';
	thedate += leftPadThisThingYo(d.getMinutes(), '0', 2);
	thedate += ':';
	thedate += leftPadThisThingYo(d.getSeconds(), '0', 2);
	return thedate;
}

function setupSelection(theplot) {
	// Add event handlers to the plot div that allow interactive selection of data
	theplot.bind("plotselected", function (event, ranges) {
		//$("#selection").text(ranges.xaxis.from.toFixed(1) + " to " + ranges.xaxis.to.toFixed(1));
		plot = $.plot(theplot, thedamndata, $.extend ( true, {}, flot_opts, {
			xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
		}));

		d = new Date();
		d.setTime(Math.floor(ranges.xaxis.from + (60*60*7*1000)));
		//startTime = d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate() + ' ' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
		startTime = getMeAGoodDamnDate(d);
		d.setTime(Math.floor(ranges.xaxis.to + (60*60*7*1000)));
		//endTime = d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate() + ' ' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
		endTime = getMeAGoodDamnDate(d);
		var new_table_data_url = table_base_url + '&' + escape(table_url_time_start_param) + '=' + escape(startTime) + '&' + escape(table_url_time_end_param)  + '=' + escape(endTime);
		$('#report_table').html('<center><img src="img/ajax-loader.gif"></center>');
		$.ajax({
			url: new_table_data_url,
			method: 'GET',
			dataType: 'html',
			success: showTableData
		});
		$("#selection").text(startTime + " to " + endTime);
	});
	theplot.bind("plotunselected", function (event) {
		$("#selection").text("");
	});
}

function showTableData(data) {
	tableDiv = document.getElementById('report_table');
	tableDiv.innerHTML = data;
	prettyPrint();
}



$(document).ready( function ()  {
	// Setup the search widget stuff!
	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$('.combobox').combobox();
	prettyPrint();

	// div to plot within
	var theplot = $("#theplot");

	// Create the plot!
	var the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, flot_opts);

	// If the clear button is hit, reset the plot with the new values
	$("#clear_selection").click(function () {
		//the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot($("#theplot"), thedamndata, flot_opts);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot($("#theplot"), thedamndata, flot_opts);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head.clearSelection();
		var new_table_url_now = table_base_url + '&' + escape(table_url_time_start_param) + '=' + escape($('#dimension-ts_min_start').val()) + '&' + escape(table_url_time_end_param)  + '=' + escape($('#dimension-ts_min_end').val());
		$('#report_table').html('<center><img src="img/ajax-loader.gif"></center>');
		$.ajax({
			url: new_table_url_now,
			method: 'GET',
			dataType: 'html',
			success: showTableData
		});
		$("#selection").text('');
	});


	$.ajax({
		url: graph_data_url,
		method: 'GET',
		dataType: 'json',
		success: newPlotData
	});

	var table_url_now = table_base_url + '&' + escape(table_url_time_start_param) + '=' + escape($('#dimension-ts_min_start').val()) + '&' + escape(table_url_time_end_param)  + '=' + escape($('#dimension-ts_min_end').val());
	$.ajax({
		url: table_url_now,
		method: 'GET',
		dataType: 'html',
		success: showTableData
	})

});

</script>

