FROM php:8.3-alpine3.19
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV APCU_VERSION 5.1.23
ENV PDO_SQLSRV_VERSION 5.12.0
ENV MS_ODBC_DOWNLOAD 'b/9/f/b9f3cce4-3925-46d4-9f46-da08869c6486'
ENV MS_ODBC_SQL_VERSION 18_18.1.1.1

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

VOLUME /home/shlink
WORKDIR /home/shlink

# Expose roadrunner port
EXPOSE 8080

CMD \
    # Install dependencies if the vendor dir does not exist
    if [[ ! -d "./vendor" ]]; then /usr/local/bin/composer install ; fi && \
    # Download roadrunner binary
    if [[ ! -f "./bin/rr" ]]; then ./vendor/bin/rr get --no-interaction --no-config --location bin/ && chmod +x bin/rr ; fi && \
    # Run with `exec` so that signals are properly handled
    exec ./bin/rr serve -c config/roadrunner/.rr.dev.yml
