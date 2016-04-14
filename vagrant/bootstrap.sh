#!/usr/bin/env bash
sudo -s

# base packages, including percona mysql repo
yum update -y
yum install -y kernel-devel-`uname -r` gcc make perl bzip2
yum install -y http://www.percona.com/downloads/percona-release/percona-release-0.0-1.x86_64.rpm
yum install -y Percona-Server-client-56 Percona-Server-shared-56 Percona-Server-server-56
yum install -y httpd php php-mysql php-bcmath wget git

# Rsync between 3.0 and 3.1 are not compatible
# FATAL I/O ERROR: dying to avoid a --delete-during issue with a pre-3.0.7 receiver.
wget http://mirror.symnds.com/distributions/gf/el/7/plus/x86_64/rsync-3.1.1-6.gf.el7.x86_64.rpm
rpm -Uvh rsync-3.1.1-6.gf.el7.x86_64.rpm

# install percona toolkit
yum install -y perl-DBD-MySQL perl-Time-HiRes perl-IO-Socket-SSL
wget -q "http://www.percona.com/redir/downloads/percona-toolkit/2.2.10/RPM/percona-toolkit-2.2.10-1.noarch.rpm"
rpm -i percona-toolkit-2.2.10-1.noarch.rpm

#sed -i 's/enforcing/disabled/g' /etc/sysconfig/selinux
sed -i s/SELINUX=enforcing/SELINUX=disabled/g /etc/selinux/config

setenforce 0

systemctl start mysqld.service
systemctl start httpd.service

git clone https://github.com/3manuek/Anemometer.git anemometer

# setup symlink for apache & install anemometer files
#ln -s /vagrant/anemometer  /var/www/html/anemometer

[[ ! -h  /var/www/html/anemometer ]] && ln -s /home/vagrant/anemometer /var/www/html/anemometer

#mysql -u root < /vagrant/anemometer/install.sql
mysql -u root < anemometer/mysql56-install.sql 2> /dev/null


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
