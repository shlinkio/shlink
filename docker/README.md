# Shlink Docker image

[![Docker build status](https://img.shields.io/github/workflow/status/shlinkio/shlink/Build%20docker%20image?logo=docker&style=flat-square)](https://github.com/shlinkio/shlink/actions?query=workflow%3A%22Build+docker+image%22)
[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?logo=docker&style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)

This image provides an easy way to set up [shlink](https://shlink.io) on a container-based runtime.

It exposes a shlink instance served with [swoole](https://www.swoole.co.uk/), which can be linked to external databases to persist data.

## Usage

The most basic way to run Shlink's docker image is by providing these mandatory env vars.

* `SHORT_DOMAIN_HOST`: The custom short domain used for this shlink instance. For example **doma.in**.
* `SHORT_DOMAIN_SCHEMA`: Either **http** or **https**.
* `GEOLITE_LICENSE_KEY`: Your GeoLite2 license key. [Learn more](https://shlink.io/documentation/geolite-license-key/) about this.

To run shlink on top of a local docker service, and using an internal SQLite database, do the following:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e SHORT_DOMAIN_HOST=doma.in \
    -e SHORT_DOMAIN_SCHEMA=https \
    -e GEOLITE_LICENSE_KEY=kjh23ljkbndskj345 \
    shlinkio/shlink:stable
```

## Full documentation

All the features supported by Shlink are also supported by the docker image.

If you want to learn more, visit the [full documentation](https://shlink.io/documentation/install-docker-image/).
