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
php bin/doctrine orm:generate-proxies -n ${flags}

echo "Clearing entities cache..."
php bin/doctrine orm:clear-cache:metadata -n ${flags}

# Try to download GeoLite2 db file only if the license key env var was defined and skipping was not explicitly set
if [ ! -z "${GEOLITE_LICENSE_KEY}" ] && [ "${SKIP_INITIAL_GEOLITE_DOWNLOAD}" != "true" ]; then
  echo "Downloading GeoLite2 db file..."
  php bin/cli visit:download-db -n ${flags}
fi

# Periodically run visit:locate every hour, if ENABLE_PERIODIC_VISIT_LOCATE=true was provided
if [ "${ENABLE_PERIODIC_VISIT_LOCATE}" = "true" ]; then
  echo "Configuring periodic visit location..."
  echo "0 * * * * php /etc/shlink/bin/cli visit:locate -q" > /tmp/crontab
  supercronic -passthrough-logs /tmp/crontab &
fi

# RoadRunner config needs these to have been set, so falling back to default values if not set yet
if [ "$SHLINK_RUNTIME" == 'rr' ]; then
  export PORT="${PORT:-"8080"}"
  # Default to 0 so that RoadRunner decides the number of workers based on the amount of logical CPUs
  export WEB_WORKER_NUM="${WEB_WORKER_NUM:-"0"}"
  export TASK_WORKER_NUM="${TASK_WORKER_NUM:-"0"}"
fi

if [ "$SHLINK_RUNTIME" == 'openswoole' ]; then
  # When restarting the container, openswoole might think it is already in execution
  # This forces the app to be started every second until the exit code is 0
  until php vendor/bin/laminas mezzio:swoole:start; do sleep 1 ; done
elif [ "$SHLINK_RUNTIME" == 'rr' ]; then
  ./bin/rr serve -c config/roadrunner/.rr.yml
fi
