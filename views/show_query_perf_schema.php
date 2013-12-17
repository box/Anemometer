<p><center><h2><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum={$checksum}"; ?>">Query <?php echo $checksum; ?></a></h2></center></p>

<div class="row">
	<div class="span4 offset3"><strong>First Seen</strong>: <?php echo ( isset($row['FIRST_SEEN']) ? $row['FIRST_SEEN'] : $row['first_seen']); ?></div>
	<div class="span4"><strong>Last Seen</strong>: <?php echo (isset($row['LAST_SEEN']) ? $row['LAST_SEEN'] : $row['last_seen']); ?></div>
</div>

<hr>
      
	<div class="row">
		<div class="span12">
			<strong>Fingerprint</strong><br>
			<pre class="prettyprint lang-sql"><?php echo $row[$fingerprint_field_name]; ?></pre>
		</div>
	</div>
	<hr>    
    
        
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

<p><center><h2>Current Statistics</h2></center>

<script>
	$(function() {
	//	$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
	// $("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
		$('.combobox').combobox();
		prettyPrint();

        <?php if ($source_type != 'performance_schema') { ?>
        
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
        <?php } ?>
	});
</script>
