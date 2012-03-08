<div>
	<div id="theplot" style="width:600px; height: 300px;"></div>
	<p>You selected: <span id="selection"></span></p>
	<p><input id="clear_selection" type="button" value="Clear selection" /></p>
</div>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
$( function ()  {
	var thefreakingoptions = {
		series: {
			lines: { show: true },
			points: { show: true},
		},
		legend: { noColumns: 2},
		xaxis: { tickDecimals: 0 },
		yaxis: { min: 0 },
		selection: { mode: "x" },
	};

	thedamndata = [
		{
			label: "test",
			data: [
	<?php foreach ($result as $row) { ?>
		<?php echo "['" . strftime('%Y%m%d%H%M', $row[get_var('fact-group')]) . "', " . $row['Query_time_sum'] . "],
" ?>
	<? } ?>
			]
		},
	];

	theplot = $("#theplot");
	theplot.bind("plotselected", function (event, ranges) {
		$("#selection").text(ranges.xaxis.from.toFixed(1) + " to " + ranges.xaxis.to.toFixed(1));
		plot = $.plot(theplot, thedamndata, $.extend ( true, {}, thefreakingoptions, {
			xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
		}));
	});
	theplot.bind("plotunselected", function (event) {
		$("#selection").text("");
	});

	var the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);

	$("#clear_selection").click(function () {
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head.clearSelection();
	});
});
</script>

