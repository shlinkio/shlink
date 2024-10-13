#!/usr/bin/env sh
set -e

cd /etc/shlink

# Create data directories if they do not exist. This allows data dir to be mounted as an empty dir if needed
mkdir -p data/cache data/locks data/log data/proxies

flags="--no-interaction --clear-db-cache"

# Read env vars through Shlink command, so that it applies the `_FILE` env var fallback logic
GEOLITE_LICENSE_KEY=$(bin/cli env-var:read GEOLITE_LICENSE_KEY)
SKIP_INITIAL_GEOLITE_DOWNLOAD=$(bin/cli env-var:read SKIP_INITIAL_GEOLITE_DOWNLOAD)
INITIAL_API_KEY=$(bin/cli env-var:read INITIAL_API_KEY)

# Skip downloading GeoLite2 db file if the license key env var was not defined or skipping was explicitly set
if [ -z "${GEOLITE_LICENSE_KEY}" ] || [ "${SKIP_INITIAL_GEOLITE_DOWNLOAD}" = "true" ]; then
  flags="${flags} --skip-download-geolite"
fi

# If INITIAL_API_KEY was provided, create an initial API key
if [ -n "${INITIAL_API_KEY}" ]; then
  flags="${flags} --initial-api-key=${INITIAL_API_KEY}"
fi

php vendor/bin/shlink-installer init ${flags}

if [ "$SHLINK_RUNTIME" = 'rr' ]; then
  ./bin/rr serve -c config/roadrunner/.rr.yml
fi
