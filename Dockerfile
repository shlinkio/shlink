FROM php:7.4.2-alpine3.11 as base

ARG SHLINK_VERSION=2.0.5
ENV SHLINK_VERSION ${SHLINK_VERSION}
ENV SWOOLE_VERSION 4.4.15
ENV LC_ALL "C"

WORKDIR /etc/shlink

RUN \
    # Install mysql and calendar
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

# Install sqlsrv driver
RUN if [ $(uname -m) == "x86_64" ]; then \
      wget https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.5.1.1-1_amd64.apk && \
      wget https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.5.1.1-1_amd64.apk && \
      apk add --allow-untrusted msodbcsql17_17.5.1.1-1_amd64.apk && \
      apk add --allow-untrusted mssql-tools_17.5.1.1-1_amd64.apk && \
      apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS unixodbc-dev && \
      pecl install pdo_sqlsrv && \
      docker-php-ext-enable pdo_sqlsrv && \
      apk del .phpize-deps && \
      rm msodbcsql17_17.5.1.1-1_amd64.apk && \
      rm mssql-tools_17.5.1.1-1_amd64.apk ; \
    fi

# Install swoole
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS && \
    pecl install swoole-${SWOOLE_VERSION} && \
    docker-php-ext-enable swoole && \
    apk del .phpize-deps

# Install shlink
FROM base as builder
COPY . .
COPY --from=composer:1.10.1 /usr/bin/composer ./composer.phar
RUN apk add --no-cache git && \
    php composer.phar install --no-dev --optimize-autoloader --prefer-dist --no-progress --no-interaction && \
    php composer.phar clear-cache && \
    rm -r docker composer.* && \
    sed -i "s/%SHLINK_VERSION%/${SHLINK_VERSION}/g" config/autoload/app_options.global.php


# Prepare final image
FROM base
LABEL maintainer="Alejandro Celaya <alejandro@alejandrocelaya.com>"

COPY --from=builder /etc/shlink .
RUN ln -s /etc/shlink/bin/cli /usr/local/bin/shlink

# Expose swoole port
EXPOSE 8080

# Expose params config dir, since the user is expected to provide custom config from there
VOLUME /etc/shlink/config/params

# Copy config specific for the image
COPY docker/docker-entrypoint.sh docker-entrypoint.sh
COPY docker/config/shlink_in_docker.local.php config/autoload/shlink_in_docker.local.php
COPY docker/config/php.ini ${PHP_INI_DIR}/conf.d/

ENTRYPOINT ["/bin/sh", "./docker-entrypoint.sh"]
