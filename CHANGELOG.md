# CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [5.1.2] - 2026-06-14
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2622](https://github.com/shlinkio/shlink/issues/2622) Fix clearing entity cache on startup silently failing when using redis/valkey.


## [5.1.1] - 2026-06-11
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2619](https://github.com/shlinkio/shlink/issues/2619) Fix `Character not in repertoire: 7 ERROR: invalid byte sequence for encoding "UTF8": 0xb7 CONTEXT: unnamed portal parameter $1` error when running `Version20260607082210` migration in Postgres, due to the missing explicitly set type.


## [5.1.0] - 2026-06-11
### Added
* [#2585](https://github.com/shlinkio/shlink/issues/2585) Add new browser condition for dynamic redirects system.

  This condition accepts the values `chrome`, `firefox`, `edge`, `safari`, `opera` and `android_browser`, to redirect to different places based on specific known web browsers.

* [#2583](https://github.com/shlinkio/shlink/issues/2583) Support redis clusters with sentinels which have their own ACL authentication.

### Changed
* [#2487](https://github.com/shlinkio/shlink/issues/2487) Create a new `long_url_hash` column, which is an indexed binary sha256 hash of the long URL. Use it when checking if a URL exists for a particular long URL, improving performance thanks to the index.
* [#2555](https://github.com/shlinkio/shlink/issues/2555) Update docker image to PHP 8.5.
* [#2590](https://github.com/shlinkio/shlink/issues/2590) Migrate to `cuyz/valinor` for request input mapping, filtering and validation.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [5.0.2] - 2026-04-16
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#2593](https://github.com/shlinkio/shlink/issues/2593) Fix long URL being ignored when editing a short URL via `short-url:edit` console command.


## [5.0.1] - 2026-03-04
### Added
* *Nothing*

### Changed
* [#2573](https://github.com/shlinkio/shlink/issues/2573) Update to PHPUnit 13
* [#2579](https://github.com/shlinkio/shlink/issues/2579) Update docker images to Alpine 3.22, to address [CVE-2025-15467](https://nvd.nist.gov/vuln/detail/CVE-2025-15467)

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [5.0.0] - 2026-01-09
### Added
* [#2431](https://github.com/shlinkio/shlink/issues/2431) Add new date-based conditions for the dynamic rules redirections system, that allow to perform redirections based on an ISO-8601 date value.

  * `before-date`: matches when current date and time is earlier than the defined threshold.
  * `after-date`: matches when current date and time is later than the defined threshold.

* [#2513](https://github.com/shlinkio/shlink/issues/2513) Add support for redis connections via unix socket (e.g. `REDIS_SERVERS=unix:/path/to/redis.sock`).
* Visits generated in the command line can now be formatted in CSV, via `--format=csv`.

### Changed
* [#2522](https://github.com/shlinkio/shlink/issues/2522) Shlink no longer tries to detect trusted proxies automatically, when resolving the visitor's IP address, as this is a potential security issue.

    Instead, if you have more than 1 proxy in front of Shlink, you should provide `TRUSTED_PROXIES` env var, with either a comma-separated list of the IP addresses of your proxies, or a number indicating how many proxies are there in front of Shlink.

* [#2311](https://github.com/shlinkio/shlink/issues/2311) All visits-related commands now return more information, and columns are arranged slightly differently.

    Among other things, they now always return the type of the visit, region, visited URL, redirected URL and whether the visit comes from a potential bot or not.

* [#2540](https://github.com/shlinkio/shlink/issues/2540) Update Symfony packages to 8.0.
* [#2512](https://github.com/shlinkio/shlink/issues/2512) Make all remaining console commands invokable.

### Deprecated
* *Nothing*

### Removed
* [#2507](https://github.com/shlinkio/shlink/issues/2507) Drop support for PHP 8.3.
* [#2514](https://github.com/shlinkio/shlink/issues/2514) Remove support to generate QR codes. This functionality is now handled by Shlink Web Client and Shlink Dashboard.
* [#2517](https://github.com/shlinkio/shlink/issues/2517) Remove `REDIRECT_APPEND_EXTRA_PATH` env var. Use `REDIRECT_EXTRA_PATH_MODE=append` instead.
* [#2519](https://github.com/shlinkio/shlink/issues/2519) Disabling API keys by their plain-text key is no longer supported. When calling `api-key:disable`, the first argument is now always assumed to be the name.
* [#2520](https://github.com/shlinkio/shlink/issues/2520) Remove deprecated `--including-all-tags` and `--show-api-key-name` options from `short-url:list` command. Use `--tags-all` and `--show-api-key` instead.
* [#2521](https://github.com/shlinkio/shlink/issues/2521) Remove deprecated `--tags` option in all commands using it. Use `--tag` multiple times instead, one per tag.
* [#2543](https://github.com/shlinkio/shlink/issues/2543) Remove support for `--order-by=field,dir` option `short-url:list` command. Use `--order-by=field-dir` instead.
* Remove support to provide redis database index via URI path. Use `?database=3` query instead.
* [#2565](https://github.com/shlinkio/shlink/issues/2565) Remove explicit dependency in ext-json, since it's part of PHP since v8.0

### Fixed
* [#2564](https://github.com/shlinkio/shlink/issues/2564) Fix error when trying to persist non-utf-8 title without being able to determine its original charset for parsing.

  Now, when resolving a website's charset, two improvements have been introduced:

  1. If the `Content-Type` header does not define the charset, we fall back to `<meta charset>` or `<meta http-equiv="Content-Type">`.
  2. If it's still not possible to determine the charset, we ignore the auto-resolved title, to avoid other encoding errors further down the line.


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
* [#2291](https://github.com/shlinkio/shlink/issues/2291) Add `api-key:delete` console command to delete API keys.

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


## Older versions
* [3.x.x](docs/changelog-archive/CHANGELOG-3.x.md)
* [2.x.x](docs/changelog-archive/CHANGELOG-2.x.md)
* [1.x.x](docs/changelog-archive/CHANGELOG-1.x.md)
