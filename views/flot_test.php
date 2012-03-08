<a href="#" class="btn" data-toggle="collapse" data-target="#raw" id="raw-btn"><i class="icon-plus"></i> Show Raw SQL</a>
<a href="#" class="btn"><i class="icon-list-alt"></i> Show Table</a>
<a href="#" class="btn"><i class="icon-picture"></i> Show Graph</a>
<a href="<?php echo $permalink ?>" class="btn"><i class="icon-magnet"></i> Permalink</a>

<div class="collapse out" id="raw">
	  <pre class="prettyprint lang-sql"><?php echo $sql ?></pre>
</div>

</div>
</div>

<div style="margin: 20px">
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr>
				<?php foreach ($columns as $c ) { ?>
					<th><?php echo $c ?></th>
				<?php } ?>
			</tr>
		</thead>
		<?php foreach ($result as $row)  { ?>
			<tr>
				<?php foreach ($columns as $c ) { ?>
					<?php if ($c == 'checksum') { ?>
						<td><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum=".$row[$c]; ?>"><?php echo $row[$c]; ?></a></td>
					<?php }  else { ?>
						<td><?php echo $row[$c]; ?></td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>
	</table>

	<div id="theplot" style="width:600px; height: 300px;"></div>
	<p>You selected: <span id="selection"></span></p>
	<p><input id="clear_selection" type="button" value="Clear selection" /></p>
</div>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>
$( function ()  {
	$('#raw').on('hidden', function () {
	  el = document.getElementById('raw-btn');
	  el.innerHTML = '<i class="icon-plus"></i> Show Raw SQL'
	});
	$('#raw').on('show', function () {
	  el = document.getElementById('raw-btn');
	  el.innerHTML = '<i class="icon-minus"></i> Hide Raw SQL'
	});

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
		<?php echo "['" . preg_replace('/(\d{4})-(\d{2})-(\d{2})/', '\1\2\3', $row[get_var('fact-group')]) . "', " . $row['Query_time_sum'] . "],
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

