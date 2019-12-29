FROM php:7.4.1-alpine3.10
LABEL maintainer="Alejandro Celaya <alejandro@alejandrocelaya.com>"

ARG SHLINK_VERSION=1.20.2
ENV SHLINK_VERSION ${SHLINK_VERSION}
ENV SWOOLE_VERSION 4.4.12
ENV COMPOSER_VERSION 1.9.1

WORKDIR /etc/shlink

RUN \
    # Install mysl and calendar
    docker-php-ext-install -j"$(nproc)" pdo_mysql calendar && \
    # Install sqlite
    apk add --no-cache sqlite-libs sqlite-dev && \
    docker-php-ext-install -j"$(nproc)" pdo_sqlite && \
    # Install postgres
    apk add --no-cache postgresql-dev && \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql && \
    # Install intl
    apk add --no-cache icu-dev && \
    docker-php-ext-install -j"$(nproc)" intl && \
    # Install zip and gd
    apk add --no-cache libzip-dev zlib-dev libpng-dev && \
    docker-php-ext-install -j"$(nproc)" zip gd

# Install swoole
# First line fixes an error when installing pecl extensions. Found in https://github.com/docker-library/php/issues/233
RUN apk add --no-cache --virtual .phpize-deps ${PHPIZE_DEPS} && \
    pecl install swoole-${SWOOLE_VERSION} && \
    docker-php-ext-enable swoole && \
    apk del .phpize-deps

# Install shlink
COPY . .
RUN rm -rf ./docker && \
    wget https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar && \
    php composer.phar install --no-dev --optimize-autoloader --prefer-dist --no-progress --no-interaction && \
    php composer.phar clear-cache && \
    rm composer.*

# Add shlink to the path to ease running it after container is created
RUN ln -s /etc/shlink/bin/cli /usr/local/bin/shlink
RUN sed -i "s/%SHLINK_VERSION%/${SHLINK_VERSION}/g" config/autoload/app_options.global.php

# Expose swoole port
EXPOSE 8080

# Expose params config dir, since the user is expected to provide custom config from there
VOLUME /etc/shlink/config/params

# Copy config specific for the image
COPY docker/docker-entrypoint.sh docker-entrypoint.sh
COPY docker/config/shlink_in_docker.local.php config/autoload/shlink_in_docker.local.php
COPY docker/config/php.ini ${PHP_INI_DIR}/conf.d/

ENTRYPOINT ["/bin/sh", "./docker-entrypoint.sh"]
