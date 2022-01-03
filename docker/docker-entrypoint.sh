#!/usr/bin/env sh
set -e

# If SHELL_VERBOSITY was not explicitly provided, run commands in quite mode (-q)
[ $SHELL_VERBOSITY ] && flags="" || flags="-q"

cd /etc/shlink

echo "Creating fresh database if needed..."
php bin/cli db:create -n ${flags}

echo "Updating database..."
php bin/cli db:migrate -n ${flags}

echo "Generating proxies..."
php vendor/doctrine/orm/bin/doctrine.php orm:generate-proxies -n ${flags}

echo "Clearing entities cache..."
php vendor/doctrine/orm/bin/doctrine.php orm:clear-cache:metadata -n ${flags}

# Try to download GeoLite2 db file only if the license key env var was defined
if [ ! -z "${GEOLITE_LICENSE_KEY}" ]; then
  echo "Downloading GeoLite2 db file..."
  php bin/cli visit:download-db -n ${flags}
fi

# Periodically run visit:locate every hour, if ENABLE_PERIODIC_VISIT_LOCATE=true was provided
if [ $ENABLE_PERIODIC_VISIT_LOCATE ]; then
  echo "Configuring periodic visit location..."
  echo "0 * * * * php /etc/shlink/bin/cli visit:locate -q" > /etc/crontabs/root
  /usr/sbin/crond &
fi

# When restarting the container, openswoole might think it is already in execution
# This forces the app to be started every second until the exit code is 0
until php vendor/bin/laminas mezzio:swoole:start; do sleep 1 ; done
