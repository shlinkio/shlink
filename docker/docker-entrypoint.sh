#!/usr/bin/env sh
set -e

cd /etc/shlink

# Create data directories if they do not exist. This allows data dir to be mounted as an empty dir if needed
mkdir -p data/cache data/locks data/log data/proxies

flags="--no-interaction --clear-db-cache"

# Read env vars through Shlink command, so that it applies the `_FILE` env var fallback logic
geolite_license_key=$(bin/cli env-var:read GEOLITE_LICENSE_KEY)
skip_initial_geolite_download=$(bin/cli env-var:read SKIP_INITIAL_GEOLITE_DOWNLOAD)
initial_api_key=$(bin/cli env-var:read INITIAL_API_KEY)

# Skip downloading GeoLite2 db file if the license key env var was not defined or skipping was explicitly set
if [ -z "${geolite_license_key}" ] || [ "${skip_initial_geolite_download}" = "true" ]; then
  flags="${flags} --skip-download-geolite"
fi

# If INITIAL_API_KEY was provided, create an initial API key
if [ -n "${initial_api_key}" ]; then
  flags="${flags} --initial-api-key=${initial_api_key}"
fi

php vendor/bin/shlink-installer init ${flags}

if [ "$SHLINK_RUNTIME" = 'rr' ]; then
  # Run with `exec` so that signals are properly handled
  exec ./bin/rr serve -c config/roadrunner/.rr.yml
fi
