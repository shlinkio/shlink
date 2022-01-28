# Upgrading

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

When using the old ones, a 404 status will me returned now.

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
