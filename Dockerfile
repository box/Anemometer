FROM php:5-apache

COPY . /var/www/html
WORKDIR /var/www/html

RUN apt-get update && apt-get install -yf mysql-client
RUN \
  apt-get update && apt-get install -yf git unzip zlib1g-dev mysql-client netcat && \
  docker-php-ext-install zip bcmath mysql mysqli pdo pdo_mysql

RUN \
  cd /var/www/html && \
  curl -sS https://getcomposer.org/installer | php && \
  mv composer.phar /usr/local/bin/composer && \
  composer install && \
  ln -s /var/www/html/conf/sample.config.inc.php /var/www/html/conf/config.inc.php

ADD docker-entrypoint.sh /docker-entrypoint.sh

CMD ["/docker-entrypoint.sh"]
