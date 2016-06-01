<div class="navbar navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand" href="<?php echo site_url(); ?>"><img src="img/anemometer.png"> Box Anemometer</a>
      <div class="nav-collapse">
        <ul class="nav">
           <li class="divider-vertical"></li>
          
          
          <?php if (is_array($datasources)) { ?>
            <li class="dropdown">
                <a href="#"
                      class="dropdown-toggle"
                      data-toggle="dropdown">
                        Datasources
                      <b class="caret"></b>
                      
                </a>
                <ul class="dropdown-menu scrollable-menu">
                  <?php foreach ($datasources as $ds) { ?> 
                    <li><a href="<?php echo site_url().'?action=report&datasource='.$ds; ?>"><?php echo $ds; ?></a></li>
                  <?php } ?>
                </ul>
            </li>
            <?php } ?>
            
            <?php if (isset($datasource)) { ?> 
              <li><a href="<?php echo site_url(). "?action=report&datasource={$datasource}"; ?>"><?php echo $datasource ?></a></li>
              <li class="divider-vertical"></li>
              <?php if ($source_type != 'performance_schema')  { ?>
                <li><a href="<?php echo site_url().'?action=graph_search&datasource='.$datasource; ?>"><i class="icon-picture icon-white" style="width: 32px"></i> Graph Search</a></li>
                <li><a href="<?php echo site_url().'?action=report&datasource='.$datasource; ?>"><i class="icon-list-alt icon-white" style="width: 16px"></i> Table Search</a></li>
              <?php } ?>
            <?php } ?>
        </ul>
      </div><!--/.nav-collapse -->  
        <?php if (isset($datasource) and $source_type != 'performance_schema') { ?> 
        <form class="form-search form-inline pull-right" id="quicksearch" action="<?php echo site_url()."?action=quicksearch&datasource={$datasource}"; ?>" method="post">
          <input type="text" class="input-medium" name="checksum" placeholder="checksum">
            <a class="btn" href="javascript:document.getElementById('quicksearch').submit()"><i class="icon-search"></i> Find Query</a>
        </form>
        <?php } ?>
        
    </div>
  </div>
</div>

<?php
/*
<?php foreach( $path as $p ) { ?> 
  <li><a href="<?php echo $url ?>"><?php echo $p ?></a></li>
<?php } ?>
*/
?>