#!/usr/bin/env sh
set -e

cd /etc/shlink

echo "Creating fresh database if needed..."
php bin/cli db:create -n -q

echo "Updating database..."
php bin/cli db:migrate -n -q

echo "Generating proxies..."
php vendor/doctrine/orm/bin/doctrine.php orm:generate-proxies -n -q

echo "Clearing entities cache..."
php vendor/doctrine/orm/bin/doctrine.php orm:clear-cache:metadata -n -q

# Try to download GeoLite2 db file only if the license key env var was defined
if [ ! -z "${GEOLITE_LICENSE_KEY}" ]; then
  echo "Downloading GeoLite2 db file..."
  php bin/cli visit:download-db -n -q
fi

# When restarting the container, swoole might think it is already in execution
# This forces the app to be started every second until the exit code is 0
until php vendor/bin/laminas mezzio:swoole:start; do sleep 1 ; done
