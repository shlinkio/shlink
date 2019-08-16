#!/usr/bin/env sh
set -e

cd /etc/shlink

echo "Creating fresh database if needed..."
php bin/cli db:create -n -q

echo "Updating database..."
php bin/cli db:migrate -n -q

echo "Generating proxies..."
php vendor/doctrine/orm/bin/doctrine.php orm:generate-proxies -n -q

# When restarting the container, swoole might think it is already in execution
# This forces the app to be started every second until the exit code is 0
until php vendor/zendframework/zend-expressive-swoole/bin/zend-expressive-swoole start; do sleep 1 ; done
