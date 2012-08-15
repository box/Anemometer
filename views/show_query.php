<p><center><h2><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum={$checksum}"; ?>">Query <?php echo $checksum; ?></a></h2></center></p>

<form action="<?php echo site_url() ."?action=upd_query&datasource={$datasource}&checksum={$checksum}"; ?>" method="POST" >
	<div class="row">
		 
		<div class="span4 offset3"><strong>First Seen</strong>: <?php echo $row['first_seen']; ?></div>
		<div class="span4"><strong>Last Seen</strong>: <?php echo $row['last_seen']; ?></div>
	</div>
		<hr>
			<div class="row">
		<div id="theplot" class="span12" style="height: 300px;"></div>
</div>
<div class="row">
	<!-- <p>You selected: <span id="selection"></span></p>
	<p><a id="clear_selection" value="Clear selection" class="btn"/ href="#"><i class="icon-fire"></i> Reset Selection</a></p> -->
</div>

			<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
<script>

// url to retrieve JSON from
var dataurl = "<?php echo $ajax_request_url ?>"

// Setup options for the plot
var thefreakingoptions = {
	series: {
		lines: { show: true },
		points: { show: true},
	},
	legend: { noColumns: 2},
	xaxis: { tickDecimals: 0, mode: "time" },
	yaxis: { min: 0 },
	selection: { mode: "x" },
};

// Placeholder for data to plot
var thedamndata = [];


// Callback function for drawing the graph after data is retrieved from an AJAX call
function newPlotData(data) {
	console.debug(data);
	for ( var i = 0; i < data.length; i++ )
	{
		for ( var j = 0; j < data[i].data.length; j++ )
		{
			data[i].data[j][0] = data[i].data[j][0] * 1000;
		}
	}
	var theplot = $("#theplot");
	thedamndata = data;
	the_freaking_plot_with_freaking_lasers_on_its_freaking_head = $.plot(theplot, thedamndata, thefreakingoptions);
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
						<strong>Last Sample</strong> on host <strong><?php echo $sample[$hostname_field_name]; ?></strong> at <strong><?php echo $sample[$time_field_name]; ?></strong>
					</td>
					<td>
						<!-- dropdown button for more samples with counts -->
						<div class="btn-group">
							<a class="btn" href="#">More Samples</a>
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
				<pre class="prettyprint lang-sql"><?php echo $sample[$sample_field_name]; ?></pre>
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
		
	
	<div class="row">
		<div class="span12">
			<?php if ($row['reviewed_by'] != '') { ?>
				<strong>Reviewed</strong> by <strong><?php echo $row['reviewed_by']?></strong> on <strong><?php echo $row['reviewed_on'] ?></strong> with status <strong><?php echo $row['reviewed_status']; ?></strong><br>
			<?php } else { ?>
				 <!-- <a class="btn" href="#" data-toggle="collapse" data-target="#new_review" ><i class="icon-plus"></i> Add Review </a> <strong>Not reviewed</strong> -->
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
	});
</script>
