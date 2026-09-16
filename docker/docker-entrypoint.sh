#!/usr/bin/env sh
set -e

cd /etc/shlink

# Create data directories if they do not exist. This allows data dir to be mounted as an empty dir if needed
mkdir -p data/cache data/locks data/log data/proxies data/temp-geolite

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
  # RoadRunner resolves `num_workers: 0` against the number of CPUs the HOST
  # reports, which is unaffected by any CPU limit set on the container. On a
  # many-core host that starts dozens of workers which can never run
  # concurrently, at roughly 50-60MB each, so the container idles far above the
  # memory it needs and is eventually killed under load.
  #
  # When the container has a CPU limit, derive the defaults from that instead.
  # Without a limit, nothing changes.
  container_cpus=''
  if [ -r /sys/fs/cgroup/cpu.max ]; then                 # cgroup v2
    read -r cpu_quota cpu_period < /sys/fs/cgroup/cpu.max
  elif [ -r /sys/fs/cgroup/cpu/cpu.cfs_quota_us ]; then  # cgroup v1
    cpu_quota=$(cat /sys/fs/cgroup/cpu/cpu.cfs_quota_us)
    cpu_period=$(cat /sys/fs/cgroup/cpu/cpu.cfs_period_us)
  fi
  if [ -n "${cpu_quota}" ] && [ "${cpu_quota}" != 'max' ] && [ "${cpu_quota}" -gt 0 ] 2>/dev/null \
     && [ -n "${cpu_period}" ] && [ "${cpu_period}" -gt 0 ] 2>/dev/null; then
    # Round up, so a fractional limit still gets one worker rather than none.
    container_cpus=$(( (cpu_quota + cpu_period - 1) / cpu_period ))
  fi

  if [ -n "${container_cpus}" ]; then
    # A floor of 2 web workers, so a single slow request cannot block the
    # whole container when the limit is one CPU.
    [ -z "${WEB_WORKER_NUM}" ] && export WEB_WORKER_NUM=$(( container_cpus > 2 ? container_cpus : 2 ))
    [ -z "${TASK_WORKER_NUM}" ] && export TASK_WORKER_NUM="${container_cpus}"
  fi

  # Run with `exec` so that signals are properly handled
  exec ./bin/rr serve -c config/roadrunner/.rr.yml
fi
