# Upgrading

## From v4.x to v5.x

### General

* Generating QR codes by appending `/qr-code` to a short URL is no longer possible. Use external services to generate QR codes from a short URL, or the logic embedded in Shlink Web Client and Shlink Dashboard.
* Shlink no longer tries to detect trusted proxies automatically, when resolving the visitor's IP address.
    Instead, if you have more than 1 proxy in front of Shlink, you should provide `TRUSTED_PROXIES` env var, with either a comma-separated list of the IP addresses of your proxies, or a number indicating how many proxies are there in front of Shlink.
* PHP 8.3 is no longer supported. Only 8.4 and 8.5 are officially supported as of Shlink 5.0.0.

### Changes in CLI

* Disabling API keys by their plain-text key is no longer supported. When calling `api-key:disable`, the first argument is now always assumed to be the name.
* All visits-related commands (`short-url:visits`, `tag:visits`, `domain:visits`, `visit:orphan` and `visit:non-orphan`) now return more information, and columns are arranged slightly differently.
* The `short-url:list` command no longer accepts `--including-all-tags` and `--show-api-key-name` options. Use `--tags-all` and `--show-api-key` instead.
* The `short-url:list` command no longer allows ordering using the `--order-by=field,dir` format. Use `--order-by=field-dir` instead.
* All commands which used to accept the `--tags` flag, no longer accept it. Pass `--tag` multiple times instead, one per tag.

### Changes in env vars

* The `REDIRECT_APPEND_EXTRA_PATH` env var is no longer supported. Use `REDIRECT_EXTRA_PATH_MODE=append` to enable the same behavior.

## From v3.x to v4.x

### General

