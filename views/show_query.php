<p><center><h2><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum={$checksum}"; ?>">Query <?php echo $checksum; ?></a></h2></center></p>

<div class="row">
	<div class="span4 offset3"><strong>First Seen</strong>: <?php echo $row['first_seen']; ?></div>
	<div class="span4"><strong>Last Seen</strong>: <?php echo $row['last_seen']; ?></div>
</div>

<hr>

	<div class="row" style="padding-bottom: 10px">
		<div class="span12">
			<a href="javascript:void(0);" class="btn" data-toggle="collapse" data-target="#graph" id="graph-btn"><i class="icon-plus"></i> Show Graph Options</a><br/>
		</div>
	</div>

		<div class="collapse out" id="graph">

			<form action="<?php echo site_url() ?>" method="GET" class="form-inline">
				<input type="hidden" name="action" value="show_query">
				<input type="hidden" name="datasource" value="<?php echo $datasource; ?>">
				<input type="hidden" name="checksum" value="<?php echo $checksum; ?>">
				<input type="hidden" name="show_form" value="1">

				<div class="row">
					<div class="span3">
						From<br>
						<div class="input-append">
							<input type="text" class="span2" name="dimension-<?php echo $time_field_name ?>_start" id="dimension-ts_min_start" value="<?php echo get_var("dimension-{$time_field_name}_start"); ?>">
							<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
						</div>
					</div>

					<div class="span4">
						To<br>
						<div class="input-append">
							<input type="text" class="span2" name="dimension-<?php echo $time_field_name ?>_end" id="dimension-ts_min_end" value="<?php echo get_var("dimension-{$time_field_name}_end"); ?>">
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
						<select name="dimension-<?php echo $hostname_field_name ?>" class="span3 combobox">
							<option value=""></option>
							<?php foreach ($hosts as $h) { ?>
								<option value="<?php echo $h ?>" <?php if (get_var("dimension-{$hostname_field_name}")!=null AND get_var("dimension-{$hostname_field_name}") == $h ) { echo "SELECTED"; } ?>><?php echo $h ?></option>
							<?php } ?>
						</select>
						<input type="checkbox" name="<?php echo "dimension-pivot-{$hostname_field_name}" ?>" value='<?php echo $time_field_name ?>'<?php echo (isset($dimension_pivot_hostname_max) ? ' CHECKED ' : '') ?>> Show each host as a separate series
					</div>

					<div class="span4" >
						Checksum<br>
						<input name="fact-<?php echo $checksum_field_name ?>" class="span4 typeahead" value="<?php echo get_var('fact-'.$checksum_field_name) ?>">
					</div>

					<div class="span4">
						<input type="submit" class="btn-primary btn-large" name="submit" value="Search">
					</div>
				</div>

			</form>
		</div>



	<div class="row">
		<div id="theplot" class="span12" style="height: 300px;"></div>
	</div>
	<div class="row">
	</div>

	<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
	<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
	<script>

// url to retrieve JSON from
var dataurl = "<?php echo $ajax_request_url ?>"
var TIMEZONE_OFFSET = <?php echo $timezone_offset; ?> * 1000;

// Setup options for the plot
var thefreakingoptions = {
	series: {
		lines: { show: true },
		points: { show: true}
	},
	grid: { hoverable: true, clickable: true },
	legend: { noColumns: 2},
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" }
};

// Placeholder for data to plot
var thedamndata = [];

function newPlotData(data) {
	// flot requires milliseconds, so convert the timestamp from seconds to milliseconds
	new_data = new Array();
	for ( var i = 0; i < data.length; i++ )
	{
		var y_sum = 0; // to check for an empty series.
		for ( var j = 0; j < data[i].data.length; j++ )
		{
			data[i].data[j][0] = data[i].data[j][0] * 1000;
			data[i].data[j][0] = data[i].data[j][0] + (TIMEZONE_OFFSET);
			y_sum += parseFloat(data[i].data[j][1]);
		}
		//console.log("i="+i+"; ysum="+y_sum);
		if (y_sum == 0 && i > 0) // this series is empty; remove it.
		{
			delete data[i];
		} else {
			new_data.push(data[i]);
		}

	}
	//console.log(data);
	var theplot = $("#theplot"); // get the graph div
	plot_obj = $.plot(theplot, new_data, thefreakingoptions);
	//setup_selection(theplot);
}

