<div>
	<div id="theplot" style="width:600px; height: 300px;"></div>
	<p>You selected: <span id="selection"></span></p>
	<p><input id="clear_selection" type="button" value="Clear selection" /></p>
</div>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
$( function ()  {
	// url to retrieve JSON from
	var dataurl = "http://dba1001.ve.box.net:90/weatherstation/index.php?action=api&datasource=Live&dimension-ts_min_start=2012-03-06+21%3A16%3A00&dimension-ts_min_end=2012-03-07+21%3A16%3A00&fact-first_seen=&table_fields%5B%5D=hour_ts&table_fields%5B%5D=Query_time_sum&dimension-hostname_max=&fact-group=hour_ts&fact-order=hour_ts&fact-having=&fact-limit=999&submit=Search&fact-where=&fact-sample=&fact-checksum=&output=json&noheader=1"

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

	function newPlotData(data) {
		console.debug(data);
		var xcol = data['columns'][0];
		var ohmyfuckinggodwillthisshitwork = [];
		var poormansdataholder = {}
		for ( var i = 1; i < data['columns'].length; i++ ) {
			ohmyfuckinggodwillthisshitwork.push({label: data['columns'][i], data: [] });
			poormansdataholder[data['columns'][i]] = [];
		}
		console.debug(ohmyfuckinggodwillthisshitwork);
		for ( var i = 0; i < data['result'].length; i++) {
			//console.debug([ data['result'][i][xcol], data['result'][i][ohmyfuckinggodwillthisshitwork[0]['label']]]);
			for ( var j = 0; j < ohmyfuckinggodwillthisshitwork.length; j++ ) {
				var point = [ data['result'][i][xcol], data['result'][i][ohmyfuckinggodwillthisshitwork[j]['label']]];
				//ohmyfuckinggodwillthisshitwork[j]['data'].push(point);
			}
		}
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, ohmyfuckinggodwillthisshitwork, thefreakingoptions);
	}

	$.ajax({
		url: dataurl,
		method: 'GET',
		dataType: 'json',
		success: newPlotData
	});
});
</script>

