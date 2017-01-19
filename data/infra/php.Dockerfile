FROM php:7.1-fpm-alpine
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

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

RUN apk add --no-cache --virtual zlib-dev
RUN docker-php-ext-install zip

RUN apk add --no-cache --virtual libmcrypt-dev
RUN docker-php-ext-install mcrypt

RUN apk add --no-cache --virtual libpng-dev
RUN docker-php-ext-install gd

# Install redis extension
ADD https://github.com/phpredis/phpredis/archive/php7.tar.gz /tmp/phpredis.tar.gz
RUN mkdir -p /usr/src/php/ext/redis\
  && tar xf /tmp/phpredis.tar.gz -C /usr/src/php/ext/redis --strip-components=1
# configure and install
RUN docker-php-ext-configure redis\
  && docker-php-ext-install redis
# cleanup
RUN rm /tmp/phpredis.tar.gz

# Install APCu extension
ADD https://pecl.php.net/get/apcu-5.1.3.tgz /tmp/apcu.tar.gz
RUN mkdir -p /usr/src/php/ext/apcu\
  && tar xf /tmp/apcu.tar.gz -C /usr/src/php/ext/apcu --strip-components=1
# configure and install
RUN docker-php-ext-configure apcu\
  && docker-php-ext-install apcu
# cleanup
RUN rm /tmp/apcu.tar.gz

# Install APCu-BC extension
ADD https://pecl.php.net/get/apcu_bc-1.0.3.tgz /tmp/apcu_bc.tar.gz
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
ADD https://pecl.php.net/get/xdebug-2.5.0 /tmp/xdebug.tar.gz
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
