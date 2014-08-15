#!/usr/bin/env bash

# base packages, including percona mysql repo
yum install -y http://www.percona.com/downloads/percona-release/percona-release-0.0-1.x86_64.rpm
yum install -y Percona-Server-client-56 Percona-Server-shared-56 Percona-Server-server-56
yum install -y httpd php php-mysql php-bcmath

# add init scripts & auto start
chkconfig --levels 235 mysqld on
/etc/init.d/mysql start
chkconfig --levels 235 httpd on
/etc/init.d/httpd start

# setup symlink for apache & install anemometer files
ln -s /vagrant/anemometer  /var/www/html/anemometer 
mysql -u root < /vagrant/anemometer/install.sql 
mysql -u root < /vagrant/anemometer/mysql56-install.sql

# install percona toolkit
yum install -y perl-DBD-MySQL perl-Time-HiRes perl-IO-Socket-SSL
wget -q "http://www.percona.com/redir/downloads/percona-toolkit/2.2.10/RPM/percona-toolkit-2.2.10-1.noarch.rpm"
rpm -i percona-toolkit-2.2.10-1.noarch.rpm 

# create my.cnf for access
cat << EOF > /home/vagrant/.my.cnf
[client]
user=root
EOF

# add cron for collection script
cat << EOF > /home/vagrant/crontab
*/5 * * * * /vagrant/anemometer/scripts/anemometer_collect.sh --interval 15 --history-db-host localhost --defaults-file /home/vagrant/.my.cnf --history-defaults-file /home/vagrant/.my.cnf
EOF
crontab -u root /home/vagrant/crontab
