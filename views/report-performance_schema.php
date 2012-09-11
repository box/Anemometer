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
						<option value="<?php echo $f ?>" <?php if (isset($table_fields_selected) and in_array($f, $table_fields_selected )) { echo "SELECTED"; } ?>><?php echo $f ?></option>
					<?php } ?>
				</optgroup>
			<?php } ?>
		</select>
	</div>
	
	
	<div class="span4">
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
		<input name="fact-DIGEST_TEXT" class="span4" value="<?php echo get_var('fact-DIGEST_TEXT') ?>"><br><br>
		
		Digest<br>
		<input name="fact-DIGEST" class="span4" value="<?php echo get_var('fact-DIGEST') ?>"><br><br>
		
	</div>
	
</div>
<hr>
