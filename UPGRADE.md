# Upgrading

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
