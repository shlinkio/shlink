FROM php:7.3.1-fpm-alpine3.8
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV PREDIS_VERSION 4.2.0
ENV MEMCACHED_VERSION 3.1.3
ENV APCU_VERSION 5.1.16
ENV APCU_BC_VERSION 1.0.4
ENV XDEBUG_VERSION "2.7.0RC1"

RUN apk update

# Install common php extensions
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install iconv
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install calendar

RUN apk add --no-cache --virtual sqlite-libs
RUN apk add --no-cache --virtual sqlite-dev
RUN docker-php-ext-install pdo_sqlite

RUN apk add --no-cache --virtual icu-dev
RUN docker-php-ext-install intl

RUN apk add --no-cache --virtual libzip-dev zlib-dev
RUN docker-php-ext-install zip

RUN apk add --no-cache --virtual libpng-dev
RUN docker-php-ext-install gd

# Install redis extension
ADD https://github.com/phpredis/phpredis/archive/$PREDIS_VERSION.tar.gz /tmp/phpredis.tar.gz
RUN mkdir -p /usr/src/php/ext/redis\
  && tar xf /tmp/phpredis.tar.gz -C /usr/src/php/ext/redis --strip-components=1
# configure and install
RUN docker-php-ext-configure redis\
  && docker-php-ext-install redis
# cleanup
RUN rm /tmp/phpredis.tar.gz

# Install memcached extension
RUN apk add --no-cache --virtual cyrus-sasl-dev
RUN apk add --no-cache --virtual libmemcached-dev
ADD https://github.com/php-memcached-dev/php-memcached/archive/v$MEMCACHED_VERSION.tar.gz /tmp/memcached.tar.gz
RUN mkdir -p /usr/src/php/ext/memcached\
  && tar xf /tmp/memcached.tar.gz -C /usr/src/php/ext/memcached --strip-components=1
# configure and install
RUN docker-php-ext-configure memcached\
  && docker-php-ext-install memcached
# cleanup
RUN rm /tmp/memcached.tar.gz

# Install APCu extension
ADD https://pecl.php.net/get/apcu-$APCU_VERSION.tgz /tmp/apcu.tar.gz
RUN mkdir -p /usr/src/php/ext/apcu\
  && tar xf /tmp/apcu.tar.gz -C /usr/src/php/ext/apcu --strip-components=1
# configure and install
RUN docker-php-ext-configure apcu\
  && docker-php-ext-install apcu
# cleanup
RUN rm /tmp/apcu.tar.gz

# Install APCu-BC extension
ADD https://pecl.php.net/get/apcu_bc-$APCU_BC_VERSION.tgz /tmp/apcu_bc.tar.gz
RUN mkdir -p /usr/src/php/ext/apcu-bc\
  && tar xf /tmp/apcu_bc.tar.gz -C /usr/src/php/ext/apcu-bc --strip-components=1
# configure and install
RUN docker-php-ext-configure apcu-bc\
  && docker-php-ext-install apcu-bc
# cleanup
RUN rm /tmp/apcu_bc.tar.gz

# Load APCU.ini before APC.ini
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini
RUN echo extension=apcu.so > /usr/local/etc/php/conf.d/20-php-ext-apcu.ini

# Install xdebug
ADD https://pecl.php.net/get/xdebug-$XDEBUG_VERSION /tmp/xdebug.tar.gz
RUN mkdir -p /usr/src/php/ext/xdebug\
  && tar xf /tmp/xdebug.tar.gz -C /usr/src/php/ext/xdebug --strip-components=1
# configure and install
RUN docker-php-ext-configure xdebug\
  && docker-php-ext-install xdebug
# cleanup
RUN rm /tmp/xdebug.tar.gz

# Install composer
RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN chmod +x composer.phar
RUN mv composer.phar /usr/local/bin/composer

# Make home directory writable by anyone
RUN chmod 777 /home
