FROM php:8.3-alpine3.19 as base

ARG SHLINK_VERSION=latest
ENV SHLINK_VERSION ${SHLINK_VERSION}
ARG SHLINK_RUNTIME=rr
ENV SHLINK_RUNTIME ${SHLINK_RUNTIME}
ARG SHLINK_USER_ID='root'
ENV SHLINK_USER_ID ${SHLINK_USER_ID}

ENV PDO_SQLSRV_VERSION 5.12.0
ENV MS_ODBC_DOWNLOAD 'b/9/f/b9f3cce4-3925-46d4-9f46-da08869c6486'
ENV MS_ODBC_SQL_VERSION 18_18.1.1.1
ENV LC_ALL 'C'

WORKDIR /etc/shlink

# Install required PHP extensions
RUN \
    # Temp install dev dependencies needed to compile the extensions
    apk add --no-cache --virtual .dev-deps sqlite-dev postgresql-dev icu-dev libzip-dev zlib-dev libpng-dev linux-headers && \
    docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql intl calendar sockets bcmath zip gd && \
    apk add --no-cache sqlite-libs && \
    docker-php-ext-install -j"$(nproc)" pdo_sqlite && \
    # Remove temp dev extensions, and install prod equivalents that are required at runtime
    apk del .dev-deps && \
    apk add --no-cache postgresql icu libzip libpng

# Install sqlsrv driver for x86_64 builds
RUN apk add --no-cache --virtual .phpize-deps ${PHPIZE_DEPS} unixodbc-dev && \
    if [ $(uname -m) == "x86_64" ]; then \
      wget https://download.microsoft.com/download/${MS_ODBC_DOWNLOAD}/msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
      apk add --allow-untrusted msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk && \
      pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} && \
      docker-php-ext-enable pdo_sqlsrv && \
      rm msodbcsql${MS_ODBC_SQL_VERSION}-1_amd64.apk ; \
    fi; \
    apk del .phpize-deps

# Install shlink
FROM base as builder
COPY . .
COPY --from=composer:2 /usr/bin/composer ./composer.phar
RUN apk add --no-cache git && \
    php composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-progress --no-interaction && \
    php composer.phar clear-cache && \
    rm -r docker composer.* && \
    sed -i "s/%SHLINK_VERSION%/${SHLINK_VERSION}/g" config/autoload/app_options.global.php


# Prepare final image
FROM base
LABEL maintainer="Alejandro Celaya <alejandro@alejandrocelaya.com>"

COPY --from=builder --chown=${SHLINK_USER_ID} /etc/shlink .
RUN ln -s /etc/shlink/bin/cli /usr/local/bin/shlink && \
    if [ "$SHLINK_RUNTIME" == 'rr' ]; then \
      php ./vendor/bin/rr get --no-interaction --no-config --location bin/ && chmod +x bin/rr ; \
    fi;

# Expose default port
EXPOSE 8080

# Copy config specific for the image
COPY docker/docker-entrypoint.sh docker-entrypoint.sh
COPY docker/config/shlink_in_docker.local.php config/autoload/shlink_in_docker.local.php
COPY docker/config/php.ini ${PHP_INI_DIR}/conf.d/

USER ${SHLINK_USER_ID}

ENTRYPOINT ["/bin/sh", "./docker-entrypoint.sh"]
