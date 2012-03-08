

<div class="row">
    
    <div class="span8 offset2 well">
    
    <h2>Choose a Datasource</h2>
        
    <form action="<?php echo site_url() ."?action=report" ?>" method="post">
        <div class="span3">
            
            <div class="input-prepend">
                <span class="add-on"><i class="icon-folder-open"></i></span><select name="datasource">
                    <?php foreach ($datasources as $ds) { ?>
                        <option value="<?php echo $ds ?>"<?php if ($datasource == $ds) { echo " SELECTED "; } ?>><?php echo $ds ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="span2">
            <input type="submit" name="submit" value="Choose Datasource" class="btn-primary"/>
        </div>
    </form>
    </div>
</div>