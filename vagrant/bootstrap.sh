#!/usr/bin/env bash


# create my.cnf for access
cat << EOF > /home/vagrant/.my.cnf
[client]
user=root
EOF


# setup symlink for apache & install anemometer files
ln -s /vagrant  /var/www/html/anemometer
mysql -u root < /vagrant/install.sql
mysql -u root < /vagrant/mysql56-install.sql



# add cron for collection script
cat << EOF > /home/vagrant/crontab
*/5 * * * * /vagrant/scripts/anemometer_collect.sh --interval 15 --history-db-host localhost --defaults-file /home/vagrant/.my.cnf --history-defaults-file /home/vagrant/.my.cnf
EOF
crontab -u root /home/vagrant/crontab
