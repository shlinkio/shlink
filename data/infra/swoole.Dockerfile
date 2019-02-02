FROM php:7.3.1-cli-alpine3.8
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV PREDIS_VERSION 4.2.0
ENV MEMCACHED_VERSION 3.1.3
ENV APCU_VERSION 5.1.16
ENV APCU_BC_VERSION 1.0.4

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

# Install swoole
# First line fixes an error when installing pecl extensions. Found in https://github.com/docker-library/php/issues/233
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS && \
    pecl install swoole && \
    docker-php-ext-enable swoole && \
    apk del .phpize-deps

# Install composer
RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN chmod +x composer.phar
RUN mv composer.phar /usr/local/bin/composer

# Make home directory writable by anyone
RUN chmod 777 /home

VOLUME /home/shlink
WORKDIR /home/shlink

# Expose swoole port
EXPOSE 8080

CMD /usr/local/bin/composer update && \
    # When restarting the container, swoole might think it is already in execution
    # This forces the app to be started every second until the exit code is 0
    until php ./vendor/bin/zend-expressive-swoole start; do sleep 1 ; done
