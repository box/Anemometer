
Box Anemometer
--------------
[![Project Status](http://opensource.box.com/badges/maintenance.svg)](http://opensource.box.com/badges)
[![Travis](https://img.shields.io/travis/box/Anemometer.svg?maxAge=2592000)](https://travis-ci.org/box/Anemometer)
[![Join the chat at https://gitter.im/box/Anemometer](https://badges.gitter.im/box/Anemometer.svg)](https://gitter.im/box/Anemometer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

This is the Box Anemometer, the MySQL Slow Query Monitor.  This tool is used to analyze slow query logs collected from MySQL instances to identify problematic queries.

### Documentation ###

1.	[What is Anemometer?](https://github.com/box/Anemometer/wiki)
2.	[Extra Features](https://github.com/box/Anemometer/wiki/Extra-Features)
3.	[Using PERFORMANCE_SCHEMA](https://github.com/box/Anemometer/wiki/Using-PERFORMANCE_SCHEMA-in-MySQL-5.6)
4.	[Collection Script](https://github.com/box/Anemometer/wiki/Anemometer-Collection-Script)
5.	[Development with Vagrant](https://github.com/box/Anemometer/wiki/Development-with-Vagrant)
6.	Installation: See Quickstart below
7.  [Unit Testing](https://github.com/box/Anemometer/wiki/Unit-Testing)

### Quickstart ###

If you're just completely itching to start using this tool, here's what you need:

1.	a MySQL database to store query analysis data in.
2.	[pt-query-digest](http://www.percona.com/doc/percona-toolkit/pt-query-digest.html).
	*	You may as well just get the whole [Percona Toolkit](http://www.percona.com/doc/percona-toolkit) while you're at it :)
3.	a slow query log from a MySQL server (see [The Slow Query Log](http://dev.mysql.com/doc/refman/5.5/en/slow-query-log.html) for info on getting one)
4.	a webserver with PHP 5.5+


#### Setup DB ####

First up, grab the anemometer code from github. Navigate to the document root of your web server and snag a copy of the Box Anemometer code.

    $ git clone git://github.com/box/Anemometer.git anemometer 
Or, if you have 9418 port closed:

    $ git clone https://github.com/box/Anemometer.git anemometer 


Then change your current working directory to the Anemometer directory:

    $ cd anemometer
    
Next, you should connect to the MySQL database you're looking to store the analysis data in and issue the following command:

    $ mysql -h db.example.com < install.sql
    $ mysql -h db.example.com -e "grant ALL ON slow_query_log.* to 'anemometer'@'%' IDENTIFIED BY 'superSecurePass';"

#### Put some data in the DB ####

Next, grab that slow query log file you have (mine's called "slow.log"!), and run pt-query-digest on it:
**NOTE:** I'm using a BASH 3.0 shell here on my MySQL database server! This is so the "$HOSTNAME" variable properly replaces with "db.example.com")


For pt-query-digest version < 2.2

    $ pt-query-digest --user=anemometer --password=superSecurePass \
                      --review h=db.example.com,D=slow_query_log,t=global_query_review \
                      --review-history h=db.example.com,D=slow_query_log,t=global_query_review_history \
                      --no-report --limit=0% \ 
                      --filter=" \$event->{Bytes} = length(\$event->{arg}) and \$event->{hostname}=\"$HOSTNAME\"" \ 
                      /var/lib/mysql/db.example.com-slow.log
    

For pt-query-digest version >= 2.2

    $ pt-query-digest --user=anemometer --password=superSecurePass \
                      --review h=db.example.com,D=slow_query_log,t=global_query_review \
                      --history h=db.example.com,D=slow_query_log,t=global_query_review_history \
                      --no-report --limit=0% \ 
                      --filter=" \$event->{Bytes} = length(\$event->{arg}) and \$event->{hostname}=\"$HOSTNAME\"" \ 
                      /var/lib/mysql/db.example.com-slow.log


    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57A" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 27.
    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57B" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 28.
    Pipeline process 11 (aggregate fingerprint) caused an error: Argument "57C" isn't numeric in numeric gt (>) at (eval 40) line 6, <> line 29.

You may see an error like above, that's okay!
TODO: explain what the options above are doing.


#### View the data! ####

Now, navigate to the document root of your web server and copy the sample config so you can edit it:

    $ cd anemometer/conf
    $ cp sample.config.inc.php config.inc.php 


The sample config explains every setting you may want to change in it.  At the very least, make sure you set the Datasource to the MySQL database you're storing the analyzed digest information in:

    $conf['datasources']['localhost'] = array(
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

In addition, the "explain" plugin is enabled by default in the current release and you'll need to setup the username and password it uses to an account that has privileges to explain queries on a given schema on a host.  For example, if you're digesting slow logs that primarily contain queries from the "world" database on db.example.com, you'll need to ensure that the user account you put into the following section of the config has the necessary privileges on the "world" database on db.example.com.  To do this, scroll down in the sample config to the section containing the plugins configuration and change the 'user' and 'password' parameters to an appropriate account:

    $conf['plugins'] = array(
            ...
        'explain'       =>      function ($sample) {
            $conn['user'] = 'anemometer';
            $conn['password'] = 'superSecurePass';
            
            return $conn;
        },
    );



Now you should be able to navigate to your webserver in a browser and see Box Anemometer in action!


### Phpdocs ###

Phpdocs for this tool can be found in the "docs" sub-directory of the project.

### Dependencies ###

This application requires an Apache webserver with PHP 5.5+ and a MySQL database that contains the data aggregated from MySQL slow query logs.


## Copyright and License

Copyright 2014 Box, Inc. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
