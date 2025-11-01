# CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [4.6.0] - 2025-11-01
### Added
* [#2327](https://github.com/shlinkio/shlink/issues/2327) Allow filtering short URL lists by those not including certain tags.

    Now, the `GET /short-urls` endpoint accepts two new params: `excludeTags`, which is an array of strings with the tags that should not be included, and `excludeTagsMode`, which accepts the values `any` and `all`, and determines if short URLs should be filtered out if they contain any of the excluded tags, or all the excluded tags.

    Additionally, the `short-url:list` command also supports the same feature via `--exclude-tag` option, which requires a value and can be provided multiple times, and `--exclude-tags-all`, which does not expect a value and determines if the mode should be `all`, or `any`.

* [#2192](https://github.com/shlinkio/shlink/issues/2192) Allow filtering short URL lists by the API key that was used to create them.

  Now, the `GET /short-urls` endpoint accepts a new `apiKeyName` param, which is ignored if the request is performed with a non-admin API key which name does not match the one provided here.

  Additionally, the `short-url:list` command also supports the same feature via the `--api-key-name` option.

* [#2330](https://github.com/shlinkio/shlink/issues/2330) Add support to serve Shlink with FrankenPHP, by providing a worker script in `bin/frankenphp-worker.php`.

* [#2449](https://github.com/shlinkio/shlink/issues/2449) Add support to provide redis credentials separately when using redis sentinels, where provided servers are the sentinels and not the redis instances.

    For this, Shlink supports two new env ras / config options, as `REDIS_SERVERS_USER` and `REDIS_SERVERS_PASSWORD`.

* [#2498](https://github.com/shlinkio/shlink/issues/2498) Allow orphan visits, non-orphan visits and tag visits lists to be filtered by domain.

    This is done via the `domain` query parameter in API endpoints, and via the `--domain` option in console commands.

* [#2472](https://github.com/shlinkio/shlink/issues/2472) Add support for PHP 8.5

### Changed
* [#2424](https://github.com/shlinkio/shlink/issues/2424) Make simple console commands invokable.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [4.5.3] - 2025-10-10
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2488](https://github.com/shlinkio/shlink/issues/2488) Ensure `Access-Control-Allow-Credentials` is set in all cross-origin responses when `CORS_ALLOW_ORIGIN=true`.


## [4.5.2] - 2025-08-27
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2433](https://github.com/shlinkio/shlink/issues/2433) Try to mitigate memory leaks allowing RoadRunner to garbage collect memory after every request and every job, by setting `GC_COLLECT_CYCLES=true`.


## [4.5.1] - 2025-08-24
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2433](https://github.com/shlinkio/shlink/issues/2433) Try to mitigate memory leaks by restarting job and http workers every 250 executions when using RoadRunner.


## [4.5.0] - 2025-07-24
### Added
* [#2438](https://github.com/shlinkio/shlink/issues/2438) Add `MERCURE_ENABLED` env var and corresponding config option, to more easily allow the mercure integration to be toggled.

    For BC, if this env var is not present, we'll still consider the integration enabled if the `MERCURE_PUBLIC_HUB_URL` env var has a value. This is considered deprecated though, and next major version will rely only on `MERCURE_ENABLED`, so if you are using Mercure, make sure to set `MERCURE_ENABLED=true` to be ready.

* [#2387](https://github.com/shlinkio/shlink/issues/2387) Add `REAL_TIME_UPDATES_TOPICS` env var and corresponding config option, to granularly decide which real-time updates topics should be enabled.
* [#2418](https://github.com/shlinkio/shlink/issues/2418) Add more granular control over how Shlink handles CORS. It is now possible to customize the `Access-Control-Allow-Origin`, `Access-Control-Max-Age` and `Access-Control-Allow-Credentials` headers via env vars or config options.
* [#2386](https://github.com/shlinkio/shlink/issues/2386) Add new `any-value-query-param` and `valueless-query-param` redirect rule conditions.

    These new rules expand the existing `query-param`, which requires both a specific non-empty value in order to match the condition.

    The new conditions match as soon as a query param exists with any or no value (in the case of `any-value-query-param`), or if a query param exists with no value at all (in the case of `valueless-query-param`).

* [#2360](https://github.com/shlinkio/shlink/issues/2360) Add `TRUSTED_PROXIES` env var and corresponding config option, to configure a comma-separated list of all the proxies in front of Shlink, or simply the amount of trusted proxies in front of Shlink.

    This is important to properly detect visitor's IP addresses instead of incorrectly matching one of the proxy's IP address, and if provided, it disables a workaround introduced in https://github.com/shlinkio/shlink/pull/2359.

* [#2274](https://github.com/shlinkio/shlink/issues/2274) Add more supported device types for the `device` redirect condition:

    * `linux`: Will match desktop devices with Linux.
    * `windows`: Will match desktop devices with Windows.
    * `macos`: Will match desktop devices with MacOS.
    * `chromeos`: Will match desktop devices with ChromeOS.
    * `mobile`: Will match any mobile devices with either Android or iOS.

* [#2093](https://github.com/shlinkio/shlink/issues/2093) Add `REDIRECT_CACHE_LIFETIME` env var and corresponding config option, so that it is possible to set the `Cache-Control` visibility directive (`public` or `private`) when the `REDIRECT_STATUS_CODE` has been set to `301` or `308`.
* [#2323](https://github.com/shlinkio/shlink/issues/2323) Add `LOGS_FORMAT` env var and corresponding config option, to allow the logs generated by Shlink to be in console or JSON formats.

### Changed
* [#2406](https://github.com/shlinkio/shlink/issues/2406) Remove references to bootstrap from error templates, and instead inline the very minimum required styles.

### Deprecated
* [#2408](https://github.com/shlinkio/shlink/issues/2408) Generating QR codes via `/{short-code}/qr-code` is now deprecated and will be removed in Shlink 5.0. Use the equivalent capability from web clients instead.

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [4.4.6] - 2025-03-20
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2391](https://github.com/shlinkio/shlink/issues/2391) When sending visits to Matomo, send the country code, not the country name.
* Fix error with new option introduced by `endroid/qr-code` 6.0.4.


## [4.4.5] - 2025-03-01
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2373](https://github.com/shlinkio/shlink/issues/2373) Ensure deprecation warnings do not end up escalated to `ErrorException`s by `ProblemDetailsMiddleware`.

  In order to do this, Shlink will entirely ignore deprecation warnings when running in production, as those do not mean something is not working, but only that something will break in future versions.


## [4.4.4] - 2025-02-19
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2366](https://github.com/shlinkio/shlink/issues/2366) Fix error "Cannot use 'SCRIPT' with redis-cluster" thrown when creating a lock while using a redis cluster.
* [#2368](https://github.com/shlinkio/shlink/issues/2368) Fix error when listing non-orphan visits using API key with `AUTHORED_SHORT_URLS` role.


## [4.4.3] - 2025-02-15
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2351](https://github.com/shlinkio/shlink/issues/2351) Fix visitor IP address resolution when Shlink is served behind more than one reverse proxy.

  This regression was introduced due to a change in behavior in `akrabat/rka-ip-address-middleware`, that now picks the first address from the right after excluding all trusted proxies.

  Since Shlink does not set trusted proxies, this means the first IP from the right is now picked instead of the first from the left, so we now reverse the list before trying to resolve the IP.

  In the future, Shlink will allow you to define trusted proxies, to avoid other potential side effects because of this reversing of the list.

* [#2354](https://github.com/shlinkio/shlink/issues/2354) Fix error "NOSCRIPT No matching script. Please use EVAL" thrown when creating a lock in redis.
* [#2319](https://github.com/shlinkio/shlink/issues/2319) Fix unique index for `short_code` and `domain_id` in `short_urls` table not being used in Microsoft SQL engines for rows where `domain_id` is `null`.

## [4.4.2] - 2025-01-29
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2346](https://github.com/shlinkio/shlink/issues/2346) Get back docker images for ARM architectures.


## [4.4.1] - 2025-01-28
### Added
* [#2331](https://github.com/shlinkio/shlink/issues/2331) Add `ADDRESS` env var which allows to customize the IP address to which RoadRunner binds, when using the official docker image.

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2341](https://github.com/shlinkio/shlink/issues/2341) Ensure all asynchronous jobs that interact with the database do not leave idle connections open.
* [#2334](https://github.com/shlinkio/shlink/issues/2334) Improve how page titles are encoded to UTF-8, falling back from mbstring to iconv if available, and ultimately using the original title in case of error, but never causing the short URL creation to fail.


## [4.4.0] - 2024-12-27
### Added
* [#2265](https://github.com/shlinkio/shlink/issues/2265) Add a new `REDIRECT_EXTRA_PATH_MODE` option that accepts three values:

    * `default`: Short URLs only match if the path matches their short code or custom slug.
    * `append`: Short URLs are matched as soon as the path starts with the short code or custom slug, and the extra path is appended to the long URL before redirecting.
    * `ignore`: Short URLs are matched as soon as the path starts with the short code or custom slug, and the extra path is ignored.

    This option effectively replaces the old `REDIRECT_APPEND_EXTRA_PATH` option, which is now deprecated and will be removed in Shlink 5.0.0

* [#2156](https://github.com/shlinkio/shlink/issues/2156) Be less restrictive on what characters are disallowed in custom slugs.

    All [URI-reserved characters](https://datatracker.ietf.org/doc/html/rfc3986#section-2.2) were disallowed up until now, but from now on, only the gen-delimiters are.

* [#2229](https://github.com/shlinkio/shlink/issues/2229) Add `logo=disabled` query param to dynamically disable the default logo on QR codes.
* [#2206](https://github.com/shlinkio/shlink/issues/2206) Add new `DB_USE_ENCRYPTION` config option to enable SSL database connections trusting any server certificate.
* [#2209](https://github.com/shlinkio/shlink/issues/2209) Redirect rules are now imported when importing short URLs from a Shlink >=4.0 instance.

### Changed
* [#2281](https://github.com/shlinkio/shlink/issues/2281) Update docker image to PHP 8.4
* [#2124](https://github.com/shlinkio/shlink/issues/2124) Improve how Shlink decides if a GeoLite db file needs to be downloaded, and reduces the chances for API limits to be reached.

    Now Shlink tracks all download attempts, and knows which of them failed and succeeded. This lets it know when was the last error or success, how many consecutive errors have happened, etc.

    It also tracks now the reason for a download to be attempted, and the error that happened when one fails.

### Deprecated
* *Nothing*

### Removed
* [#2247](https://github.com/shlinkio/shlink/issues/2247) Drop support for PHP 8.2

### Fixed
* *Nothing*


## [4.3.1] - 2024-11-25
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2285](https://github.com/shlinkio/shlink/issues/2285) Fix performance degradation when using Microsoft SQL due to incorrect order of columns in `unique_short_code_plus_domain` index.


## [4.3.0] - 2024-11-24
### Added
* [#2159](https://github.com/shlinkio/shlink/issues/2159) Add support for PHP 8.4.
* [#2207](https://github.com/shlinkio/shlink/issues/2207) Add `hasRedirectRules` flag to short URL API model. This flag tells if a specific short URL has any redirect rules attached to it.
* [#1520](https://github.com/shlinkio/shlink/issues/1520) Allow short URLs list to be filtered by `domain`.

    This change applies both to the `GET /short-urls` endpoint, via the `domain` query parameter, and the `short-url:list` console command, via the `--domain`|`-d` flag.

* [#1774](https://github.com/shlinkio/shlink/issues/1774) Add new geolocation redirect rules for the dynamic redirects system.

    * `geolocation-country-code`: Allows to perform redirections based on the ISO 3166-1 alpha-2 two-letter country code resolved while geolocating the visitor.
    * `geolocation-city-name`: Allows to perform redirections based on the city name resolved while geolocating the visitor.

* [#2032](https://github.com/shlinkio/shlink/issues/2032) Save the URL to which a visitor is redirected when a visit is tracked.

    The value is exposed in the API as a new `redirectUrl` field for visit objects.

    This is useful to know where a visitor was redirected for a short URL with dynamic redirect rules, for special redirects, or simply in case the long URL was changed over time, and you still want to know where visitors were redirected originally.

    Some visits may not have a redirect URL if a redirect didn't happen, like for orphan visits when no special redirects are configured, or when a visit is tracked as part of the pixel action.

### Changed
* [#2193](https://github.com/shlinkio/shlink/issues/2193) API keys are now hashed using SHA256, instead of being saved in plain text.

    As a side effect, API key names have now become more important, and are considered unique.

    When people update to this Shlink version, existing API keys will be hashed for everything to continue working.

    In order to avoid data to be lost, plain-text keys will be written in the `name` field, either together with any existing name, or as the name itself. Then users are responsible for renaming them using the new `api-key:rename` command.

    For newly created API keys, it is recommended to provide a name, but if not provided, a name will be generated from a redacted version of the new API key.

* Update to Shlink PHP coding standard 2.4
* Update to `hidehalo/nanoid-php` 2.0
* Update to PHPStan 2.0

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2264](https://github.com/shlinkio/shlink/issues/2264) Fix visits counts not being deleted when deleting short URL or orphan visits.


## [4.2.5] - 2024-11-03
### Added
* *Nothing*

### Changed
* Update to Shlink PHP coding standard 2.4

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2244](https://github.com/shlinkio/shlink/issues/2244) Fix integration with Redis 7.4 and Valkey.


## [4.2.4] - 2024-10-27
### Added
* *Nothing*

### Changed
* [#2231](https://github.com/shlinkio/shlink/issues/2231) Update to `endroid/qr-code` 6.0.
* [#2221](https://github.com/shlinkio/shlink/issues/2221) Switch to env vars to handle dev/local options.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2232](https://github.com/shlinkio/shlink/issues/2232) Run RoadRunner in docker with `exec` to ensure signals are properly handled.


## [4.2.3] - 2024-10-17
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2225](https://github.com/shlinkio/shlink/issues/2225) Fix regression introduced in v4.2.2, making config options with `null` value to be promoted as env vars with value `''`, instead of being skipped.


## [4.2.2] - 2024-10-14
### Added
* *Nothing*

### Changed
* [#2208](https://github.com/shlinkio/shlink/issues/2208) Explicitly promote installer config options as env vars, instead of as a side effect of loading the app config.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2213](https://github.com/shlinkio/shlink/issues/2213) Fix spaces being replaced with underscores in query parameter names, when forwarded from short URL to long URL.
* [#2217](https://github.com/shlinkio/shlink/issues/2217) Fix docker image tag suffix being leaked to the version set inside Shlink, producing invalid SemVer version patterns.
* [#2212](https://github.com/shlinkio/shlink/issues/2212) Fix env vars read in docker entry point not properly falling back to their `_FILE` suffixed counterpart.


## [4.2.1] - 2024-10-04
### Added
* [#2183](https://github.com/shlinkio/shlink/issues/2183) Redis database index to be used can now be specified in the connection URI path, and Shlink will honor it.

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2201](https://github.com/shlinkio/shlink/issues/2201) Fix `MEMORY_LIMIT` option being ignored when provided via installer options.


## [4.2.0] - 2024-08-11
### Added
* [#2120](https://github.com/shlinkio/shlink/issues/2120) Add new IP address condition for the dynamic rules redirections system.

  The conditions allow you to define IP addresses to match as static IP (1.2.3.4), CIDR block (192.168.1.0/24) or wildcard pattern (1.2.\*.\*).

* [#2018](https://github.com/shlinkio/shlink/issues/2018) Add option to allow all short URLs to be unconditionally crawlable in robots.txt, via `ROBOTS_ALLOW_ALL_SHORT_URLS=true` env var, or config option.
* [#2109](https://github.com/shlinkio/shlink/issues/2109) Add option to customize user agents robots.txt, via `ROBOTS_USER_AGENTS=foo,bar,baz` env var, or config option.
* [#2163](https://github.com/shlinkio/shlink/issues/2163) Add `short-urls:edit` command to edit existing short URLs.

  This brings CLI and API interfaces capabilities closer, and solves an overlook since the feature was implemented years ago.

* [#2164](https://github.com/shlinkio/shlink/pull/2164) Add missing `--title` option to `short-url:create` and `short-url:edit` commands.

### Changed
* [#2096](https://github.com/shlinkio/shlink/issues/2096) Update to RoadRunner 2024.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [4.1.1] - 2024-05-23
### Added
* *Nothing*

### Changed
* Use new reusable workflow to publish docker image
* [#2015](https://github.com/shlinkio/shlink/issues/2015) Update to PHPUnit 11.
* [#2130](https://github.com/shlinkio/shlink/pull/2130) Replace deprecated `pugx/shortid-php` package with `hidehalo/nanoid-php`.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2111](https://github.com/shlinkio/shlink/issues/2111) Fix typo in OAS docs examples where redirect rules with `query-param` condition type were defined as `query`.
* [#2129](https://github.com/shlinkio/shlink/issues/2129) Fix error when resolving title for sites not using UTF-8 charset (detected with Japanese charsets).


## [4.1.0] - 2024-04-14
### Added
* [#1330](https://github.com/shlinkio/shlink/issues/1330) All visit-related endpoints now expose the `visitedUrl` prop for any visit.

  Previously, this was exposed only for orphan visits, since this can be an arbitrary value for those.

* [#2077](https://github.com/shlinkio/shlink/issues/2077) When sending visits to Matomo, the short URL title is now used as document title in matomo.
* [#2059](https://github.com/shlinkio/shlink/issues/2059) Add new `short-url:delete-expired` command that can be used to programmatically delete expired short URLs.

  Expired short URLs are those that have a `validUntil` date in the past, or optionally, that have reached the max amount of visits.

  This command can be run periodically by those who create many disposable URLs which are valid only for a period of time, and then can be deleted to save space.

* [#1925](https://github.com/shlinkio/shlink/issues/1925) Add new `integration:matomo:send-visits` console command that can be used to send existing visits to integrated Matomo instance.
* [#2087](https://github.com/shlinkio/shlink/issues/2087) Allow `memory_limit` to be configured via the new `MEMORY_LIMIT` env var or configuration option.

### Changed
* [#2034](https://github.com/shlinkio/shlink/issues/2034) Modernize entities, using constructor property promotion and readonly wherever possible.
* [#2036](https://github.com/shlinkio/shlink/issues/2036) Deep performance improvement in some endpoints which involve counting visits:

  * listing short URLs ordered by visits counts.
  * loading tags with stats.
  * visits overview.

  This has been achieved by introducing a new table which tracks slotted visits counts. We can then `SUM` all counts for certain short URL, avoiding `COUNT(visits)` aggregates which are much less performant when there are a lot of visits.

* [#2049](https://github.com/shlinkio/shlink/issues/2049) Request ID is now propagated to the background tasks/jobs scheduled during a request.

  This allows for a better traceability, as the logs generated during those jobs will have a matching UUID as the logs generated during the request the triggered the job.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2095](https://github.com/shlinkio/shlink/issues/2095) Fix custom slugs not being properly imported from bitly
* Fix error when importing short URLs and visits from a Shlink 4.x instance


## [4.0.3] - 2024-03-15
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2058](https://github.com/shlinkio/shlink/issues/2058) Fix DB credentials provided as env vars being casted to `int` if they include only numbers.
* [#2060](https://github.com/shlinkio/shlink/issues/2060) Fix error when trying to redirect to a non-http long URL.


## [4.0.2] - 2024-03-09
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2021](https://github.com/shlinkio/shlink/issues/2021) Fix infinite GeoLite2 downloads.


## [4.0.1] - 2024-03-08
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2041](https://github.com/shlinkio/shlink/issues/2041) Document missing `color` and `bgColor` params for the QR code route in the OAS docs.
* [#2043](https://github.com/shlinkio/shlink/issues/2043) Fix language redirect conditions matching too low quality accepted languages.


## [4.0.0] - 2024-03-03
### Added
* [#1914](https://github.com/shlinkio/shlink/issues/1914) Add new dynamic redirects engine based on rules. Rules are conditions checked against the visitor's request, and when matching, they can result in a redirect to a different long URL.

  Rules can be based on things like the presence of specific params, headers, locations, etc. This version ships with three initial rule condition types: device, query param and language.

* [#1902](https://github.com/shlinkio/shlink/issues/1902) Add dynamic redirects based on query parameters.

  This is implemented on top of the new [rule-based redirects](https://github.com/shlinkio/shlink/discussions/1912).

* [#1915](https://github.com/shlinkio/shlink/issues/1915) Add dynamic redirects based on accept language.

  This is implemented on top of the new [rule-based redirects](https://github.com/shlinkio/shlink/discussions/1912).

* [#1868](https://github.com/shlinkio/shlink/issues/1868) Add support for [docker compose secrets](https://docs.docker.com/compose/use-secrets/) to the docker image.
* [#1979](https://github.com/shlinkio/shlink/issues/1979) Allow orphan visits lists to be filtered by type.

  This is supported both by the `GET /visits/orphan` API endpoint via `type=...` query param, and by the `visit:orphan` CLI command, via `--type` flag.

* [#1904](https://github.com/shlinkio/shlink/issues/1904) Allow to customize QR codes foreground color, background color and logo.
* [#1884](https://github.com/shlinkio/shlink/issues/1884) Allow a path prefix to be provided during short URL creation.

  This can be useful to let Shlink generate partially random URLs, but with a known prefix.

  Path prefixes are validated and filtered taking multi-segment slugs into consideration, which means slashes are replaced with dashes as long as multi-segment slugs are disabled.

### Changed
* [#1935](https://github.com/shlinkio/shlink/issues/1935) Replace dependency on abandoned `php-middleware/request-id` with userland simple middleware.
* [#1988](https://github.com/shlinkio/shlink/issues/1988) Remove dependency on `league\uri` package.
* [#1909](https://github.com/shlinkio/shlink/issues/1909) Update docker image to PHP 8.3.
* [#1786](https://github.com/shlinkio/shlink/issues/1786) Run API tests with RoadRunner by default.
* [#2008](https://github.com/shlinkio/shlink/issues/2008) Update to Doctrine ORM 3.0.
* [#2010](https://github.com/shlinkio/shlink/issues/2010) Update to Symfony 7.0 components.
* [#2016](https://github.com/shlinkio/shlink/issues/2016) Simplify and improve how code coverage is generated in API and CLI tests.
* [#1674](https://github.com/shlinkio/shlink/issues/1674) Database columns persisting long URLs have now `TEXT` type, which allows for much longer values.

### Deprecated
* *Nothing*

### Removed
* [#1908](https://github.com/shlinkio/shlink/issues/1908) Remove support for openswoole (and swoole).

### Fixed
* [#2000](https://github.com/shlinkio/shlink/issues/2000) Fix short URL creation/edition getting stuck when trying to resolve the title of a long URL which never returns a response.


## [3.7.3] - 2024-01-04
### Added
* *Nothing*

### Changed
* [#1968](https://github.com/shlinkio/shlink/issues/1968) Move migrations from `data` to `module/Core`.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1967](https://github.com/shlinkio/shlink/issues/1967) Allow an empty dir to be mounted in `data` when using the docker image.


## [3.7.2] - 2023-12-26
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1960](https://github.com/shlinkio/shlink/issues/1960) Allow QR codes to be optionally resolved even when corresponding short URL is not enabled.


## [3.7.1] - 2023-12-17
### Added
* *Nothing*

### Changed
* Remove dependency on functional-php library
* [#1939](https://github.com/shlinkio/shlink/issues/1939) Fine-tune RoadRunner logs to avoid too many useless info.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1947](https://github.com/shlinkio/shlink/issues/1947) Fix error when importing short URLs while using Postgres.


## [3.7.0] - 2023-11-25
### Added
* [#1798](https://github.com/shlinkio/shlink/issues/1798) Experimental support to send visits to an external Matomo instance.
* [#1780](https://github.com/shlinkio/shlink/issues/1780) Add new `NO_ORPHAN_VISITS` API key role.

  Keys with this role will always get `0` when fetching orphan visits.

  When trying to delete orphan visits the result will also be `0` and no visits will actually get deleted.

* [#1879](https://github.com/shlinkio/shlink/issues/1879) Cache namespace can now be customized via config option or `CACHE_NAMESPACE` env var.

  This is important if you are running multiple Shlink instance on the same server, or they share the same Redis instance (even more so if they are on different versions).

* [#1905](https://github.com/shlinkio/shlink/issues/1905) Add support for PHP 8.3.
* [#1927](https://github.com/shlinkio/shlink/issues/1927) Allow redis credentials be URL-decoded before passing them to connection.
* [#1834](https://github.com/shlinkio/shlink/issues/1834) Add support for redis encrypted connections using SSL/TLS.

  Encryption should work out of the box if servers schema is set tp `tls` or `rediss`, including support for self-signed certificates.

  This has been tested with AWS ElasticCache using in-transit encryption, and with Digital Ocean Redis database.

* [#1906](https://github.com/shlinkio/shlink/issues/1906) Add support for RabbitMQ encrypted connections using SSL/TLS.

  In order to enable SLL, you need to pass `RABBITMQ_USE_SSL=true` or the corresponding config option.

  Connections using self-signed certificates should work out of the box.

  This has been tested with AWS RabbitMQ using in-transit encryption, and with CloudAMQP.

### Changed
* [#1799](https://github.com/shlinkio/shlink/issues/1799) RoadRunner/openswoole jobs are not run anymore for tasks that are actually disabled.

  For example, if you did not enable RabbitMQ real-time updates, instead of triggering a job that ends immediately, the job will not even be enqueued.

* [#1835](https://github.com/shlinkio/shlink/issues/1835) Docker image is now built only when a release is tagged, and new tags are included, for minor and major versions.
* [#1055](https://github.com/shlinkio/shlink/issues/1055) Update OAS definition to v3.1.
* [#1885](https://github.com/shlinkio/shlink/issues/1885) Update to chronos 3.0.
* [#1896](https://github.com/shlinkio/shlink/issues/1896) Requests to health endpoint are no longer logged.
* [#1877](https://github.com/shlinkio/shlink/issues/1877) Print a warning when manually running `visit:download-db` command and a GeoLite2 license was not provided.

### Deprecated
* [#1783](https://github.com/shlinkio/shlink/issues/1783) Deprecated support for openswoole. RoadRunner is the best replacement, with the same capabilities, but much easier and convenient to install and manage.

### Removed
* [#1790](https://github.com/shlinkio/shlink/issues/1790) Drop support for PHP 8.1.

### Fixed
* [#1819](https://github.com/shlinkio/shlink/issues/1819) Fix incorrect timeout when running DB commands during Shlink start-up.
* [#1901](https://github.com/shlinkio/shlink/issues/1901) Do not allow short URLs with custom slugs containing URL-reserved characters, as they will not work at all afterward.
* [#1900](https://github.com/shlinkio/shlink/issues/1900) Fix short URL visits deletion when multi-segment slugs are enabled.


## [3.6.4] - 2023-09-23
### Added
* *Nothing*

### Changed
* [#1866](https://github.com/shlinkio/shlink/issues/1866) The `INITIAL_API_KEY` env var is now only relevant for the official docker image.

  Going forward, new non-docker Shlink installations provisioned with env vars that also wish to provide an initial API key, should do it by using the `vendor/bin/shlink-installer init --initial-api-key=%SOME_KEY%` command, instead of using `INITIAL_API_KEY`.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1819](https://github.com/shlinkio/shlink/issues/1819) Fix incorrect timeout when running DB commands during Shlink start-up.
* [#1870](https://github.com/shlinkio/shlink/issues/1870) Make sure shared locks include the cache prefix when using Redis.
* [#1866](https://github.com/shlinkio/shlink/issues/1866) Fix error when starting docker image with `INITIAL_API_KEY` env var.


## [3.6.3] - 2023-06-14
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1817](https://github.com/shlinkio/shlink/issues/1817) Fix Shlink trying to create SQLite database tables even if they already exist.


## [3.6.2] - 2023-06-08
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1808](https://github.com/shlinkio/shlink/issues/1808) Fix `rr` binary downloading during Shlink update.


## [3.6.1] - 2023-06-04
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1413](https://github.com/shlinkio/shlink/issues/1413) Fix error when creating initial DB in Postgres in a cluster where a default `postgres` db does not exist or the credentials do not grant permissions to connect.
* [#1803](https://github.com/shlinkio/shlink/issues/1803) Fix default RoadRunner port when not using docker image.


## [3.6.0] - 2023-05-24
### Added
* [#1148](https://github.com/shlinkio/shlink/issues/1148) Add support to delete short URL visits.

  This can be done via `DELETE /short-urls/{shortCode}/visits` REST endpoint or via `short-url:visits-delete` console command.

  The CLI command includes a warning and requires the user to confirm before proceeding.

* [#1681](https://github.com/shlinkio/shlink/issues/1681) Add support to delete orphan visits.

  This can be done via `DELETE /visits/orphan` REST endpoint or via `visit:orphan-delete` console command.

  The CLI command includes a warning and requires the user to confirm before proceeding.

* [#1753](https://github.com/shlinkio/shlink/issues/1753) Add a new `vendor/bin/shlink-installer init` command that can be used to automate Shlink installations.

  This command can create the initial database, update it, create proxies, clean cache, download initial GeoLite db files, etc

  The official docker image also uses it on its entry point script.

* [#1656](https://github.com/shlinkio/shlink/issues/1656) Add support for openswoole 22
* [#1784](https://github.com/shlinkio/shlink/issues/1784) Add new docker tag where the container runs as a non-root user.
* [#953](https://github.com/shlinkio/shlink/issues/953) Add locks that prevent errors on duplicated keys when creating short URLs in parallel that depend on the same new tag or domain.

### Changed
* [#1755](https://github.com/shlinkio/shlink/issues/1755) Update to roadrunner 2023
* [#1745](https://github.com/shlinkio/shlink/issues/1745) Roadrunner is now the default docker runtime.

  There are now three different docker images published:

  * Versions without suffix (like `3.6.0`) will contain the default runtime, whichever it is.
  * Versions with `-roadrunner` suffix (like `3.6.0-roadrunner`) will always use roadrunner as the runtime, even if default one changes in the future.
  * Versions with `-openswoole` suffix (like `3.6.0-openswoole`) will always use openswoole as the runtime, even if default one changes in the future.

### Deprecated
* Deprecated `ENABLE_PERIODIC_VISIT_LOCATE` env var. Use an external mechanism to automate visit locations.

### Removed
* *Nothing*

### Fixed
* [#1760](https://github.com/shlinkio/shlink/issues/1760) Fix domain not being set to null when importing short URLs with default domain.
* [#953](https://github.com/shlinkio/shlink/issues/953) Fix duplicated key errors and short URL creation failing when creating short URLs in parallel that depend on the same new tag or domain.
* [#1741](https://github.com/shlinkio/shlink/issues/1741) Fix randomly using 100% CPU in task workers when trying to download GeoLite DB files.
* Fix Shlink trying to connect to RabbitMQ even if configuration set to not connect.


## [3.5.4] - 2023-04-12
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1742](https://github.com/shlinkio/shlink/issues/1742) Fix URLs using schemas which do not contain `//`, like `mailto:`, to no longer be considered valid.
* [#1743](https://github.com/shlinkio/shlink/issues/1743) Fix Error when trying to create short URLs from CLI on an openswoole context.

  Unfortunately the reason are real-time updates do not work with openswoole when outside an openswoole request, so the feature has been disabled for that context.


## [3.5.3] - 2023-03-31
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1715](https://github.com/shlinkio/shlink/issues/1715) Fix short URL creation/edition allowing long URLs without schema. Now a validation error is thrown.
* [#1537](https://github.com/shlinkio/shlink/issues/1537) Fix incorrect list of tags being returned for some author-only API keys.
* [#1738](https://github.com/shlinkio/shlink/issues/1738) Fix memory leak when importing short URLs with many visits.


## [3.5.2] - 2023-02-16
### Added
* *Nothing*

### Changed
* [#1696](https://github.com/shlinkio/shlink/issues/1696) Migrated to PHPUnit 10.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1698](https://github.com/shlinkio/shlink/issues/1698) Fixed error 500 in `robots.txt`.
* [#1688](https://github.com/shlinkio/shlink/issues/1688) Fixed huge performance degradation on `/tags/stats` endpoint.
* [#1693](https://github.com/shlinkio/shlink/issues/1693) Fixed Shlink thinking database already exists if it finds foreign tables.


## [3.5.1] - 2023-02-04
### Added
* *Nothing*

### Changed
* [#1685](https://github.com/shlinkio/shlink/issues/1685) Changed `loosely` mode to `loose`, as it was a typo. The old one keeps working and maps to the new one, but it's considered deprecated.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1682](https://github.com/shlinkio/shlink/issues/1682) Fixed incorrect case-insensitive checks in short URLs when using Microsoft SQL server.
* [#1684](https://github.com/shlinkio/shlink/issues/1684) Fixed entities metadata cache not being cleared at docker container start-up when using redis with replication.


## [3.5.0] - 2023-01-28
### Added
* [#1557](https://github.com/shlinkio/shlink/issues/1557) Added support to dynamically redirect to different long URLs based on the visitor's device type.

  For the moment, only `android`, `ios` and `desktop` can have their own specific long URL, and when the visitor cannot be matched against any of them, the regular long URL will be used.

  In the future, more granular device types could be added if appropriate (iOS tablet, android table, tablet, mobile phone, Linux, Mac, Windows, etc).

  In order to match the visitor's device, the `User-Agent` header is used.

* [#1632](https://github.com/shlinkio/shlink/issues/1632) Added amount of bots, non-bots and total visits to the visits summary endpoint.
* [#1633](https://github.com/shlinkio/shlink/issues/1633) Added amount of bots, non-bots and total visits to the tag stats endpoint.
* [#1653](https://github.com/shlinkio/shlink/issues/1653) Added support for all HTTP methods in short URLs, together with two new redirect status codes, 307 and 308.

  Existing Shlink instances will continue to work the same. However, if you decide to set the redirect status codes as 307 or 308, Shlink will also return a redirect for short URLs even when the request method is different from `GET`.

  The status 308 is equivalent to 301, and 307 is equivalent to 302. The difference is that the spec requires the client to respect the original HTTP method when performing the redirect. With 301 and 302, some old clients might perform a `GET` request during the redirect, regardless the original request method.

* [#1662](https://github.com/shlinkio/shlink/issues/1662) Added support to provide openswoole-specific config options via env vars prefixed with `OPENSWOOLE_`.
* [#1389](https://github.com/shlinkio/shlink/issues/1389) and [#706](https://github.com/shlinkio/shlink/issues/706) Added support for case-insensitive short URLs.

  In order to achieve this, a new env var/config option has been implemented (`SHORT_URL_MODE`), which allows either `strict` or ~~`loosely`~~ `loose`.

  Default value is `strict`, but if `loose` is provided, then short URLs will be matched in a case-insensitive way, and new short URLs will be generated with short-codes in lowercase only.

### Changed
* *Nothing*

### Deprecated
* [#1676](https://github.com/shlinkio/shlink/issues/1676) Deprecated `GET /short-urls/shorten` endpoint. Use `POST /short-urls` to create short URLs instead.
* [#1678](https://github.com/shlinkio/shlink/issues/1678) Deprecated `validateUrl` option on URL creation/edition.

### Removed
* *Nothing*

### Fixed
* [#1639](https://github.com/shlinkio/shlink/issues/1639) Fixed 500 error returned when request body is not valid JSON, instead of a proper descriptive error.


## [3.4.0] - 2022-12-16
### Added
* [#1612](https://github.com/shlinkio/shlink/issues/1612) Allowed to filter short URLs out of lists, when `validUntil` date is in the past or have reached their maximum amount of visits.

  This can be done by:

  * Providing `excludeMaxVisitsReached=true` and/or `excludePastValidUntil=true` to the `GET /short-urls` endpoint.
  * Providing `--exclude-max-visits-reached` and/or `--exclude-past-valid-until` to the `short-urls:list` command.

* [#1613](https://github.com/shlinkio/shlink/issues/1613) Added amount of visits coming from bots, non-bots and total to every short URL in the short URLs list.

  Additionally, added option to order by non-bot visits, by passing `nonBotVisits-DESC` or `nonBotVisits-ASC`.

* [#1599](https://github.com/shlinkio/shlink/issues/1599) Added support for credentials on redis DSNs, either only password, or both username and password.
* [#1616](https://github.com/shlinkio/shlink/issues/1616) Added support to import orphan visits when importing short URLs from another Shlink instance.
* [#1519](https://github.com/shlinkio/shlink/issues/1519) Allowing to search short URLs by default domain.
* [#1555](https://github.com/shlinkio/shlink/issues/1555) and [#1625](https://github.com/shlinkio/shlink/issues/1625) Added full support for PHP 8.2, updating the docker image to this version.

### Changed
* [#1563](https://github.com/shlinkio/shlink/issues/1563) Moved logic to reuse command options to option classes instead of base abstract command classes.
* [#1569](https://github.com/shlinkio/shlink/issues/1569) Migrated test doubles from phpspec/prophecy to PHPUnit mocks.
* [#1329](https://github.com/shlinkio/shlink/issues/1329) Split some logic from `VisitRepository` and `ShortUrlRepository` into separated repository classes.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1618](https://github.com/shlinkio/shlink/issues/1618) Fixed imported short URLs and visits dates not being set to the target server timezone.
* [#1578](https://github.com/shlinkio/shlink/issues/1578) Fixed short URL allowing an empty string as the domain during creation.
* [#1580](https://github.com/shlinkio/shlink/issues/1580) Fixed `FLUSHDB` being run on Shlink docker start-up when using redis, causing full cache to be flushed.


## [3.3.2] - 2022-10-18
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1576](https://github.com/shlinkio/shlink/issues/1576) Fixed error when trying to retry visits location from CLI.


## [3.3.1] - 2022-09-30
### Added
* *Nothing*

### Changed
* [#1474](https://github.com/shlinkio/shlink/issues/1474) Added preliminary support for PHP 8.2 during CI workflow.
* [#1551](https://github.com/shlinkio/shlink/issues/1551) Moved services related to geolocating visits to the `Visit\Geolocation` namespace.
* [#1550](https://github.com/shlinkio/shlink/issues/1550) Reorganized main namespaces from Core module.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1556](https://github.com/shlinkio/shlink/issues/1556) Fixed trailing slash not working when enabling multi-segment slashes.


## [3.3.0] - 2022-09-18
### Added
* [#1221](https://github.com/shlinkio/shlink/issues/1221) Added experimental support to run Shlink with [RoadRunner](https://roadrunner.dev) instead of openswoole.
* [#1531](https://github.com/shlinkio/shlink/issues/1531) and [#1090](https://github.com/shlinkio/shlink/issues/1090) Added support for trailing slashes in short URLs.
* [#1406](https://github.com/shlinkio/shlink/issues/1406) Added new REST API version 3.

  When making requests to the REST API with `/rest/v3/...` and an error occurs, all error types will be different, with the next correlation:

  * `INVALID_ARGUMENT` -> `https://shlink.io/api/error/invalid-data`
  * `INVALID_SHORT_URL_DELETION` -> `https://shlink.io/api/error/invalid-short-url-deletion`
  * `DOMAIN_NOT_FOUND` -> `https://shlink.io/api/error/domain-not-found`
  * `FORBIDDEN_OPERATION` -> `https://shlink.io/api/error/forbidden-tag-operation`
  * `INVALID_URL` -> `https://shlink.io/api/error/invalid-url`
  * `INVALID_SLUG` -> `https://shlink.io/api/error/non-unique-slug`
  * `INVALID_SHORTCODE` -> `https://shlink.io/api/error/short-url-not-found`
  * `TAG_CONFLICT` -> `https://shlink.io/api/error/tag-conflict`
  * `TAG_NOT_FOUND` -> `https://shlink.io/api/error/tag-not-found`
  * `MERCURE_NOT_CONFIGURED` -> `https://shlink.io/api/error/mercure-not-configured`
  * `INVALID_AUTHORIZATION` -> `https://shlink.io/api/error/missing-authentication`
  * `INVALID_API_KEY` -> `https://shlink.io/api/error/invalid-api-key`

  If you make a request to the API with v2 or v1, the old error types will be returned, until Shlink 4 is released, when only the new ones will be used.

  Non-error responses are not affected.

* [#1513](https://github.com/shlinkio/shlink/issues/1513) Added publishing of the docker image in GHCR.
* [#1114](https://github.com/shlinkio/shlink/issues/1114) Added support to provide an initial API key via `INITIAL_API_KEY` env var, when running Shlink with openswoole or RoadRunner.

  Also, the installer tool now allows to generate an initial API key that can be copy-pasted (this tool is run interactively), in case you use php-fpm or you don't want to use env vars.

* [#1528](https://github.com/shlinkio/shlink/issues/1528) Added support to delay when the GeoLite2 DB file is downloaded in docker images, speeding up its startup time.

  In order to do it, pass `SKIP_INITIAL_GEOLITE_DOWNLOAD=true` when creating the container.

### Changed
* [#1339](https://github.com/shlinkio/shlink/issues/1339) Added new test suite for CLI E2E tests.
* [#1503](https://github.com/shlinkio/shlink/issues/1503) Drastically improved build time in GitHub Actions, by optimizing parallelization and adding php extensions cache.
* [#1525](https://github.com/shlinkio/shlink/issues/1525) Migrated to custom doctrine CLI entry point.
* [#1492](https://github.com/shlinkio/shlink/issues/1492) Migrated to immutable options objects, mapped with [cuyz/valinor](https://github.com/CuyZ/Valinor).

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [3.2.1] - 2022-08-08
### Added
* *Nothing*

### Changed
* [#1495](https://github.com/shlinkio/shlink/issues/1495) Centralized how routes are configured to support multi-segment slugs.
* [#1497](https://github.com/shlinkio/shlink/issues/1497) Updated to latest shlink dependencies with support for PHP 8.1 only.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1499](https://github.com/shlinkio/shlink/issues/1499) Fixed loading of config options as env vars, which was making all default configurations to be loaded unless env vars were explicitly provided.


## [3.2.0] - 2022-08-05
### Added
* [#854](https://github.com/shlinkio/shlink/issues/854) Added support for multi-segment custom slugs.

  The feature is disabled by default, but you can optionally opt in. If you do, you will be able to create short URLs with multiple segments in the custom slug, like `https://example.com/foo/bar/baz`.

* [#1280](https://github.com/shlinkio/shlink/issues/1280) Added missing visit-related commands.

  Now you can run `tag:visits`, `domain:visits`, `visit:orphan` or `visit:non-orphan` to get the corresponding list of visits from the command line.

* [#962](https://github.com/shlinkio/shlink/issues/962) Added new real-time update for new short URLs.

  You can now subscribe to the `https://shlink.io/new-short-url` topic on any of the supported async updates technologies in order to get notified when a short URL is created.

* [#1367](https://github.com/shlinkio/shlink/issues/1367) Added support to publish real-time updates in redis pub/sub.

  The publishing will happen in the same redis instance/cluster configured for caching.

### Changed
* [#1452](https://github.com/shlinkio/shlink/issues/1452) Updated to monolog 3
* [#1485](https://github.com/shlinkio/shlink/issues/1485) Changed payload published in RabbitMQ for all visits events, in order to conform with the Async API spec.

  Since this is a breaking change, also provided a new `RABBITMQ_LEGACY_VISITS_PUBLISHING=true` env var that can be provided in order to keep the old payload.

  This env var is considered deprecated and will be removed in Shlink 4, when the legacy format will no longer be supported.

### Deprecated
* *Nothing*

### Removed
* [#1280](https://github.com/shlinkio/shlink/issues/1280) Dropped support for PHP 8.0

### Fixed
* [#1471](https://github.com/shlinkio/shlink/issues/1471) Fixed error when running `visit:locate` command with any extra parameter (like `--retry`).


## [3.1.2] - 2022-06-04
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1448](https://github.com/shlinkio/shlink/issues/1448) Fixed HTML entities not being properly parsed when auto-resolving page titles.
* [#1458](https://github.com/shlinkio/shlink/issues/1458) Fixed 500 error when filtering short URLs by ALL tags and search term.


## [3.1.1] - 2022-05-09
### Added
* *Nothing*

### Changed
* [#1444](https://github.com/shlinkio/shlink/issues/1444) Updated docker image to openswoole 4.11.1, in an attempt to fix error.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1439](https://github.com/shlinkio/shlink/issues/1439) Fixed crash when trying to auto-resolve titles for URLs which serve large binary files.


## [3.1.0] - 2022-04-23
### Added
* [#1294](https://github.com/shlinkio/shlink/issues/1294) Allowed to provide a specific domain when importing URLs from YOURLS.
* [#1416](https://github.com/shlinkio/shlink/issues/1416) Added support to import URLs from Kutt.it.
* [#1418](https://github.com/shlinkio/shlink/issues/1418) Added support to customize the timezone used by Shlink, falling back to the default one set in PHP config.

  The timezone can be set via the `TIMEZONE` env var, or using the installer tool.

* [#1309](https://github.com/shlinkio/shlink/issues/1309) Improved URL importing, ensuring individual errors do not make the whole process fail, and instead, failing URLs are skipped.
* [#1162](https://github.com/shlinkio/shlink/issues/1162) Added new endpoint to get visits by domain.

  The endpoint is `GET /domains/{domain}/visits`, and it has the same capabilities as any other visits endpoint, allowing pagination and filtering.

### Changed
* [#1359](https://github.com/shlinkio/shlink/issues/1359) Hidden database commands.
* [#1385](https://github.com/shlinkio/shlink/issues/1385) Prevented a big error message from being logged when using Shlink without mercure.
* [#1398](https://github.com/shlinkio/shlink/issues/1398) Increased required mutation score for unit tests to 85%.
* [#1419](https://github.com/shlinkio/shlink/issues/1419) Input dates are now parsed to Shlink's configured timezone or default timezone before using them for database queries.
* [#1428](https://github.com/shlinkio/shlink/issues/1428) Updated native dependencies in docker image and base image to PHP v8.1.5.

### Deprecated
* [#1340](https://github.com/shlinkio/shlink/issues/1340) Deprecated webhooks. New events will only be added to other real-time updates approaches, and webhooks will be completely removed in Shlink 4.0.0.

### Removed
* *Nothing*

### Fixed
* [#1397](https://github.com/shlinkio/shlink/issues/1397) Fixed `db:create` command always reporting the schema exists if the `db:migrate` command has been run before by mistake.
* [#1402](https://github.com/shlinkio/shlink/issues/1402) Fixed the base path getting appended with the default domain by mistake, causing multiple side effects in several places.


## [3.0.3] - 2022-02-19
### Added
* *Nothing*

### Changed
* [#1382](https://github.com/shlinkio/shlink/issues/1382) Updated docker image to PHP 8.1.3.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1377](https://github.com/shlinkio/shlink/issues/1377) Fixed installer always setting delete threshold with value 1.
* [#1379](https://github.com/shlinkio/shlink/issues/1379) Ensured API keys cannot be created with a domain-only role linked to default domain.


## [3.0.2] - 2022-02-10
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1373](https://github.com/shlinkio/shlink/issues/1373) Fixed incorrect config import when updating from Shlink 2.x using SQLite.
* [#1369](https://github.com/shlinkio/shlink/issues/1369) Fixed slow regexps in `.htaccess` file.


## [3.0.1] - 2022-02-04
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1363](https://github.com/shlinkio/shlink/issues/1363) Fixed titles being resolved no matter what when `validateUrl` is not set or is explicitly set to true.
* [#1352](https://github.com/shlinkio/shlink/issues/1352) Updated to stable pdo_sqlsrv in docker image.


## [3.0.0] - 2022-01-28
### Added
* [#767](https://github.com/shlinkio/shlink/issues/767) Added full support to use emojis everywhere, whether it is custom slugs, titles, referrers, etc.
* [#1274](https://github.com/shlinkio/shlink/issues/1274) Added support to filter short URLs lists by all provided tags.

  The `GET /short-urls` endpoint now accepts a `tagsMode=all` param which will make only short URLs matching **all** the tags in the `tags[]` query param, to be returned.

  The `short-urls:list` command now accepts a `-i`/`--including-all-tags` flag which behaves the same.

* [#1273](https://github.com/shlinkio/shlink/issues/1273) Added support for pagination in tags lists, allowing to improve performance by loading subsets of tags.

  For backwards compatibility, lists continue returning all items by default, but the `GET /tags` endpoint now supports `page` and `itemsPerPage` query params, to make sure only a subset of the tags is returned.

  This is supported both when invoking the endpoint with and without `withStats=true` query param.

  Additionally, the endpoint also supports filtering by `searchTerm` query param. When provided, only tags matching it will be returned.

* [#1063](https://github.com/shlinkio/shlink/issues/1063) Added new endpoint that allows fetching all existing non-orphan visits, in case you need a global view of all visits received by your Shlink instance.

  This can be achieved using the `GET /visits/non-orphan` endpoint.

### Changed
* [#1277](https://github.com/shlinkio/shlink/issues/1277) Reduced docker image size to 45% of the original size.
* [#1268](https://github.com/shlinkio/shlink/issues/1268) Updated dependencies, including symfony/console 6 and mezzio/mezzio-swoole 4.
* [#1283](https://github.com/shlinkio/shlink/issues/1283) Changed behavior of `DELETE_SHORT_URL_THRESHOLD` env var, disabling the feature if a value was not provided.
* [#1300](https://github.com/shlinkio/shlink/issues/1300) Changed default ordering for short URLs list, returning always from newest to oldest.
* [#1299](https://github.com/shlinkio/shlink/issues/1299) Updated to the latest base docker images, based in PHP 8.1.1, and bumped openswoole to v4.9.1.
* [#1282](https://github.com/shlinkio/shlink/issues/1282) Env vars now have precedence over installer options.
* [#1328](https://github.com/shlinkio/shlink/issues/1328) Refactored ShortUrlsRepository to use DTOs in methods with too many arguments.

### Deprecated
* [#1315](https://github.com/shlinkio/shlink/issues/1315) Deprecated `GET /tags?withStats=true` endpoint. Use `GET /tags/stats` instead.

### Removed
* [#1275](https://github.com/shlinkio/shlink/issues/1275) Removed everything that was deprecated in Shlink 2.x.

  See [UPGRADE](UPGRADE.md#from-v2x-to-v3x) doc in order to get details on how to migrate to this version.

* [#1347](https://github.com/shlinkio/shlink/issues/1347) Dropped support for regular swoole in favor of openswoole.

  Since openswoole support was introduced in the previous release version, Shlink will still consider the swoole extension as openswoole, as at the moment, functionality hasn't deviated too much, and will simplify migrating to Shlink 3.0.0

  However, there's no longer active testing with regular swoole, and it is considered no longer supported. If some incompatibility arises, the only supported solution will be to migrate to openswoole.

### Fixed
* *Nothing*


## Older versions
* [2.x.x](docs/changelog-archive/CHANGELOG-2.x.md)
* [1.x.x](docs/changelog-archive/CHANGELOG-1.x.md)
