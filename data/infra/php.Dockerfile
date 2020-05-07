FROM php:7.4.5-fpm-alpine3.11
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV APCU_VERSION 5.1.18
ENV APCU_BC_VERSION 1.0.5
ENV XDEBUG_VERSION 2.9.0

RUN apk update

# Install common php extensions
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install iconv
RUN docker-php-ext-install calendar

RUN apk add --no-cache oniguruma-dev
RUN docker-php-ext-install mbstring

RUN apk add --no-cache sqlite-libs
RUN apk add --no-cache sqlite-dev
RUN docker-php-ext-install pdo_sqlite

RUN apk add --no-cache icu-dev
RUN docker-php-ext-install intl

RUN apk add --no-cache libzip-dev zlib-dev
RUN docker-php-ext-install zip

RUN apk add --no-cache libpng-dev
RUN docker-php-ext-install gd

RUN apk add --no-cache postgresql-dev
RUN docker-php-ext-install pdo_pgsql

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

# Install sqlsrv driver
RUN wget https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.5.1.1-1_amd64.apk && \
    apk add --allow-untrusted msodbcsql17_17.5.1.1-1_amd64.apk && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS unixodbc-dev && \
    pecl install pdo_sqlsrv && \
    docker-php-ext-enable pdo_sqlsrv && \
    apk del .phpize-deps && \
    rm msodbcsql17_17.5.1.1-1_amd64.apk

# Install composer
RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN chmod +x composer.phar
RUN mv composer.phar /usr/local/bin/composer

# Make home directory writable by anyone
RUN chmod 777 /home
