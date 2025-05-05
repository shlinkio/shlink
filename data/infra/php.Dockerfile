FROM php:8.4-fpm-alpine3.21
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV APCU_VERSION 5.1.24
ENV PDO_SQLSRV_VERSION 5.12.0
ENV MS_ODBC_DOWNLOAD '7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8'
ENV MS_ODBC_SQL_VERSION 18_18.4.1.1

RUN apk update

# Install common php extensions
RUN docker-php-ext-install pdo_mysql
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

RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS linux-headers && \
    docker-php-ext-install sockets && \
    apk del .phpize-deps
RUN docker-php-ext-install bcmath

# Install APCu extension
ADD https://pecl.php.net/get/apcu-$APCU_VERSION.tgz /tmp/apcu.tar.gz
RUN mkdir -p /usr/src/php/ext/apcu \
  && tar xf /tmp/apcu.tar.gz -C /usr/src/php/ext/apcu --strip-components=1 \
  && docker-php-ext-configure apcu \
  && docker-php-ext-install apcu \
  && rm /tmp/apcu.tar.gz \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini \
  && echo extension=apcu.so > /usr/local/etc/php/conf.d/20-php-ext-apcu.ini

# Install xdebug and sqlsrv driver
RUN apk add --update linux-headers && \
    wget https://download.microsoft.com/download/${MS_ODBC_DOWNLOAD}/msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
    apk add --allow-untrusted msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS unixodbc-dev && \
    pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} xdebug && \
    docker-php-ext-enable pdo_sqlsrv xdebug && \
    apk del .phpize-deps && \
    rm msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Make home directory writable by anyone
RUN chmod 777 /home
