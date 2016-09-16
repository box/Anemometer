<div class="row">
	<div class="span12">
	</div>
</div>

<?php
	$limit = ($rpp < $num_rows) ? $rpp : $num_rows;
?>

<div class="row">
	<div class="span4">
	Displaying samples <strong><?php echo $start+1; ?> - <?php echo $start+$limit; ?> </strong>
	<?php if (isset($start) and $start > 0) { ?> 
		<a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp={$rpp}&start=".($start - $rpp); ?>"class="btn">&larr; Newer</a>
	<?php } ?>
	
	  <?php if ($num_rows > $limit) { ?> 
		<a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp={$rpp}&start=".($start + $rpp); ?>" class="btn">Older &rarr;</a>
	  <?php } ?>
	</div>
</div>
<hr>

<?php for ($i=1; $i<= $limit; $i++) {
	$sample = $samples->fetch_assoc();
	?>
<div class="row">
	<div class="span12">
                <i class="icon-leaf"></i> <strong>Sample</strong> on db <strong> <?php echo $sample['db_max']; ?> </strong> @ host <strong><?php echo $sample['hostname_max']; ?></strong> at <strong><?php echo $sample['ts_max']; ?></strong>
		<pre class="prettyprint lang-sql"><?php echo htmlspecialchars($sample['sample']); ?></pre>
	</div>
</div>
<?php } ?>
<hr>
	<div class="row">
	<div class="span4">
	Displaying samples <strong><?php echo $start+1; ?> - <?php echo $start+$limit; ?> </strong>
	<?php if (isset($start) and $start > 0) { ?> 
		<a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp={$rpp}&start=".($start - $rpp); ?>"class="btn">&larr; Newer</a>
	<?php } ?>
	
	  <?php if ($num_rows > $limit) { ?> 
		<a href="<?php echo site_url()."?action=samples&datasource={$datasource}&checksum={$checksum}&rpp={$rpp}&start=".($start + $rpp); ?>" class="btn">Older &rarr;</a>
	  <?php } ?>
	</div>
</div>
	
<script>
	$(function() {
		prettyPrint();
	});
</script>
