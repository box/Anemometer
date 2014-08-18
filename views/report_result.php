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
});
</script>
<div class="row">
	<div class="span12">
		<a href="javascript:void(0);" class="btn" data-toggle="collapse" data-target="#raw" id="raw-btn"><i class="icon-plus"></i> Show Raw SQL</a>
		<!--
		<a href="javascript:void(0);" class="btn"><i class="icon-list-alt"></i> Show Table</a>
		<a href="javascript:void(0);" class="btn"><i class="icon-picture"></i> Show Graph</a>
		-->

		<a href="<?php echo $permalink ?>" class="btn"><i class="icon-magnet"></i> Permalink</a>
		<a href="<?php echo $jsonlink ?>" class="btn"><i class="icon-cog"></i> JSON</a>

		<div class="collapse out" id="raw">
			  <pre class="prettyprint lang-sql"><?php echo $sql ?></pre>
		</div>
    </div>

</div>

</div>
</div>

<div style="margin: 20px">
    Showing <b><?php echo count($result); ?></b> results
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr>
				<?php foreach ($columns as $c ) { ?>
					<th><?php echo $c ?></th>
				<?php } ?>
			</tr>
		</thead>
		<?php foreach ($result as $row)  { ?>
			<?php
				$row_class = array();
				if (isset($callbacks)) {
					foreach ($callbacks as $fxname => $fx) {
						if (array_key_exists($fxname, $row)) {
							$result = $fx($row[$fxname]);
							$row[$fxname] = $result[0];
							$row_class[] = $result[1];
						}
					}
				}
			?>
			<tr class="<?php echo join(" ", $row_class); ?>">
				<?php foreach ($columns as $c ) { ?>
					<?php if ($c == 'checksum') { ?>
						<td><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum=".$row[$c]; ?>"><?php echo $row[$c]; ?></a></td>
					<?php }  else if ($c == 'DIGEST') { ?>
							<td><a href="<?php echo site_url()."?action=show_query&datasource={$datasource}&checksum=".$row[$c]; ?>"><?php echo $row[$c]; ?></a></td>
					<?php } else { ?>
						<td><?php echo $row[$c]; ?></td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>
	</table>
</div>
