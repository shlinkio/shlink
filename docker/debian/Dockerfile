FROM php:8.5-cli-trixie AS base

ARG SHLINK_VERSION=latest
ENV SHLINK_VERSION=${SHLINK_VERSION}
ARG SHLINK_RUNTIME=rr
ENV SHLINK_RUNTIME=${SHLINK_RUNTIME}

ENV USER_ID='1001'
ENV PDO_SQLSRV_VERSION='5.13.0'
ENV LC_ALL='C'

WORKDIR /etc/shlink

# Install required PHP extensions, then remove their build dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
      libicu-dev libpq-dev libsqlite3-dev libzip-dev zlib1g-dev \
      libicu76 libpq5 libsqlite3-0 libzip5 libpng16-16t64 && \
    docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql intl calendar sockets bcmath zip pdo_sqlite && \
    apt-get purge -y --auto-remove libicu-dev libpq-dev libsqlite3-dev libzip-dev zlib1g-dev && \
    rm -rf /var/lib/apt/lists/*

# Install the SQL Server driver on both amd64 and arm64
RUN curl -fsSL https://packages.microsoft.com/config/debian/13/packages-microsoft-prod.deb \
      -o /tmp/packages-microsoft-prod.deb && \
    dpkg -i /tmp/packages-microsoft-prod.deb && \
    rm /tmp/packages-microsoft-prod.deb && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev && \
    pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} && \
    docker-php-ext-enable pdo_sqlsrv && \
    apt-get purge -y --auto-remove unixodbc-dev && \
    rm -rf /var/lib/apt/lists/*

# Install shlink
FROM base AS builder
COPY . .
COPY --from=composer:2 /usr/bin/composer ./composer.phar
RUN apt-get update && \
    apt-get install -y --no-install-recommends git && \
    rm -rf /var/lib/apt/lists/* && \
    php composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-progress --no-interaction && \
    php composer.phar clear-cache && \
    rm -r docker composer.* && \
    sed -i "s/%SHLINK_VERSION%/${SHLINK_VERSION}/g" module/Core/src/Config/Options/AppOptions.php

# Prepare final image
FROM base
LABEL maintainer="Alejandro Celaya <alejandro@alejandrocelaya.com>"

COPY --from=builder --chown=${USER_ID} /etc/shlink .
RUN ln -s /etc/shlink/bin/cli /usr/local/bin/shlink && \
    if [ "$SHLINK_RUNTIME" = 'rr' ]; then \
      php ./vendor/bin/rr get --no-interaction --no-config --location bin/ && chmod +x bin/rr; \
    fi

# Expose default port
EXPOSE 8080

# Copy config specific for the image
COPY docker/docker-entrypoint.sh docker-entrypoint.sh
COPY docker/config/php.ini ${PHP_INI_DIR}/conf.d/

USER ${USER_ID}

ENTRYPOINT ["/bin/sh", "./docker-entrypoint.sh"]
