FROM php:8.1.3-alpine3.15 as base

ARG SHLINK_VERSION=latest
ENV SHLINK_VERSION ${SHLINK_VERSION}
ENV OPENSWOOLE_VERSION 4.9.1
ENV PDO_SQLSRV_VERSION 5.10.0
ENV MS_ODBC_SQL_VERSION 17.5.2.2
ENV LC_ALL "C"

WORKDIR /etc/shlink

# Install required PHP extensions
RUN \
    # Temp install dev dependencies needed to compile the extensions
    apk add --no-cache --virtual .dev-deps sqlite-dev postgresql-dev icu-dev libzip-dev zlib-dev libpng-dev && \
    docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql intl calendar sockets bcmath zip gd && \
    apk add --no-cache sqlite-libs && \
    docker-php-ext-install -j"$(nproc)" pdo_sqlite && \
    # Remove temp dev extensions, and install prod equivalents that are required at runtime
    apk del .dev-deps && \
    apk add --no-cache postgresql icu libzip libpng

# Install openswoole and sqlsrv driver for x86_64 builds
RUN apk add --no-cache --virtual .phpize-deps ${PHPIZE_DEPS} unixodbc-dev && \
    pecl install openswoole-${OPENSWOOLE_VERSION} && \
    docker-php-ext-enable openswoole && \
    if [ $(uname -m) == "x86_64" ]; then \
      wget https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
      apk add --no-cache --allow-untrusted msodbcsql17_${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
      pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} && \
      docker-php-ext-enable pdo_sqlsrv && \
      rm msodbcsql17_${MS_ODBC_SQL_VERSION}-1_amd64.apk ; \
    fi; \
    apk del .phpize-deps

# Install shlink
FROM base as builder
COPY . .
COPY --from=composer:2 /usr/bin/composer ./composer.phar
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

# Expose default openswoole port
EXPOSE 8080

# Copy config specific for the image
COPY docker/docker-entrypoint.sh docker-entrypoint.sh
COPY docker/config/shlink_in_docker.local.php config/autoload/shlink_in_docker.local.php
COPY docker/config/php.ini ${PHP_INI_DIR}/conf.d/

# Change the ownership of /etc/shlink/data to be writable, then change the user to non-root
# FIXME Disabled for now, as it conflicts with ENABLE_PERIODIC_VISIT_LOCATE, which is used to configure a cron as root.
#       Ref: https://github.com/shlinkio/shlink/issues/1132
#RUN chown 1001 /etc/shlink/data
#RUN chown 1001 /etc/shlink/data/locks
#RUN chown 1001 /etc/shlink/data/proxies
#RUN chown 1001 /etc/shlink/data/cache
#RUN chown 1001 /etc/shlink/data/log
#USER 1001

ENTRYPOINT ["/bin/sh", "./docker-entrypoint.sh"]
