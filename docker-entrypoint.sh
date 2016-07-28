#!/bin/bash

while ! nc -vz $ANEMOMETER_PORT_3306_TCP_ADDR 3306; do sleep 1; done

mysql -h $ANEMOMETER_PORT_3306_TCP_ADDR -u $ANEMOMETER_ENV_MYSQL_USER -p$ANEMOMETER_ENV_MYSQL_PASS slow_query_log -e 'show tables;' &>/dev/null || \
( mysql -h $ANEMOMETER_PORT_3306_TCP_ADDR -u $ANEMOMETER_ENV_MYSQL_USER -p$ANEMOMETER_ENV_MYSQL_PASS < /var/www/html/install.sql && \
mysql -h $ANEMOMETER_PORT_3306_TCP_ADDR -u $ANEMOMETER_ENV_MYSQL_USER -p$ANEMOMETER_ENV_MYSQL_PASS < /var/www/html/mysql56-install.sql )

apache2-foreground
