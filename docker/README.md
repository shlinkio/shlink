# Shlink Docker image

[![Docker build status](https://img.shields.io/github/actions/workflow/status/shlinkio/shlink/ci-docker-image-build.yml?logo=docker&style=flat-square)](https://github.com/shlinkio/shlink/actions/workflows/ci-docker-image-build.yml)
[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?logo=docker&style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)

This image provides an easy way to set up [shlink](https://shlink.io) on a container-based runtime.

It exposes a shlink instance served with [RoadRunner](https://roadrunner.dev), which can be linked to external databases to persist data.

## Image variants

* **Alpine** (default): `stable-alpine`, `latest-alpine`, and `<version>-alpine`. Existing `stable`, `latest`, and `<version>` tags remain Alpine-based aliases.
* **Debian Trixie**: `stable-debian`, `latest-debian`, and `<version>-debian`.

RoadRunner aliases are also available: `<tag>-roadrunner-alpine` and `<tag>-roadrunner-debian`, where `<tag>` is `stable`, `latest`, or a version. Existing `<tag>-roadrunner` aliases remain Alpine-based.

`latest` tracks every `v*` release-tag push, including prereleases. `stable` is updated only when the tag contains none of `alpha`, `beta`, or `rc`. These rules apply to both variants and their aliases.

Both variants support `linux/amd64` and `linux/arm64/v8`, use the same configuration and entrypoint, and run as user `1001`. Debian includes the SQL Server driver on both architectures; Alpine includes it on amd64 only.

To use Debian, replace `shlinkio/shlink:stable` in the example below with `shlinkio/shlink:stable-debian`.

## Usage

The most basic way to run Shlink's docker image is by providing these mandatory env vars.

* `DEFAULT_DOMAIN`: The default short domain used for this shlink instance. For example **s.test**.
* `IS_HTTPS_ENABLED`: Either **true** or **false**. Tells if Shlink is being served with HTTPs or not.
* `GEOLITE_LICENSE_KEY`: Your GeoLite2 license key. [Learn more](https://shlink.io/documentation/geolite-license-key/) about this.

To run shlink on top of a local docker service, and using an internal SQLite database, do the following:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e DEFAULT_DOMAIN=s.test \
    -e IS_HTTPS_ENABLED=true \
    -e GEOLITE_LICENSE_KEY=kjh23ljkbndskj345 \
    shlinkio/shlink:stable
```

## Building locally

Each variant has its own Dockerfile under `docker/alpine/` or `docker/debian/`. Both use the repository root as their build context and share `docker/docker-entrypoint.sh` and `docker/config/php.ini`.

Run these commands from the repository root:

```bash
docker build -f docker/alpine/Dockerfile -t shlink:alpine .
docker build -f docker/debian/Dockerfile -t shlink:debian .
```

The `Test docker image build` workflow builds both variants for both architectures on pull requests that change `docker/**`, `.dockerignore`, `composer.json`, or either Docker workflow. This extends the previous root-Dockerfile-only filter to cover the relocated recipes and shared container files.

Both Docker workflows reuse `shlinkio/github-actions` and require its Dockerfile-selection input. The separate `Build and publish docker image` workflow runs on `v*` release-tag pushes. Each tag family is built for both architectures and published to Docker Hub and GHCR. Pull-request builds do not log in to registries or publish images.

## Full documentation

All the features supported by Shlink are also supported by the docker image.

If you want to learn more, visit the [full documentation](https://shlink.io/documentation/install-docker-image/).
