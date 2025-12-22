FROM php:8.4-alpine3.21
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

ENV PDO_SQLSRV_VERSION='5.12.0'
ENV MS_ODBC_DOWNLOAD='7/6/d/76de322a-d860-4894-9945-f0cc5d6a45f8'
ENV MS_ODBC_SQL_VERSION='18_18.4.1.1'

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

RUN apk add --no-cache postgresql-dev
RUN docker-php-ext-install pdo_pgsql

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie
RUN apk add --no-cache libzip-dev zlib-dev && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS linux-headers && \
    docker-php-ext-install sockets && \
    pie install xdebug/xdebug && \
    pie install pecl/zip && \
    apk del .phpize-deps
RUN docker-php-ext-install bcmath

# Install sqlsrv driver
RUN apk add --update linux-headers && \
    wget https://download.microsoft.com/download/${MS_ODBC_DOWNLOAD}/msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
    apk add --allow-untrusted msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS unixodbc-dev && \
    pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} && \
    docker-php-ext-enable pdo_sqlsrv && \
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
    # Create env file if it does not exist yet
    if [[ ! -f "./config/params/shlink_dev_env.php" ]]; then cp ./config/params/shlink_dev_env.php.dist ./config/params/shlink_dev_env.php ; fi && \
    # Run with `exec` so that signals are properly handled
    exec ./bin/rr serve -c config/roadrunner/.rr.dev.yml
