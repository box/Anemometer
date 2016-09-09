FROM centos:7
MAINTAINER Box Open Source "oss@box.com"

COPY . /var/www/html
WORKDIR /var/www/html

RUN yum -y install http://www.percona.com/downloads/percona-release/redhat/0.1-3/percona-release-0.1-3.noarch.rpm

RUN yum -y install percona-toolkit mysql
RUN yum -y install python git nmap-ncat httpd php php-mysql php-bcmath php-xml


RUN cd /var/www/html && \
  curl -sS https://getcomposer.org/installer | php && \
  install -m 755 composer.phar /usr/local/bin/composer && \
  /usr/local/bin/composer update && \
  /usr/local/bin/composer install

RUN install -m 755 docker/docker-entrypoint.py /docker-entrypoint.py

EXPOSE 80

CMD ["/docker-entrypoint.py"]
