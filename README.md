Box Anemometer
--------------

This is the Box Anemometer, the MySQL Slow Query Monitor.  This tool is used to analyze slow query logs collected from MySQL instances to identify problematic queries.

### Quickstart ###

If you're just completely itching to start using this tool, here's what you need:
*	a MySQL database to store query analysis data in.
*	[pt-query-digest](http://www.percona.com/doc/percona-toolkit/pt-query-digest.html).
	*	You may as well just get the whole [Percona Toolkit](http://www.percona.com/doc/percona-toolkit) while you're at it :)
*	a slow query log from a MySQL server (see [The Slow Query Log](http://dev.mysql.com/doc/refman/5.5/en/slow-query-log.html) for info on getting one)
*	a webserver with PHP


#### Setup DB ####

First up, you should connect to the MySQL database you're looking to store the analysis data in and issue the following statements:

    $ mysql -h db.example.com -e "
    -- Create the database needed for the Box Anemometer
    CREATE DATABASE slow_query_log;
    
    -- Create the global query review table
    CREATE TABLE `global_query_review` (
      `checksum` bigint(20) unsigned NOT NULL,
      `fingerprint` text NOT NULL,
      `sample` text NOT NULL,
      `first_seen` datetime DEFAULT NULL,
      `last_seen` datetime DEFAULT NULL,
      `reviewed_by` varchar(20) DEFAULT NULL,
      `reviewed_on` datetime DEFAULT NULL,
      `comments` text,
      PRIMARY KEY (`checksum`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    -- Create the historical query review table
    CREATE TABLE `global_query_review_history` (
      `checksum` bigint(20) unsigned NOT NULL,
      `sample` text NOT NULL,
      `ts_min` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      `ts_max` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      `ts_cnt` float DEFAULT NULL,
      `Query_time_sum` float DEFAULT NULL,
      `Query_time_min` float DEFAULT NULL,
      `Query_time_max` float DEFAULT NULL,
      `Query_time_pct_95` float DEFAULT NULL,
      `Query_time_stddev` float DEFAULT NULL,
      `Query_time_median` float DEFAULT NULL,
      `Lock_time_sum` float DEFAULT NULL,
      `Lock_time_min` float DEFAULT NULL,
      `Lock_time_max` float DEFAULT NULL,
      `Lock_time_pct_95` float DEFAULT NULL,
      `Lock_time_stddev` float DEFAULT NULL,
      `Lock_time_median` float DEFAULT NULL,
      `Rows_sent_sum` float DEFAULT NULL,
      `Rows_sent_min` float DEFAULT NULL,
      `Rows_sent_max` float DEFAULT NULL,
      `Rows_sent_pct_95` float DEFAULT NULL,
      `Rows_sent_stddev` float DEFAULT NULL,
      `Rows_sent_median` float DEFAULT NULL,
      `Rows_examined_sum` float DEFAULT NULL,
      `Rows_examined_min` float DEFAULT NULL,
      `Rows_examined_max` float DEFAULT NULL,
      `Rows_examined_pct_95` float DEFAULT NULL,
      `Rows_examined_stddev` float DEFAULT NULL,
      `Rows_examined_median` float DEFAULT NULL,
      `Rows_affected_sum` float DEFAULT NULL,
      `Rows_affected_min` float DEFAULT NULL,
      `Rows_affected_max` float DEFAULT NULL,
      `Rows_affected_pct_95` float DEFAULT NULL,
      `Rows_affected_stddev` float DEFAULT NULL,
      `Rows_affected_median` float DEFAULT NULL,
      `Rows_read_sum` float DEFAULT NULL,
      `Rows_read_min` float DEFAULT NULL,
      `Rows_read_max` float DEFAULT NULL,
      `Rows_read_pct_95` float DEFAULT NULL,
      `Rows_read_stddev` float DEFAULT NULL,
      `Rows_read_median` float DEFAULT NULL,
      `Merge_passes_sum` float DEFAULT NULL,
      `Merge_passes_min` float DEFAULT NULL,
      `Merge_passes_max` float DEFAULT NULL,
      `Merge_passes_pct_95` float DEFAULT NULL,
      `Merge_passes_stddev` float DEFAULT NULL,
      `Merge_passes_median` float DEFAULT NULL,
      `InnoDB_IO_r_ops_min` float DEFAULT NULL,
      `InnoDB_IO_r_ops_max` float DEFAULT NULL,
      `InnoDB_IO_r_ops_pct_95` float DEFAULT NULL,
      `InnoDB_IO_r_ops_stddev` float DEFAULT NULL,
      `InnoDB_IO_r_ops_median` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_min` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_max` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_pct_95` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_stddev` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_median` float DEFAULT NULL,
      `InnoDB_IO_r_wait_min` float DEFAULT NULL,
      `InnoDB_IO_r_wait_max` float DEFAULT NULL,
      `InnoDB_IO_r_wait_pct_95` float DEFAULT NULL,
      `InnoDB_IO_r_ops_stddev` float DEFAULT NULL,
      `InnoDB_IO_r_ops_median` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_min` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_max` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_pct_95` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_stddev` float DEFAULT NULL,
      `InnoDB_IO_r_bytes_median` float DEFAULT NULL,
      `InnoDB_IO_r_wait_min` float DEFAULT NULL,
      `InnoDB_IO_r_wait_max` float DEFAULT NULL,
      `InnoDB_IO_r_wait_pct_95` float DEFAULT NULL,
      `InnoDB_IO_r_wait_stddev` float DEFAULT NULL,
      `InnoDB_IO_r_wait_median` float DEFAULT NULL,
      `InnoDB_rec_lock_wait_min` float DEFAULT NULL,
      `InnoDB_rec_lock_wait_max` float DEFAULT NULL,
      `InnoDB_rec_lock_wait_pct_95` float DEFAULT NULL,
      `InnoDB_rec_lock_wait_stddev` float DEFAULT NULL,
      `InnoDB_rec_lock_wait_median` float DEFAULT NULL,
      `InnoDB_queue_wait_min` float DEFAULT NULL,
      `InnoDB_queue_wait_max` float DEFAULT NULL,
      `InnoDB_queue_wait_pct_95` float DEFAULT NULL,
      `InnoDB_queue_wait_stddev` float DEFAULT NULL,
      `InnoDB_queue_wait_median` float DEFAULT NULL,
      `InnoDB_pages_distinct_min` float DEFAULT NULL,
      `InnoDB_pages_distinct_max` float DEFAULT NULL,
      `InnoDB_pages_distinct_pct_95` float DEFAULT NULL,
      `InnoDB_pages_distinct_stddev` float DEFAULT NULL,
      `InnoDB_pages_distinct_median` float DEFAULT NULL,
      `QC_Hit_cnt` float DEFAULT NULL,
      `QC_Hit_sum` float DEFAULT NULL,
      `Full_scan_cnt` float DEFAULT NULL,
      `Full_scan_sum` float DEFAULT NULL,
      `Full_join_cnt` float DEFAULT NULL,
      `Full_join_sum` float DEFAULT NULL,
      `Tmp_table_cnt` float DEFAULT NULL,
      `Tmp_table_sum` float DEFAULT NULL,
      `Disk_tmp_table_cnt` float DEFAULT NULL,
      `Disk_tmp_table_sum` float DEFAULT NULL,
      `Filesort_cnt` float DEFAULT NULL,
      `Filesort_sum` float DEFAULT NULL,
      `Disk_filesort_cnt` float DEFAULT NULL,
      `Disk_filesort_sum` float DEFAULT NULL,
      PRIMARY KEY (`checksum`,`ts_min`,`ts_max`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    -- Create the user that Box anemmometer will use.
    grant ALL ON `slow_query_log`.* to 'anemometer'@'%' IDENTIFIED BY 'superSecurePass';"
    

#### Put some data in the DB ####

Next, grab that slow query log file you have (mine's called "slow.log"!), and run pt-query-digest on it:
**NOTE:** I'm using a BASH 3.0 shell here on my MySQL database server! This is so the "$HOSTNAME" variable properly replaces with "db.example.com")

    $ pt-query-digest --review h=db.example.com,D=slow_query_log,t=global_query_review --review-history h=db.example.com,D=slow_query_log,t=global_query_review_history --no-report --limit=0% --filter=" \$event->{Bytes} = length(\$event->{arg}) and \$event->{hostname}=\"$HOSTNAME\"" /var/lib/mysql/db.example.com-slow.log
    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57A" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 27.
    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57B" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 28.
    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57C" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 29.

You may see an error like above, that's okay!.
TODO: explain what the options above are doing.


#### View the data! ####

Now, navigate to the web root of your apache server and snag a copy of the Box Anemometer code. Then copy the sample config so you can edit it:

    $ git clone git@github.com:box/Anemometer.git anemometer
    $ cd anemometer/conf
    $ cp sample.config.inc.php config.inc.php 


The sample config explains every setting you may want to change in it.  At the very least, make sure you set the Datasource to the MySQL database you're story the analyzed digest information in:

    $conf['datasources']['locahost'] = array(
    	'host'	=> 'db.example.com',
    	'port'	=> 3306,
    	'db'	=> 'slow_query_log',
    	'user'	=> 'anemometer',
    	'password' => 'superSecurePass',
    	'tables' => array(
    		'global_query_review' => 'fact',
    		'global_query_review_history' => 'dimension'
    	)
    );


Now you should be able to navigate to your webserver in a browser and see Box Anemometer in action!


### Phpdocs ###

Phpdocs for this tool can be found in the "docs" sub-directory of the project.

### Dependencies ###

This application requires an Apache webserver with PHP and a MySQL database that contains the data aggregated from MySQL slow query logs.
