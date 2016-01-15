#!/bin/bash

while ! nc -vz $DB_PORT_3306_TCP_ADDR 3306; do sleep 1; done

mysql -h $DB_PORT_3306_TCP_ADDR -u $DB_ENV_MYSQL_USER -p$DB_ENV_MYSQL_PASS < /var/www/html/install.sql
mysql -h $DB_PORT_3306_TCP_ADDR -u $DB_ENV_MYSQL_USER -p$DB_ENV_MYSQL_PASS < /var/www/html/mysql56-install.sql

apache2-foreground