function setupSelection(theplot) {
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
	console.log('the options are:');
	console.debug(thefreakingoptions);
}

function showTooltip(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 5,
            left: x + 5,
            padding: '2px',
			'border-radius': '4px',
			'background-color': 'black',
			color: 'white',
            opacity: 0.80
	}).appendTo("body").fadeIn(200);
}

var previousPoint = null;
$("#theplot").bind("plothover", function (event, pos, item) {
	/*
	$("#x").text(pos.x.toFixed(2));
	$("#y").text(pos.y.toFixed(2));
	*/

	if (item) {
		if (previousPoint != item.dataIndex) {
			previousPoint = item.dataIndex;

			$("#tooltip").remove();
			var x = item.datapoint[0].toFixed(2),
				y = item.datapoint[1].toFixed(2);

			var theDate = new Date(x-TIMEZONE_OFFSET);
			showTooltip(item.pageX, item.pageY,
						item.series.label + "<br/>\n" + theDate + " = " + y);
		}
	}
	else {
		$("#tooltip").remove();
		previousPoint = null;
	}
});

$(document).ready( function ()  {
	// div to plot within
	var theplot = $("#theplot");

	// Create the plot!
	var the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);
	/*
	// If the clear button is hit, reset the plot with the new values
	$("#clear_selection").click(function () {
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);
		the_freaking_plot_with_freaking_lasers_on_its_freaking_head.clearSelection();
	});
	*/

	$.ajax({
		url: dataurl,
		method: 'GET',
		dataType: 'json',
		success: newPlotData
	});

	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	$("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });


});

</script>

	<div class="row">
		<div class="span12">
			<strong>Fingerprint</strong><br>
			<pre class="prettyprint lang-sql"><?php echo $row['fingerprint']; ?></pre>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="span12">
			<!-- <div class="alert alert-info"> -->
			<?php if ($show_samples) { ?>

				<table width="100%">
				<tr>
					<td>
					 	<strong>Last Sample</strong> on db <strong><?php echo $sample['db_max']; ?></strong> @ host <strong><?php echo $sample[$hostname_field_name]; ?></strong> at <strong><?php echo $sample[$time_field_name]; ?></strong>
					</td>
					<td>
						<!-- dropdown button for more samples with counts -->
						<div class="btn-group">
							<a class="btn" href="javascript:void(0);">More Samples</a>
							<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
							  <span class="caret"></span>
							</a>
							<ul class="dropdown-menu">
							  <li><a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp=20"; ?>">20</a></li>
							  <li><a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp=50"; ?>">50</a></li>
							  <li><a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp=100"; ?>">100</a></li>
							</ul>
						</div>
					</td>
				</tr>
				</table>
				<pre class="prettyprint lang-sql"><?php echo htmlspecialchars($sample[$sample_field_name]); ?></pre>
				<?php } ?>

		<?php if (isset($explain_plan_error)) { ?>
			<div class="alert"><strong>Error in Query Explain Plugin:</strong> <?php echo $explain_plan_error; ?></div>
		<?php } ?>
		<div class="accordion" id="accordion2">
			<?php if (isset($explain_plan)) { ?>
			<div class="accordion-group">
              <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseZero">
                  Explain Plan
                </a>
              </div>
              <div id="collapseZero" class="accordion-body collapse out">
                <div class="accordion-inner">
					<pre class="prettyprint lang-sql nowrap"><?php echo $explain_plan; ?></pre>
                </div>
              </div>
			  <?php } ?>

		<?php if (isset($visual_explain) and $visual_explain != '') { ?>
            <div class="accordion-group">
              <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                  Visual Explain Plan
                </a>
              </div>
              <div id="collapseOne" class="accordion-body collapse out">
                <div class="accordion-inner">
					<pre><?php echo $visual_explain; ?></pre>
                </div>
              </div>
            </div>
		<?php } ?>

		<?php if (isset($query_advisor) and $query_advisor != '') { ?>
            <div class="accordion-group">
              <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
                  Query Advisor
                </a>
              </div>
              <div id="collapseTwo" class="accordion-body collapse">
                <div class="accordion-inner">
                  <pre><?php echo $query_advisor; ?></pre>
                </div>
              </div>
            </div>
		<?php } ?>

		<?php if (isset($create_table) and $create_table != '') { ?>
            <div class="accordion-group">
              <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseThree">
                  Create Table Statements
                </a>
              </div>
              <div id="collapseThree" class="accordion-body collapse">
                <div class="accordion-inner">
                  <pre class="prettyprint lang-sql"><?php echo $create_table; ?></pre>
                </div>
              </div>
            </div>
		<?php } ?>

		<?php if (isset($table_status) and $table_status != '') { ?>
			<div class="accordion-group">
              <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseFour">
                  Table Status
                </a>
              </div>
              <div id="collapseFour" class="accordion-body collapse">
                <div class="accordion-inner">
                  <pre><?php echo $table_status; ?></pre>
                </div>
              </div>
            </div>

		<?php } ?>
          </div>
			<!-- </div> -->
		</div>
	</div>

