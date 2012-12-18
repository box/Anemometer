<script>
	$(function() {
		dbFields = <?php echo json_encode(flatten_array($table_fields)); ?>;
		$("#dimension-ts_min_start").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
		$("#dimension-ts_min_end").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
		$("#fact-first_seen").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });
		$(".typeahead").typeahead({ source: dbFields, items: 8});
		$('.combobox').combobox();
		prettyPrint();
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
				<input type="text" class="span2" name="dimension-<?php echo $time_field_name ?>_start" id="dimension-ts_min_start" value="<?php echo get_var('dimension-'.$time_field_name.'_start'); ?>">
				<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
			</div>
		</div>
		
    

		<div class="span4">
			To<br>
			<div class="input-append">
				<input type="text" class="span2" name="dimension-<?php echo $time_field_name ?>_end" id="dimension-ts_min_end" value="<?php echo get_var('dimension-'.$time_field_name.'_end'); ?>">
				<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
			</div>
		</div>

	<div class="span4">
		Query first seen since<br>
		<div class="input-append">
			<input type="text" class="span2" name="fact-first_seen" id="fact-first_seen" value="<?php echo get_var('fact-first_seen'); ?>">
			<span class="add-on"><i class="icon-calendar" id="dp"></i></span>
		</div>
	</div>
</div>
	<hr>
<div class="row">
	<div class="span3">
			Table Fields<br>
		<select name="table_fields[]" class="span3" size="20" multiple="true">
			<optgroup label="Custom Fields">
			<?php foreach ($custom_fields as $f)  { ?>
				<option value="<?php echo $f ?>" <?php if (isset($table_fields_selected) and in_array($f, $table_fields_selected )) { echo "SELECTED"; } ?>><?php echo $f ?></option>
			<?php } ?>
			</optgroup>
			
			<?php foreach (array_keys($table_fields) as $table)  { ?>
				<optgroup label="<?php echo $table; ?>">
					<?php foreach ($table_fields[$table] as $f)  { ?>
						<?php $field_name = $table_aliases[$table].".{$f}"; ?>
						<?php $is_selected = false; ?>
						<?php if (isset($table_fields_selected)
								  and in_array($field_name, $table_fields_selected )
								  or (!in_array($f, $exception_select_fields) and in_array($f, $table_fields_selected))
								  )
								  {
									$is_selected = true;
								  }
						?>
						<option value="<?php echo $field_name; ?>" <?php if ($is_selected) { echo "SELECTED"; } ?>><?php echo $f ?></option>
					<?php } ?>
				</optgroup>
			<?php } ?>
		</select>
	</div>
	
	<div class="span4">
		Filter By Host<br>
		<select name="dimension-<?php echo $hostname_field_name; ?>" class="span3 combobox">
			<option value=""></option>
			<?php foreach ($hosts as $h) { ?>
				<option value="<?php echo $h ?>" <?php if (get_var('dimension-'.$hostname_field_name) == $h ) { echo "SELECTED"; } ?>><?php echo $h ?></option>
			<?php } ?>
		</select><br>
		
		Group By<br>
		<input name="fact-group" class="span4 typeahead" value="<?php echo get_var('fact-group') ?>" data-provide="typeahead"><br><br>
		Order By<br>
		<input name="fact-order" class="span4 typeahead" value="<?php echo get_var('fact-order') ?>"><br><br>
		Having<br>
		<input name="fact-having" class="span4 typeahead" value="<?php echo get_var('fact-having') ?>"><br><br>
		Limit<br>
		<input name="fact-limit" class="span1" value="<?php echo get_var('fact-limit') ?>"><br><br>
		<center>
			<input type="submit" class="btn-primary btn-large" name="submit" value="Search">
		</center>
		
	</div>
	
	<div class="span4">
		<!--
		Extra Fields<br>
		<textarea name="extra_fields" class="span4" rows="6"><?php echo get_var('extra_fields') ?></textarea><br><br>
		-->
		
		Where<br>
		<textarea name="fact-where" class="span4" rows="6"><?php echo get_var('fact-where') ?></textarea><br><br>
		Query Sample Contains<br>
		<input name="fact-<?php echo $sample_field_name ?>" class="span4" value="<?php echo get_var('fact-'.$sample_field_name) ?>"><br><br>
		Reviewed Status<br>
		<select class="span4 combobox" name="fact-reviewed_status">
			<option value=""></option>
		<?php foreach ($review_types as $rt) { ?>
				<option value="<?php echo $rt ?>" <?php if (get_var('fact-reviewed_status') == $rt) { echo ' SELECTED '; } ?>><?php echo $rt ?></option>
		<?php } ?>
		</select><br>
		
		Checksum<br>
		<input name="fact-<?php echo $checksum_field_name ?>" class="span4 typeahead" value="<?php echo get_var('fact-'.$checksum_field_name) ?>"><br><br>
		
	</div>
	
</div>
<hr>
