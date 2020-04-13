# Shlink Docker image

[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)

This image provides an easy way to set up [shlink](https://shlink.io) on a container-based runtime.

It exposes a shlink instance served with [swoole](https://www.swoole.co.uk/), which persists data in a local [sqlite](https://www.sqlite.org/index.html) database.

## Usage

Shlink docker image exposes port `8080` in order to interact with its HTTP interface.

It also expects these two env vars to be provided, in order to properly generate short URLs at runtime.

* `SHORT_DOMAIN_HOST`: The custom short domain used for this shlink instance. For example **doma.in**.
* `SHORT_DOMAIN_SCHEMA`: Either **http** or **https**.

So based on this, to run shlink on a local docker service, you should run a command like this:

```bash
docker run --name shlink -p 8080:8080 -e SHORT_DOMAIN_HOST=doma.in -e SHORT_DOMAIN_SCHEMA=https shlinkio/shlink:stable
```

### Interact with shlink's CLI on a running container.

Once the shlink container is running, you can interact with the CLI tool by running `shlink` with any of the supported commands.

For example, if the container is called `shlink_container`, you can generate a new API key with:

```bash
docker exec -it shlink_container shlink api-key:generate
```

Or you can list all tags with:

```bash
docker exec -it shlink_container shlink tag:list
```

Or locate remaining visits with:

```bash
docker exec -it shlink_container shlink visit:locate
```

All shlink commands will work the same way.

You can also list all available commands just by running this:

```bash
docker exec -it shlink_container shlink
```

## Use an external DB

The image comes with a working sqlite database, but in production you will probably want to usa a distributed database.

It is possible to use a set of env vars to make this shlink instance interact with an external MySQL, MariaDB, PostgreSQL or Microsoft SQL Server database.

* `DB_DRIVER`: **[Mandatory]**. Use the value **mysql**, **maria**, **postgres** or **mssql** to prevent the sqlite database to be used.
* `DB_NAME`: [Optional]. The database name to be used. Defaults to **shlink**.
* `DB_USER`: **[Mandatory]**. The username credential for the database server.
* `DB_PASSWORD`: **[Mandatory]**. The password credential for the database server.
* `DB_HOST`: **[Mandatory]**. The host name of the server running the database engine.
* `DB_PORT`: [Optional]. The port in which the database service is running.
    * Default value is based on the value provided for `DB_DRIVER`:
        * **mysql** or **maria** -> `3306`
        * **postgres** -> `5432`
        * **mssql** -> `1433`

> PostgreSQL is supported since v1.16.1 and Microsoft SQL server since v2.1.0. Do not try to use them with previous versions.

Taking this into account, you could run shlink on a local docker service like this:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e SHORT_DOMAIN_HOST=doma.in \
    -e SHORT_DOMAIN_SCHEMA=https \
    -e DB_DRIVER=mysql \
    -e DB_USER=root \
    -e DB_PASSWORD=123abc \
    -e DB_HOST=something.rds.amazonaws.com \
    shlinkio/shlink:stable
```

You could even link to a local database running on a different container:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    [...] \
    -e DB_HOST=some_mysql_container \
    --link some_mysql_container \
    shlinkio/shlink:stable
```

> If you have considered using SQLite but sharing the database file with a volume, read [this issue](https://github.com/shlinkio/shlink-docker-image/issues/40) first.

## Other integrations

### Use an external redis server

If you plan to run more than one Shlink instance, there are some resources that should be shared ([Multi instance considerations](#multi-instance-considerations)).

One of those resources are the locks Shlink generates to prevent some operations to be run more than once in parallel (in the future, these redis servers could be used for other caching operations).

In order to share those locks, you should use an external redis server (or a cluster of redis servers), by providing the `REDIS_SERVERS` env var.

It can be either one server name or a comma-separated list of servers.

> If more than one redis server is provided, Shlink will expect them to be configured as a [redis cluster](https://redis.io/topics/cluster-tutorial).

### Integrate with a mercure hub server

One way to get real time updates when certain events happen in Shlink is by integrating it with a [mercure hub](https://mercure.rocks/) server.

If you do that, Shlink will publish updates and other clients can subscribe to those.

There are three env vars you need to provide if you want to enable this:

* `MERCURE_PUBLIC_HUB_URL`: **[Mandatory]**. The public URL of a mercure hub server to which Shlink will sent updates. This URL will also be served to consumers that want to subscribe to those updates.
* `MERCURE_INTERNAL_HUB_URL`: **[Optional]**. An internal URL for a mercure hub. Will be used only when publishing updates to mercure, and does not need to be public. If this is not provided, the `MERCURE_PUBLIC_HUB_URL` one will be used to publish updates.
* `MERCURE_JWT_SECRET`: **[Mandatory]**. The secret key that was provided to the mercure hub server, in order to be able to generate valid JWTs for publishing/subscribing to that server.

So in order to run shlink with mercure integration, you would do it like this:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e SHORT_DOMAIN_HOST=doma.in \
    -e SHORT_DOMAIN_SCHEMA=https \
    -e "MERCURE_PUBLIC_HUB_URL=https://example.com"
    -e "MERCURE_INTERNAL_HUB_URL=http://my-mercure-hub.prod.svc.cluster.local"
    -e MERCURE_JWT_SECRET=super_secret_key
    shlinkio/shlink:stable
```

## All supported env vars

A few env vars have been already used in previous examples, but this image supports others that can be used to customize its behavior.

This is the complete list of supported env vars:

* `SHORT_DOMAIN_HOST`: The custom short domain used for this shlink instance. For example **doma.in**.
* `SHORT_DOMAIN_SCHEMA`: Either **http** or **https**.
* `DB_DRIVER`: **sqlite** (which is the default value), **mysql**, **maria**, **postgres** or **mssql**.
* `DB_NAME`: The database name to be used when using an external database driver. Defaults to **shlink**.
* `DB_USER`: The username credential to be used when using an external database driver.
* `DB_PASSWORD`: The password credential to be used when using an external database driver.
* `DB_HOST`: The host name of the database server  when using an external database driver.
* `DB_PORT`: The port in which the database service is running when using an external database driver.
    * Default value is based on the value provided for `DB_DRIVER`:
        * **mysql** or **maria** -> `3306`
        * **postgres** -> `5432`
        * **mssql** -> `1433`
* `DISABLE_TRACK_PARAM`: The name of a query param that can be used to visit short URLs avoiding the visit to be tracked. This feature won't be available if not value is provided.
* `DELETE_SHORT_URL_THRESHOLD`: The amount of visits on short URLs which will not allow them to be deleted. Defaults to `15`.
* `VALIDATE_URLS`: Boolean which tells if shlink should validate a status 20x is returned (after following redirects) when trying to shorten a URL. Defaults to `false`.
* `INVALID_SHORT_URL_REDIRECT_TO`: If a URL is provided here, when a user tries to access an invalid short URL, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `REGULAR_404_REDIRECT_TO`: If a URL is provided here, when a user tries to access a URL not matching any one supported by the router, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `BASE_URL_REDIRECT_TO`: If a URL is provided here, when a user tries to access Shlink's base URL, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `BASE_PATH`: The base path from which you plan to serve shlink, in case you don't want to serve it from the root of the domain. Defaults to `''`.
* `WEB_WORKER_NUM`: The amount of concurrent http requests this shlink instance will be able to server. Defaults to 16.
* `TASK_WORKER_NUM`: The amount of concurrent background tasks this shlink instance will be able to execute. Defaults to 16.
* `VISITS_WEBHOOKS`: A comma-separated list of URLs that will receive a `POST` request when a short URL receives a visit.
* `DEFAULT_SHORT_CODES_LENGTH`: The length you want generated short codes to have. It defaults to 5 and has to be at least 4, so any value smaller than that will fall back to 4.
* `REDIS_SERVERS`: A comma-separated list of redis servers where Shlink locks are stored (locks are used to prevent some operations to be run more than once in parallel).
* `MERCURE_PUBLIC_HUB_URL`: The public URL of a mercure hub server to which Shlink will sent updates. This URL will also be served to consumers that want to subscribe to those updates.
* `MERCURE_INTERNAL_HUB_URL`: An internal URL for a mercure hub. Will be used only when publishing updates to mercure, and does not need to be public. If this is not provided but `MERCURE_PUBLIC_HUB_URL` was, the former one will be used to publish updates.
* `MERCURE_JWT_SECRET`: The secret key that was provided to the mercure hub server, in order to be able to generate valid JWTs for publishing/subscribing to that server.

An example using all env vars could look like this:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e SHORT_DOMAIN_HOST=doma.in \
    -e SHORT_DOMAIN_SCHEMA=https \
    -e DB_DRIVER=mysql \
    -e DB_NAME=shlink \
    -e DB_USER=root \
    -e DB_PASSWORD=123abc \
    -e DB_HOST=something.rds.amazonaws.com \
    -e DB_PORT=3306 \
    -e DISABLE_TRACK_PARAM="no-track" \
    -e DELETE_SHORT_URL_THRESHOLD=30 \
    -e VALIDATE_URLS=true \
    -e "INVALID_SHORT_URL_REDIRECT_TO=https://my-landing-page.com" \
    -e "REGULAR_404_REDIRECT_TO=https://my-landing-page.com" \
    -e "BASE_URL_REDIRECT_TO=https://my-landing-page.com" \
    -e "REDIS_SERVERS=tcp://172.20.0.1:6379,tcp://172.20.0.2:6379" \
    -e "BASE_PATH=/my-campaign" \
    -e WEB_WORKER_NUM=64 \
    -e TASK_WORKER_NUM=32 \
    -e "VISITS_WEBHOOKS=http://my-api.com/api/v2.3/notify,https://third-party.io/foo" \
    -e DEFAULT_SHORT_CODES_LENGTH=6 \
    -e "MERCURE_PUBLIC_HUB_URL=https://example.com"
    -e "MERCURE_INTERNAL_HUB_URL=http://my-mercure-hub.prod.svc.cluster.local"
    -e MERCURE_JWT_SECRET=super_secret_key
    shlinkio/shlink:stable
```

## Provide config via volumes

Rather than providing custom configuration via env vars, it is also possible ot provide config files in json format.

Mounting a volume at `config/params` you will make shlink load all the files on it with the `.config.json` suffix.

The whole configuration should have this format, but it can be split into multiple files that will be merged:

```json
{
    "disable_track_param": "my_param",
    "delete_short_url_threshold": 30,
    "short_domain_schema": "https",
    "short_domain_host": "doma.in",
    "validate_url": true,
    "invalid_short_url_redirect_to": "https://my-landing-page.com",
    "regular_404_redirect_to": "https://my-landing-page.com",
    "base_url_redirect_to": "https://my-landing-page.com",
    "base_path": "/my-campaign",
    "web_worker_num": 64,
    "task_worker_num": 32,
    "default_short_codes_length": 6,
    "redis_servers": [
        "tcp://172.20.0.1:6379",
        "tcp://172.20.0.2:6379"
    ],
    "visits_webhooks": [
        "http://my-api.com/api/v2.3/notify",
        "https://third-party.io/foo"
    ],
    "db_config": {
        "driver": "pdo_mysql",
        "dbname": "shlink",
        "user": "root",
        "password": "123abc",
        "host": "something.rds.amazonaws.com",
        "port": "3306"
    },
    "mercure_public_hub_url": "https://example.com",
    "mercure_internal_hub_url": "http://my-mercure-hub.prod.svc.cluster.local",
    "mercure_jwt_secret": "super_secret_key"
}
```

> This is internally parsed to how shlink expects the config. If you are using a version previous to 1.17.0, this parser is not present and you need to provide a config structure like the one [documented previously](https://github.com/shlinkio/shlink-docker-image/tree/v1.16.3#provide-config-via-volumes).

Once created just run shlink with the volume:

```bash
docker run --name shlink -p 8080:8080 -v ${PWD}/my/config/dir:/etc/shlink/config/params shlinkio/shlink:stable
```

## Multi instance considerations

These are some considerations to take into account when running multiple instances of shlink.

* Some operations performed by Shlink should never be run more than once at the same time (like creating the database for the first time, or downloading the GeoLite2 database). For this reason, Shlink uses a locking system.

    However, these locks are locally scoped to each Shlink instance by default.

    You can (and should) make the locks to be shared by all Shlink instances by using a redis server/cluster. Just define the `REDIS_SERVERS` env var with the list of servers.

## Versions

Versioning on this docker image works as follows:

* `X.X.X`:  when providing a specific version number, the image version will match the shlink version it contains. For example, installing `shlinkio/shlink:1.15.0`, you will get an image containing shlink v1.15.0.
* `stable`: always holds the latest stable tag. For example, if latest shlink version is 2.0.0, installing `shlinkio/shlink:stable`, you will get an image containing shlink v2.0.0
* `latest`: always holds the latest contents in master, and it's considered unstable and not suitable for production.

> **Important**: The docker image was introduced with shlink v1.15.0, so there are no official images previous to that versions.