<form action="<?php echo site_url() ."?action=upd_query&datasource={$datasource}&checksum={$checksum}"; ?>" method="POST" >
	<div class="row">
		<div class="span12">
			<?php if ($row['reviewed_by'] != '') { ?>
				<strong>Reviewed</strong> by <strong><?php echo $row['reviewed_by']?></strong> on <strong><?php echo $row['reviewed_on'] ?></strong> with status <strong><?php echo $row['reviewed_status']; ?></strong><br>
			<?php } else { ?>
				 <!-- <a class="btn" href="javascript:void(0);" data-toggle="collapse" data-target="#new_review" ><i class="icon-plus"></i> Add Review </a> <strong>Not reviewed</strong> -->
				<!-- <span class="label label-info">Not Reviewed</span> -->
			<?php } ?>
			<div id="new_review" class="collapse in">
					<i class="icon-comment"></i> <strong>comments</strong><br>
					<textarea name="comments" class="span12" rows="16"><?php echo $row['comments']; ?></textarea>

					<div class="row">
						<div class="span12">
					<strong>Status: </strong><br><select name="reviewed_status" class="combobox">
						<?php foreach ($review_types as $rt) { ?>
							<option value="<?php echo $rt ?>" <?php if ($row['reviewed_status'] == $rt) { echo ' SELECTED '; } ?>><?php echo $rt ?></option>
						<?php } ?>
					</select><br>
					<strong>Reviewed By: </strong><br><select name="reviewed_by" class="combobox">
						<?php foreach ($reviewers as $r) { ?>
							<option value="<?php echo $r ?>" <?php if (isset($current_auth_user) AND $r==$current_auth_user) { echo ' SELECTED '; } ?>><?php echo $r ?></option>
						<?php } ?>
					</select><br>

						<input type="submit" name="submit" value="Review and Update Comments" class="btn btn-primary"/>
						<!--
						<input type="submit" name="submit" value="Review" class="btn btn-primary"/>
						-->
						<input type="submit" name="submit" value="Update Comments" class="btn btn-primary"/>
						<input type="submit" name="submit" value="Clear Review" class="btn btn-primary"/>
					</div>
					</div>
				</div>


		</div>
	</div>
</form>

<p><center><h2>90 Day History</h2></center>

<script>
	$(function() {
	//	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	// $("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
		$('.combobox').combobox();
		prettyPrint();

		$('#graph').on('hidden', function () {
			el = document.getElementById('graph-btn');
			el.innerHTML = '<i class="icon-plus"></i> Show Graph Options'
		});
		$('#graph').on('show', function () {
		  el = document.getElementById('graph-btn');
		  el.innerHTML = '<i class="icon-minus"></i> Hide Graph Options'
		});

		show_graph = <?php echo get_var('show_form') ? 'true' : 'false' ?>;
		if (show_graph) {
		  $("#graph-btn").trigger("click");
		}
	});
</script>