* Swoole and Openswoole are no longer officially supported runtimes. The recommended alternative is RoadRunner.
* Dist files for swoole/openswoole are no longer published.
* Webhooks are no longer supported. Migrate to one of the other [real-time updates](https://shlink.io/documentation/advanced/real-time-updates/) mechanisms.
* When using RoadRunner, the amount of web workers, task workers and the port number can no longer be provided via config options. Use `WEB_WORKER_NUM`, `TASK_WORKER_NUM` and `PORT` env vars instead.

### Changes in URL shortener

* The short URLs `loosely` mode is no longer supported, as it was a typo. Use `loose` mode instead.
* QR codes URLs now work by default, even for short URLs that cannot be visited due to max visits or date range limitations.
  If you want to keep previous behavior, pass `QR_CODE_FOR_DISABLED_SHORT_URLS=false` or the equivalent configuration option.
* Long URL title resolution is now enabled by default. You can still disable it by passing `AUTO_RESOLVE_TITLES=false` or the equivalent configuration option.
* Shlink no longer allows to opt-in for long URL verification. Long URLs are unconditionally considered correct during short URL creation/edition.
* Device long URLs have been migrated to the new Dynamic rule-based redirects system and will continue to work as expected, but the API surface has changed.
  If you use shlink-web-client and rely on this feature when creating/updating short URLs, **DO NOT UPDATE YET**. Support for dynamic rule-based redirects will be added to shlink-web-client soon, in v4.1.0

### Changes in REST API

* REST API v1/v2 now behave like v3. This only affects error codes, which are now proper URIs.
  * `INVALID_ARGUMENT` -> `https://shlink.io/api/error/invalid-data`
  * `INVALID_SHORT_URL_DELETION` -> `https://shlink.io/api/error/invalid-short-url-deletion`
  * `DOMAIN_NOT_FOUND` -> `https://shlink.io/api/error/domain-not-found`
  * `FORBIDDEN_OPERATION` -> `https://shlink.io/api/error/forbidden-tag-operation`
  * `INVALID_SLUG` -> `https://shlink.io/api/error/non-unique-slug`
  * `INVALID_SHORTCODE` -> `https://shlink.io/api/error/short-url-not-found`
  * `TAG_CONFLICT` -> `https://shlink.io/api/error/tag-conflict`
  * `TAG_NOT_FOUND` -> `https://shlink.io/api/error/tag-not-found`
  * `MERCURE_NOT_CONFIGURED` -> `https://shlink.io/api/error/mercure-not-configured`
  * `INVALID_AUTHORIZATION` -> `https://shlink.io/api/error/missing-authentication`
  * `INVALID_API_KEY` -> `https://shlink.io/api/error/invalid-api-key`
* Endpoints previously returning props like `"visitsCount": {number}` no longer do it. There should be an alternative `"visitsSummary": {}` object with the amount nested on it.
* It is no longer possible to order the short URLs list with `orderBy=visitsCount-ASC`/`orderBy=visitsCount-DESC`. Use `orderBy=visits-ASC`/`orderBy=visits-DESC` instead.
* It is no longer possible to get tags with stats using `GET /tags?withStats=true`. Use `GET /tags/stats` endpoint instead.
* The `deviceLongUrls` are ignored when calling `POST /short-urls` or `PATCH /short-urls/{shortCode}`. These should now be configured as dynamic rule-based redirects via `POST /short-urls/{shortCode}/redirect-rules`.

### Changes in Docker image

* Since openswoole is no longer supported, there are no longer image tags suffixed with `openswoole`. You should migrate to the default or `roadrunner` ones.
* The `non-root` docker tag is no longer published, as all docker images are now running without super-user permissions.
* Due to previous point, it is no longer possible to pass `ENABLE_PERIODIC_VISIT_LOCATE=true` in order to configure a cron job that locates visits periodically.
  This was not really needed in the docker image, as visits are located on the fly.

### Changes in integrations

* Credentials in redis URLs should now be URL-encoded, as they are unconditionally url-decoded before being used. Previously, it was possible to customize this behavior via `REDIS_DECODE_CREDENTIALS=true|false`.
* Providing redis URIs in the form of `tcp://password@6.6.6.6:6379` is no longer supported. If you want to provide password with no username, do `tcp://:password@6.6.6.6:6379` instead.

## From v2.x to v3.x

### Changes in REST API

* The `type` property returned when trying to delete a URL that reached the visits threshold, when using the `DELETE /short-urls/{shortCode}` endpoint, is now `INVALID_SHORT_URL_DELETION` instead of `INVALID_SHORTCODE_DELETION`.
* The `INVALID_AUTHORIZATION` error no longer includes the `expectedTypes` property. Use `expectedHeaders` one instead.
* The `GET /rest/v2/short-urls` endpoint no longer allows ordering by `visitsCount`, `visitCount` or `originalUrl`. Use `visits` instead of the first two, and `longUrl` instead of the last one.
* The `GET /rest/v2/short-urls` endpoint no longer allows providing the ordering params with array notation, as in `/shortUrls?orderBy[longUrl]=DESC`. Instead, use the following notation `/shortUrls?orderBy=longUrl-DESC`.
* The `GET /rest/v2/short-urls` endpoint now has a default ordering of newest-to-oldest. Use `/shortUrls?orderBy=dateCreated-ASC` in order to keep the oldest-to-newest behavior.
* Requests expecting a body no longer support url-encoded payloads. Instead, always use JSON bodies with `Content-Type: application/json`.
* The next endpoints have been removed:
  * `PUT /rest/v2/short-urls/{shortCode}/tags`: Use the `PATCH /rest/v2/short-urls/{shortCode}` endpoint to set the short URL tags.
  * `POST /rest/v2/tags`: Use `POST /rest/v2/short-urls` or `PATCH /rest/v2/short-urls/{shortCodes}` to create new tags already attached to a short URL. Creating orphan tags makes no sense.

### Changes in CLI

* The next commands have been removed:
  * `short-url:generate`: Use `short-url:create` instead.
  * `tag:create`: Creating orphan tags makes no sense.
* Params in camelCase format are no longer supported. They all have an equivalent kebab-case replacement. (for example, from `--startDate` to `--start-date`).
* The `short-url:create` command no longer accepts the `--no-validate-url` flag. Now URLs are never validated, unless `--validate-url` is passed.
* The CLI installer tool entry-points have changed.
  * `bin/install`: replaced by `vendor/bin/shlink-installer install`
  * `bin/update`: replaced by `vendor/bin/shlink-installer update`
  * `bin/set-option`: replaced by `vendor/bin/shlink-installer set-option`

### Changes in config

* The next env vars have been removed:
  * `INVALID_SHORT_URL_REDIRECT_TO`: Replaced by `DEFAULT_INVALID_SHORT_URL_REDIRECT`.
  * `REGULAR_404_REDIRECT_TO`: Replaced by `DEFAULT_REGULAR_404_REDIRECT`.
  * `BASE_URL_REDIRECT_TO`: Replaced by `DEFAULT_BASE_URL_REDIRECT`.
  * `SHORT_DOMAIN_HOST`: Replaced by `DEFAULT_DOMAIN`.
  * `SHORT_DOMAIN_SCHEMA`: Replaced by `IS_HTTPS_ENABLED`.
  * `USE_HTTPS`: Replaced by `IS_HTTPS_ENABLED`.
  * `VALIDATE_URLS`: There's no replacement. URLs are not validated, unless explicitly requested during creation or edition.
* The next env vars behavior has changed:
  * `DELETE_SHORT_URL_THRESHOLD`: Now, if this env var is not provided, the "visits threshold" won't be checked at all when deleting short URLs. Make sure you explicitly provide a value if you want to enable this feature.
* Environment variables now have precedence over configuration set via the installer tool.

### Other changes

* A default GeoLite2 license key is no longer provided. If you don't provide your own as explained in [the docs](https://shlink.io/documentation/geolite-license-key/), Shlink will not try to update the file anymore.
* The docker image no longer accepts providing configuration via json files mounted in the `config/params` folder. Only env vars are supported now.
* If you were manually serving Shlink with swoole, the entry script has to be changed from `/path/to/shlink/vendor/bin/mezzio-swoole start` to `/path/to/shlink/vendor/bin/laminas mezzio:swoole:start`
* The `GET /{shortCode}/qr-code/{size}` url has been removed. Use `GET /{shortCode}/qr-code?size={size}` instead.
* Regular swoole extension is no longer supported. Use openswoole instead, as a direct replacement. In most of the cases you just need to uninstall one and install the other, the rest is transparent.

## From v1.x to v2.x

### PHP 7.4 required

This new version takes advantage of several new features introduced in PHP 7.4.

Thanks to that, the code is more reliable and robust, and easier to maintain and improve.

However, that means that any previous PHP version is no longer supported.

### Preview generation

The ability to generate website previews has been completely removed and has no replacement.

The feature never properly worked, and it wasn't really useful. Because of that, the feature is no longer available on Shlink 2.x

Removing this feature has these implications:

* The `short-url:process-previews` CLI command no longer exists, and an error will be thrown if executed.
* The `/{shortCode}/preview` path is no longer valid, and will return a 404 status.

### Removed paths

These routes have been removed, but have a direct replacement:

* `/qr/{shortCode}[/{size}]` -> `/{shortCode}/qr-code[/{size}]`
* `PUT /rest/v{version}/short-urls/{shortCode}` -> `PATCH /rest/v{version}/short-urls/{shortCode}`

When using the old ones, a 404 status will be returned now.

### Removed command and route aliases

All the aliases for the CLI commands in the `short-urls` namespace have been removed. If you were using any of those commands with the `shortcode` or `short-code` prefixes, make sure to update them to use the `short-urls` prefix instead.

The same happens for all REST endpoints starting with `/short-code`. They were previously aliased to `/short-urls` ones, but they will return a 404 now. Make sure to update them accordingly.

### JWT authentication removed

Shlink's REST API no longer accepts authentication using a JWT token. The API key has to be passed now in the `x-api-key` header.

Removing this feature has these implications:

* Shlink will no longer introspect the `Authorization` header for Bearer tokens.
* The `POST /rest/v{version}/authenticate` endpoint no longer exists and will return a 404.

### API version is now required

Endpoints need to provide a version in the path now. Previously, not providing a version used to fall back to v1. Now, it will return a 404 status, as no route will match.

The only exception is the `/rest/health` endpoint, which will continue working without the version.

### API errors

Shlink v1.21.0 introduced support for API errors using the Problem Details format, as well as the v2 of the API.

For backwards compatibility reasons, requests performed to v1 continued to return the old `error` and `message` properties.

Starting with Shlink v2.0.0, both versions of the API will no longer return those two properties.

As a replacement, use `type` instead of `error`, and `detail` instead of `message`.

### Changes in models

The next REST API models have changed:

* **ShortUrl**: The `originalUrl` property was deprecated and has been removed. Use `longUrl` instead.
* **Visit**: The `remoteAddr` property was deprecated and has been removed. It has no replacement.
* **VisitLocation**: The `latitude` and `longitude` properties are no longer strings, but float.

### URL validation

Shlink can verify provided long URLs are valid before trying to shorten them. Starting with v2, it no longer does it by default and needs to be explicitly enabled instead of explicitly disabled.

### Removed config options

The `not_found_redirect_to` config option and the `NOT_FOUND_REDIRECT_TO` env var are no longer taken into consideration for the docker image.

Instead, use `invalid_short_url_redirect_to` and `INVALID_SHORT_URL_REDIRECT_TO` respectively.

### Migrated to Laminas

The project has been using Zend Framework components since the beginning. Since it has been re-branded as [Laminas](https://getlaminas.org/), this version updates to the new set of components.

Updating to Laminas components has these implications:

* If you were manually serving Shlink with swoole, the entry script has to be changed from `/path/to/shlink/vendor/bin/zend-expressive-swoole` to `/path/to/shlink/vendor/bin/mezzio-swoole`
