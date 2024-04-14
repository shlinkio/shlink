# CHANGELOG 2.x

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [2.10.3] - 2022-01-23
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1349](https://github.com/shlinkio/shlink/issues/1349) Fixed memory leak in cache implementation.


## [2.10.2] - 2022-01-07
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1293](https://github.com/shlinkio/shlink/issues/1293) Fixed error when trying to create/import short URLs with a too long title.
* [#1306](https://github.com/shlinkio/shlink/issues/1306) Ensured remote IP address is not logged when using swoole/openswoole.
* [#1308](https://github.com/shlinkio/shlink/issues/1308) Fixed memory leak when using redis due to the amount of non-expiring keys created by doctrine. Now they have a 24h expiration by default.


## [2.10.1] - 2021-12-21
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1285](https://github.com/shlinkio/shlink/issues/1285) Fixed error caused by database connections expiring after some hours of inactivity.
* [#1286](https://github.com/shlinkio/shlink/issues/1286) Fixed `x-request-id` header not being generated during non-rest requests.


## [2.10.0] - 2021-12-12
### Added
* [#1163](https://github.com/shlinkio/shlink/issues/1163) Allowed setting not-found redirects for default domain in the same way it's done for any other domain.

  This implies a few non-breaking changes:

    * The domains list no longer has the values of `INVALID_SHORT_URL_REDIRECT_TO`, `REGULAR_404_REDIRECT_TO` and `BASE_URL_REDIRECT_TO` on the default domain redirects.
    * The `GET /domains` endpoint includes a new `defaultRedirects` property in the response, with the default redirects set via config or env vars.
    * The `INVALID_SHORT_URL_REDIRECT_TO`, `REGULAR_404_REDIRECT_TO` and `BASE_URL_REDIRECT_TO` env vars are now deprecated, and should be replaced by `DEFAULT_INVALID_SHORT_URL_REDIRECT`, `DEFAULT_REGULAR_404_REDIRECT` and `DEFAULT_BASE_URL_REDIRECT` respectively. Deprecated ones will continue to work until v3.0.0, where they will be removed.

* [#868](https://github.com/shlinkio/shlink/issues/868) Added support to publish real-time updates in a RabbitMQ server.

  Shlink will create new exchanges and queues for every topic documented in the [Async API spec](https://api-spec.shlink.io/async-api/), meaning, you will have one queue for orphan visits, one for regular visits, and one queue for every short URL with its visits.

  The RabbitMQ server config can be provided via installer config options, or via environment variables.

* [#1204](https://github.com/shlinkio/shlink/issues/1204) Added support for `openswoole` and migrated official docker image to `openswoole`.
* [#1242](https://github.com/shlinkio/shlink/issues/1242) Added support to import urls and visits from YOURLS.

  In order to do it, you need to first install this [dedicated plugin](https://slnk.to/yourls-import) in YOURLS, and then run the `short-url:import yourls` command, as with any other source.

* [#1235](https://github.com/shlinkio/shlink/issues/1235) Added support to disable rounding QR codes block sizing via config option, env var or query param.
* [#1188](https://github.com/shlinkio/shlink/issues/1188) Added support for PHP 8.1.

  The official docker image has also been updated to use PHP 8.1 by default.

### Changed
* [#844](https://github.com/shlinkio/shlink/issues/844) Added mutation checks to API tests.
* [#1218](https://github.com/shlinkio/shlink/issues/1218) Updated to symfony/mercure 0.6.
* [#1223](https://github.com/shlinkio/shlink/issues/1223) Updated to phpstan 1.0.
* [#1258](https://github.com/shlinkio/shlink/issues/1258) Updated to Symfony 6 components, except symfony/console.
* Added `domain` field to `DeleteShortUrlException` exception.

### Deprecated
* [#1260](https://github.com/shlinkio/shlink/issues/1260) Deprecated `USE_HTTPS` env var that was added in previous release, in favor of the new `IS_HTTPS_ENABLED`.

  The old one proved to be confusing and misleading, making people think it was used to actually enable HTTPS transparently, instead of its actual purpose, which is just telling Shlink it is being served with HTTPS.

### Removed
* *Nothing*

### Fixed
* [#1206](https://github.com/shlinkio/shlink/issues/1206) Fixed debugging of the docker image, so that it does not run the commands with `-q` when the `SHELL_VERBOSITY` env var has been provided.
* [#1254](https://github.com/shlinkio/shlink/issues/1254) Fixed examples in swagger docs.


## [2.9.3] - 2021-11-15
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1232](https://github.com/shlinkio/shlink/issues/1232) Solved potential SQL injection by enforcing `doctrine/dbal` 3.1.4.


## [2.9.2] - 2021-10-23
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1210](https://github.com/shlinkio/shlink/issues/1210) Fixed real time updates not being notified due to an incorrect handling of db transactions on multi-process tasks.
* [#1211](https://github.com/shlinkio/shlink/issues/1211) Fixed `There is no active transaction` error when running migrations in MySQL/Mariadb after updating to doctrine-migrations 3.3.
* [#1197](https://github.com/shlinkio/shlink/issues/1197) Fixed amount of task workers provided via config option or env var not being validated to ensure enough workers to process all parallel tasks.


## [2.9.1] - 2021-10-11
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1201](https://github.com/shlinkio/shlink/issues/1201) Fixed crash when using the new `USE_HTTPS`, as it's boolean raw value was being used instead of resolving "https" or "http".


## [2.9.0] - 2021-10-10
### Added
* [#1015](https://github.com/shlinkio/shlink/issues/1015) Shlink now accepts configuration via env vars even when not using docker.

  The config generated with the installing tool still has precedence over the env vars, so it cannot be combined. Either you use the tool, or use env vars.

* [#1149](https://github.com/shlinkio/shlink/issues/1149) Allowed to set custom defaults for the QR codes.
* [#1112](https://github.com/shlinkio/shlink/issues/1112) Added new option to define if the query string should be forwarded on a per-short URL basis.

  The new `forwardQuery=true|false` param can be provided during short URL creation or edition, via REST API or CLI command, allowing to override the default behavior which makes the query string to always be forwarded.

* [#1105](https://github.com/shlinkio/shlink/issues/1105) Added support to define placeholders on not-found redirects, so that the redirected URL receives the originally visited path and/or domain.

  Currently, `{DOMAIN}` and `{ORIGINAL_PATH}` placeholders are supported, and they can be used both in the redirected URL's path or query.

  When they are used in the query, the values are URL encoded.

* [#1119](https://github.com/shlinkio/shlink/issues/1119) Added support to provide redis sentinel when using redis cache.
* [#1016](https://github.com/shlinkio/shlink/issues/1016) Added new option to send orphan visits to webhooks, via `NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS` env var or installer tool.

  The option is disabled by default, as the payload is backwards incompatible. You will need to adapt your webhooks to treat the `shortUrl` property as optional before enabling this option.

* [#1104](https://github.com/shlinkio/shlink/issues/1104) Added ability to disable tracking based on IP addresses.

  IP addresses can be provided in the form of fixed addresses, CIDR blocks, or wildcard patterns (192.168.*.*).

### Changed
* [#1142](https://github.com/shlinkio/shlink/issues/1142) Replaced `doctrine/cache` package with `symfony/cache`.
* [#1157](https://github.com/shlinkio/shlink/issues/1157) All routes now support CORS, not only rest ones.
* [#1144](https://github.com/shlinkio/shlink/issues/1144) Added experimental builds under PHP 8.1.

### Deprecated
* [#1164](https://github.com/shlinkio/shlink/issues/1164) Deprecated `SHORT_DOMAIN_HOST` and `SHORT_DOMAIN_SCHEMA` env vars. Use `DEFAULT_DOMAIN` and `USE_HTTPS=true|false` instead.

### Removed
* *Nothing*

### Fixed
* [#1165](https://github.com/shlinkio/shlink/issues/1165) Fixed warning displayed when trying to locate visits and there are none pending.
* [#1172](https://github.com/shlinkio/shlink/pull/1172) Removed unneeded explicitly defined volumes in docker image.


## [2.8.1] - 2021-08-15
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1155](https://github.com/shlinkio/shlink/issues/1155) Fixed numeric query params in long URLs being replaced by `0`.


## [2.8.0] - 2021-08-04
### Added
* [#1089](https://github.com/shlinkio/shlink/issues/1089) Added new `ENABLE_PERIODIC_VISIT_LOCATE` env var to docker image which schedules the `visit:locate` command every hour when provided with value `true`.
* [#1082](https://github.com/shlinkio/shlink/issues/1082) Added support for error correction level on QR codes.

  Now, when calling the `GET /{shorCode}/qr-code` URL, you can pass the `errorCorrection` query param with values `L` for Low, `M` for Medium, `Q` for Quartile or `H` for High.

* [#1080](https://github.com/shlinkio/shlink/issues/1080) Added support to redirect to URLs as soon as the path starts with a valid short code, appending the rest of the path to the redirected long URL.

  With this, if you have the `https://example.com/abc123` short URL redirecting to `https://www.twitter.com`, a visit to `https://example.com/abc123/shlinkio` will take you to `https://www.twitter.com/shlinkio`.

  This behavior needs to be actively opted in, via installer config options or env vars.

* [#943](https://github.com/shlinkio/shlink/issues/943) Added support to define different "not-found" redirects for every domain handled by Shlink.

  Shlink will continue to allow defining the default values via env vars or config, but afterwards, you can use the `domain:redirects` command or the `PATCH /domains/redirects` REST endpoint to define specific values for every single domain.

### Changed
* [#1118](https://github.com/shlinkio/shlink/issues/1118) Increased phpstan required level to 8.
* [#1127](https://github.com/shlinkio/shlink/issues/1127) Updated to infection 0.24.
* [#1139](https://github.com/shlinkio/shlink/issues/1139) Updated project dependencies, including base docker image to use PHP 8.0.9 and Alpine 3.14.

### Deprecated
* *Nothing*

### Removed
* [#1046](https://github.com/shlinkio/shlink/issues/1046) Dropped support for PHP 7.4.

### Fixed
* [#1098](https://github.com/shlinkio/shlink/issues/1098) Fixed errors when using Redis for caching, caused by some third party lib bug that was fixed on dependencies update.


## [2.7.3] - 2021-08-02
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1135](https://github.com/shlinkio/shlink/issues/1135) Fixed error when importing short URLs with no visits from another Shlink instance.
* [#1136](https://github.com/shlinkio/shlink/issues/1136) Fixed error when fetching tag/short-url/orphan visits for a page lower than 1.


## [2.7.2] - 2021-07-30
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1128](https://github.com/shlinkio/shlink/issues/1128) Increased memory limit reserved for the docker image, preventing it from crashing on GeoLite db download.


## [2.7.1] - 2021-05-30
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1100](https://github.com/shlinkio/shlink/issues/1100) Fixed Shlink trying to download GeoLite2 db files even when tracking has been disabled.


## [2.7.0] - 2021-05-23
### Added
* [#1044](https://github.com/shlinkio/shlink/issues/1044) Added ability to set names on API keys, which helps to identify them when the list grows.
* [#819](https://github.com/shlinkio/shlink/issues/819) Visits are now always located in real time, even when not using swoole.

  The only side effect is that a GeoLite2 db file is now installed when the docker image starts or during shlink installation or update.

  Also, when using swoole, the file is now updated **after** tracking a visit, which means it will not apply until the next one.

* [#1059](https://github.com/shlinkio/shlink/issues/1059) Added ability to optionally display author API key and its name when listing short URLs from the command line.
* [#1066](https://github.com/shlinkio/shlink/issues/1066) Added support to import short URLs and their visits from another Shlink instance using its API.
* [#898](https://github.com/shlinkio/shlink/issues/898) Improved tracking granularity, allowing to disable visits tracking completely, or just parts of it.

  In order to achieve it, Shlink now supports 4 new tracking-related options, that can be customized via env vars for docker, or via installer:

    * `disable_tracking`: If true, visits will not be tracked at all.
    * `disable_ip_tracking`: If true, visits will be tracked, but neither the IP address, nor the location will be resolved.
    * `disable_referrer_tracking`: If true, the referrer will not be tracked.
    * `disable_ua_tracking`: If true, the user agent will not be tracked.

* [#955](https://github.com/shlinkio/shlink/issues/955) Added new option to set short URLs as crawlable, making them be listed in the robots.txt as Allowed.
* [#900](https://github.com/shlinkio/shlink/issues/900) Shlink now tries to detect if the visit is coming from a potential bot or crawler, and allows to exclude those visits from visits lists if desired.

### Changed
* [#1036](https://github.com/shlinkio/shlink/issues/1036) Updated to `happyr/doctrine-specification` 2.0.
* [#1039](https://github.com/shlinkio/shlink/issues/1039) Updated to `endroid/qr-code` 4.0.
* [#1008](https://github.com/shlinkio/shlink/issues/1008) Ensured all logs are sent to the filesystem while running API tests, which helps debugging the reason for tests to fail.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1041](https://github.com/shlinkio/shlink/issues/1041) Ensured the default value for the version while building the docker image is `latest`.
* [#1067](https://github.com/shlinkio/shlink/issues/1067) Fixed exception when persisting multiple short URLs in one batch which include the same new tags/domains. This can potentially happen when importing URLs.


## [2.6.2] - 2021-03-12
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1047](https://github.com/shlinkio/shlink/issues/1047) Fixed error in migrations when doing a fresh installation using PHP8 and MySQL/Mariadb databases.


## [2.6.1] - 2021-02-22
### Added
* *Nothing*

### Changed
* [#1026](https://github.com/shlinkio/shlink/issues/1026) Removed non-inclusive terms from source code.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#1024](https://github.com/shlinkio/shlink/issues/1024) Fixed migration that is incorrectly skipped due to the wrong condition being used to check it.
* [#1031](https://github.com/shlinkio/shlink/issues/1031) Fixed shortening of twitter URLs with URL validation enabled.
* [#1034](https://github.com/shlinkio/shlink/issues/1034) Fixed warning displayed when shlink is stopped while running it with swoole.


## [2.6.0] - 2021-02-13
### Added
* [#856](https://github.com/shlinkio/shlink/issues/856) Added PHP 8.0 support.
* [#941](https://github.com/shlinkio/shlink/issues/941) Added support to provide a title for every short URL.

  The title can also be automatically resolved from the long URL, when no title was explicitly provided, but this option needs to be opted in.

* [#913](https://github.com/shlinkio/shlink/issues/913) Added support to import short URLs from a standard CSV file.

  The file requires the `Long URL` and `Short code` columns, and it also accepts the optional `title`, `domain` and `tags` columns.

* [#1000](https://github.com/shlinkio/shlink/issues/1000) Added support to provide a `margin` query param when generating some URL's QR code.
* [#675](https://github.com/shlinkio/shlink/issues/675) Added ability to track visits to the base URL, invalid short URLs or any other "not found" URL, as known as orphan visits.

  This behavior is enabled by default, but you can opt out via env vars or config options.

  This new orphan visits can be consumed in these ways:

    * The `https://shlink.io/new-orphan-visit` mercure topic, which gets notified when an orphan visit occurs.
    * The `GET /visits/orphan` REST endpoint, which behaves like the short URL visits and tags visits endpoints, but returns only orphan visits.

### Changed
* [#977](https://github.com/shlinkio/shlink/issues/977) Migrated from `laminas/laminas-paginator` to `pagerfanta/core` to handle pagination.
* [#986](https://github.com/shlinkio/shlink/issues/986) Updated official docker image to use PHP 8.
* [#1010](https://github.com/shlinkio/shlink/issues/1010) Increased timeout for database commands to 10 minutes.
* [#874](https://github.com/shlinkio/shlink/issues/874) Changed how dist files are generated. Now there will be two for every supported PHP version, with and without support for swoole.

  The dist files will have been built under the same PHP version they are meant to be run under, ensuring resolved dependencies are the proper ones.

### Deprecated
* [#959](https://github.com/shlinkio/shlink/issues/959) Deprecated all command flags using camelCase format (like `--expirationDate`), adding kebab-case replacements for all of them (like `--expiration-date`).

  All the existing camelCase flags will continue working for now, but will be removed in Shlink 3.0.0

* [#862](https://github.com/shlinkio/shlink/issues/862) Deprecated the endpoint to edit tags for a short URL (`PUT /short-urls/{shortCode}/tags`).

  The short URL edition endpoint (`PATCH /short-urls/{shortCode}`) now supports setting the tags too. Use it instead.

### Removed
* *Nothing*

### Fixed
* [#988](https://github.com/shlinkio/shlink/issues/988) Fixed serving zero-byte static files in apache and apache-compatible web servers.
* [#990](https://github.com/shlinkio/shlink/issues/990) Fixed short URLs not properly composed in REST API endpoints when both custom domain and custom base path are used.
* [#1002](https://github.com/shlinkio/shlink/issues/1002) Fixed weird behavior in which GeoLite2 metadata's `buildEpoch` is parsed as string instead of int.
* [#851](https://github.com/shlinkio/shlink/issues/851) Fixed error when trying to schedule swoole tasks in ARM architectures (like raspberry).


## [2.5.2] - 2021-01-24
### Added
* [#965](https://github.com/shlinkio/shlink/issues/965) Added docs section for Architectural Decision Records, including the one for API key roles.

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#979](https://github.com/shlinkio/shlink/issues/979) Added missing `itemsPerPage` query param to swagger docs for short URLs list.
* [#980](https://github.com/shlinkio/shlink/issues/980) Fixed value used for `Access-Control-Allow-Origin`, that could not work as expected when including an IP address.
* [#947](https://github.com/shlinkio/shlink/issues/947) Fixed incorrect value returned in `Access-Control-Allow-Methods` header, which always contained all methods.


## [2.5.1] - 2021-01-21
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#968](https://github.com/shlinkio/shlink/issues/968) Fixed index error in MariaDB while updating to v2.5.0.
* [#972](https://github.com/shlinkio/shlink/issues/972) Fixed 500 error when calling single-step short URL creation endpoint.


## [2.5.0] - 2021-01-17
### Added
* [#795](https://github.com/shlinkio/shlink/issues/795) and [#882](https://github.com/shlinkio/shlink/issues/882) Added new roles system to API keys.

  API keys can have any combinations of these two roles now, allowing to limit their interactions:

    * Can interact only with short URLs created with that API key.
    * Can interact only with short URLs for a specific domain.

* [#833](https://github.com/shlinkio/shlink/issues/833) Added support to connect through unix socket when using an external MySQL, MariaDB or Postgres database.

  It can be provided during the installation, or as the `DB_UNIX_SOCKET` env var for the docker image.

* [#869](https://github.com/shlinkio/shlink/issues/869) Added support for Mercure Hub 0.10.
* [#896](https://github.com/shlinkio/shlink/issues/896) Added support for unicode characters in custom slugs.
* [#930](https://github.com/shlinkio/shlink/issues/930) Added new `bin/set-option` script that allows changing individual configuration options on existing shlink instances.
* [#877](https://github.com/shlinkio/shlink/issues/877) Improved API tests on CORS, and "refined" middleware handling it.

### Changed
* [#912](https://github.com/shlinkio/shlink/issues/912) Changed error templates to be plain html files, removing the dependency on `league/plates` package.
* [#875](https://github.com/shlinkio/shlink/issues/875) Updated to `mezzio/mezzio-swoole` v3.1.
* [#952](https://github.com/shlinkio/shlink/issues/952) Simplified in-project docs, by keeping only the basics and linking to the websites docs for anything else.

### Deprecated
* [#917](https://github.com/shlinkio/shlink/issues/917) Deprecated `/{shortCode}/qr-code/{size}` URL, in favor of providing the size in the query instead, `/{shortCode}/qr-code?size={size}`.
* [#924](https://github.com/shlinkio/shlink/issues/924) Deprecated mechanism to provide config options to the docker image through volumes. Use the env vars instead as a direct replacement.

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [2.4.2] - 2020-11-22
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#904](https://github.com/shlinkio/shlink/issues/904) Explicitly added missing "Domains" and "Integrations" tags to swagger docs.
* [#901](https://github.com/shlinkio/shlink/issues/901) Ensured domains which are not in use on any short URL are not returned on the list of domains.
* [#899](https://github.com/shlinkio/shlink/issues/899) Avoided filesystem errors produced while downloading geolite DB files on several shlink instances that share the same filesystem.
* [#827](https://github.com/shlinkio/shlink/issues/827) Fixed swoole config getting loaded in config cache if a console command is run before any web execution, when swoole extension is enabled, making subsequent non-swoole web requests fail.


## [2.4.1] - 2020-11-10
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#891](https://github.com/shlinkio/shlink/issues/891) Fixed error when running migrations in postgres due to incorrect return type hint.
* [#846](https://github.com/shlinkio/shlink/issues/846) Fixed base image used for the PHP-FPM dev container.
* [#867](https://github.com/shlinkio/shlink/issues/867) Fixed not-found redirects not using proper status (301 or 302) as configured during installation.


## [2.4.0] - 2020-11-08
### Added
* [#829](https://github.com/shlinkio/shlink/issues/829) Added support for QR codes in SVG format, by passing `?format=svg` to the QR code URL.
* [#820](https://github.com/shlinkio/shlink/issues/820) Added new option to force enabling or disabling URL validation on a per-URL basis.

  Currently, there's a global config that tells if long URLs should be validated (by ensuring they are publicly accessible and return a 2xx status). However, this is either always applied or never applied.

  Now, it is possible to enforce validation or enforce disabling validation when a new short URL is created or edited:

    * On the `POST /short-url` and `PATCH /short-url/{shortCode}` endpoints, you can now pass `validateUrl: true/false` in order to enforce enabling or disabling validation, ignoring the global config. If the value is not provided, the global config is still normally applied.
    * On the `short-url:generate` CLI command, you can pass `--validate-url` or `--no-validate-url` flags, in order to enforce enabling or disabling validation. If none of them is provided, the global config is still normally applied.

* [#838](https://github.com/shlinkio/shlink/issues/838) Added new endpoint and CLI command to list existing domains.

  It returns both default domain and specific domains that were used for some short URLs.

    * REST endpoint: `GET /rest/v2/domains`
    * CLI Command: `domain:list`

* [#832](https://github.com/shlinkio/shlink/issues/832) Added support to customize the port in which the docker image listens by using the `PORT` env var or the `port` config option.

* [#860](https://github.com/shlinkio/shlink/issues/860) Added support to import links from bit.ly.

  Run the command `short-urls:import bitly` and introduce requested information in order to import all your links.

  Other sources will be supported in future releases.

### Changed
* [#836](https://github.com/shlinkio/shlink/issues/836) Added support for the `<field>-<dir>` notation while determining how to order the short URLs list, as in `?orderBy=shortCode-DESC`. This effectively deprecates the array notation (`?orderBy[shortCode]=DESC`), that will be removed in Shlink 3.0.0
* [#782](https://github.com/shlinkio/shlink/issues/782) Added code coverage to API tests.
* [#858](https://github.com/shlinkio/shlink/issues/858) Updated to latest infection version. Updated docker images to PHP 7.4.11 and swoole 4.5.5
* [#887](https://github.com/shlinkio/shlink/pull/887) Started tracking the API key used to create short URLs, in order to allow restrictions in future releases.

### Deprecated
* [#883](https://github.com/shlinkio/shlink/issues/883) Deprecated `POST /tags` endpoint and `tag:create` command, as tags are created automatically while creating short URLs.

### Removed
* *Nothing*

### Fixed
* [#837](https://github.com/shlinkio/shlink/issues/837) Drastically improved performance when creating a new shortUrl and providing `findIfExists = true`.
* [#878](https://github.com/shlinkio/shlink/issues/878) Added missing `gmp` extension to the official docker image.


## [2.3.0] - 2020-08-09
### Added
* [#746](https://github.com/shlinkio/shlink/issues/746) Allowed to configure the kind of redirect you want to use for your short URLs. You can either set:

    * `302` redirects: Default behavior. Visitors always hit the server.
    * `301` redirects: Better for SEO. Visitors hit the server the first time and then cache the redirect.

  When selecting 301 redirects, you can also configure the time redirects are cached, to mitigate deviations in stats.

* [#734](https://github.com/shlinkio/shlink/issues/734) Added support to redirect to deeplinks and other links with schemas different from `http` and `https`.
* [#709](https://github.com/shlinkio/shlink/issues/709) Added multi-architecture builds for the docker image.

* [#707](https://github.com/shlinkio/shlink/issues/707) Added `--all` flag to `short-urls:list` command, which will print all existing URLs in one go, with no pagination.

  It has one limitation, though. Because of the way the CLI tooling works, all rows in the table must be loaded in memory. If the amount of URLs is too high, the command may fail due to too much memory usage.

### Changed
* [#508](https://github.com/shlinkio/shlink/issues/508) Added mutation checks to database tests.
* [#790](https://github.com/shlinkio/shlink/issues/790) Updated to doctrine/migrations v3.
* [#798](https://github.com/shlinkio/shlink/issues/798) Updated to guzzlehttp/guzzle v7.
* [#822](https://github.com/shlinkio/shlink/issues/822) Updated docker image to use PHP 7.4.9 with Alpine 3.12 and swoole 4.5.2.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [2.2.2] - 2020-06-08
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#769](https://github.com/shlinkio/shlink/issues/769) Fixed custom slugs not allowing valid URL characters, like `.`, `_` or `~`.
* [#781](https://github.com/shlinkio/shlink/issues/781) Fixed memory leak when loading visits for a tag which is used for big amounts of short URLs.


## [2.2.1] - 2020-05-11
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#764](https://github.com/shlinkio/shlink/issues/764) Fixed error when trying to match an existing short URL which does not have `validSince` and/or `validUntil`, but you are providing either one of them for the new one.


## [2.2.0] - 2020-05-09
### Added
* [#712](https://github.com/shlinkio/shlink/issues/712) Added support to integrate Shlink with a [mercure hub](https://mercure.rocks/) server.

  Thanks to that, Shlink will be able to publish events that can be consumed in real time.

  For now, two topics (events) are published, when new visits occur. Both include a payload with the visit and the shortUrl:

        * A visit occurs on any short URL: `https://shlink.io/new-visit`.
        * A visit occurs on short URLs with a specific short code: `https://shlink.io/new-visit/{shortCode}`.

  The updates are only published when serving Shlink with swoole.

  Also, Shlink exposes a new endpoint `GET /rest/v2/mercure-info`, which returns the public URL of the mercure hub, and a valid JWT that can be used to subscribe to updates.

* [#673](https://github.com/shlinkio/shlink/issues/673) Added new `[GET /visits]` rest endpoint which returns basic visits stats.
* [#674](https://github.com/shlinkio/shlink/issues/674) Added new `[GET /tags/{tag}/visits]` rest endpoint which returns visits by tag.

  It works in the same way as the `[GET /short-urls/{shortCode}/visits]` one, returning the same response payload, and supporting the same query params, but the response is the list of visits in all short URLs which have provided tag.

* [#672](https://github.com/shlinkio/shlink/issues/672) Enhanced `[GET /tags]` rest endpoint so that it is possible to get basic stats info for every tag.

  Now, if the `withStats=true` query param is provided, the response payload will include a new `stats` property which is a list with the amount of short URLs and visits for every tag.

  Also, the `tag:list` CLI command has been changed and it always behaves like this.

* [#640](https://github.com/shlinkio/shlink/issues/640) Allowed to optionally disable visitors' IP address anonymization. This will make Shlink no longer be GDPR-compliant, but it's OK if you only plan to share your URLs in countries without this regulation.

### Changed
* [#692](https://github.com/shlinkio/shlink/issues/692) Drastically improved performance when loading visits. Specially noticeable when loading big result sets.
* [#657](https://github.com/shlinkio/shlink/issues/657) Updated how DB tests are run in travis by using docker containers which allow all engines to be covered.
* [#751](https://github.com/shlinkio/shlink/issues/751) Updated PHP and swoole versions used in docker image, and removed mssql-tools, as they are not needed.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#729](https://github.com/shlinkio/shlink/issues/729) Fixed weird error when fetching multiple visits result sets concurrently using mariadb or mysql.
* [#735](https://github.com/shlinkio/shlink/issues/735) Fixed error when cleaning metadata cache during installation when APCu is enabled.
* [#677](https://github.com/shlinkio/shlink/issues/677) Fixed `/health` endpoint returning `503` fail responses when the database connection has expired.
* [#732](https://github.com/shlinkio/shlink/issues/732) Fixed wrong client IP in access logs when serving app with swoole behind load balancer.


## [2.1.4] - 2020-04-30
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#742](https://github.com/shlinkio/shlink/issues/742) Allowed a custom GeoLite2 license key to be provided, in order to avoid download limits.


## [2.1.3] - 2020-04-09
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#712](https://github.com/shlinkio/shlink/issues/712) Fixed app set-up not clearing entities metadata cache.
* [#711](https://github.com/shlinkio/shlink/issues/711) Fixed `HEAD` requests returning a duplicated `Content-Length` header.
* [#716](https://github.com/shlinkio/shlink/issues/716) Fixed Twitter not properly displaying preview for final long URL.
* [#717](https://github.com/shlinkio/shlink/issues/717) Fixed DB connection expiring on task workers when using swoole.
* [#705](https://github.com/shlinkio/shlink/issues/705) Fixed how the short URL domain is inferred when generating QR codes, making sure the configured domain is respected even if the request is performed using a different one, and only when a custom domain is used, then that one is used instead.


## [2.1.2] - 2020-03-29
### Added
* *Nothing*

### Changed
* [#696](https://github.com/shlinkio/shlink/issues/696) Updated to infection v0.16.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#700](https://github.com/shlinkio/shlink/issues/700) Fixed migration not working with postgres.
* [#690](https://github.com/shlinkio/shlink/issues/690) Fixed tags being incorrectly sluggified when filtering short URL lists, making results not be the expected.


## [2.1.1] - 2020-03-28
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#697](https://github.com/shlinkio/shlink/issues/697) Recovered `.htaccess` file that was unintentionally removed in v2.1.0, making Shlink unusable with Apache.


## [2.1.0] - 2020-03-28
### Added
* [#626](https://github.com/shlinkio/shlink/issues/626) Added support for Microsoft SQL Server.
* [#556](https://github.com/shlinkio/shlink/issues/556) Short code lengths can now be customized, both globally and on a per-short URL basis.
* [#541](https://github.com/shlinkio/shlink/issues/541) Added a request ID that is returned on `X-Request-Id` header, can be provided from outside and is set in log entries.
* [#642](https://github.com/shlinkio/shlink/issues/642) IP geolocation is now performed over the non-anonymized IP address when using swoole.
* [#521](https://github.com/shlinkio/shlink/issues/521) The long URL for any existing short URL can now be edited using the `PATCH /short-urls/{shortCode}` endpoint.

### Changed
* [#656](https://github.com/shlinkio/shlink/issues/656) Updated to PHPUnit 9.
* [#641](https://github.com/shlinkio/shlink/issues/641) Added two new flags to the `visit:locate` command, `--retry` and `--all`.

    * When `--retry` is provided, it will try to re-locate visits which IP address was originally considered not found, in case it was a temporal issue.
    * When `--all` is provided together with `--retry`, it will try to re-locate all existing visits. A warning and confirmation are displayed, as this can have side effects.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#665](https://github.com/shlinkio/shlink/issues/665) Fixed `base_url_redirect_to` simplified config option not being properly parsed.
* [#663](https://github.com/shlinkio/shlink/issues/663) Fixed Shlink allowing short URLs to be created with an empty custom slug.
* [#678](https://github.com/shlinkio/shlink/issues/678) Fixed `db` commands not running in a non-interactive way.


## [2.0.5] - 2020-02-09
### Added
* [#651](https://github.com/shlinkio/shlink/issues/651) Documented how Shlink behaves when using multiple domains.

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#648](https://github.com/shlinkio/shlink/issues/648) Ensured any user can write in log files, in case shlink is run by several system users.
* [#650](https://github.com/shlinkio/shlink/issues/650) Ensured default domain is ignored when trying to create a short URL.


## [2.0.4] - 2020-02-02
### Added
* *Nothing*

### Changed
* [#577](https://github.com/shlinkio/shlink/issues/577) Wrapped params used to customize short URL lists into a DTO with implicit validation.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#620](https://github.com/shlinkio/shlink/issues/620) Ensured "controlled" errors (like validation errors and such) won't be logged with error level, preventing logs to be polluted.
* [#637](https://github.com/shlinkio/shlink/issues/637) Fixed several work flows in which short URLs with domain are handled form the API.
* [#644](https://github.com/shlinkio/shlink/issues/644) Fixed visits to short URL on non-default domain being linked to the URL on default domain with the same short code.
* [#643](https://github.com/shlinkio/shlink/issues/643) Fixed searching on short URL lists not taking into consideration the domain name.


## [2.0.3] - 2020-01-27
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#624](https://github.com/shlinkio/shlink/issues/624) Fixed order in which headers for remote IP detection are inspected.
* [#623](https://github.com/shlinkio/shlink/issues/623) Fixed short URLs metadata being impossible to reset.
* [#628](https://github.com/shlinkio/shlink/issues/628) Fixed `GET /short-urls/{shortCode}` REST endpoint returning a 404 for short URLs which are not enabled.
* [#621](https://github.com/shlinkio/shlink/issues/621) Fixed permission denied error when updating same GeoLite file version more than once.


## [2.0.2] - 2020-01-12
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#614](https://github.com/shlinkio/shlink/issues/614) Fixed `OPTIONS` requests including the `Origin` header not always returning an empty body with status 2xx.
* [#615](https://github.com/shlinkio/shlink/issues/615) Fixed query args with no value being lost from the long URL when users are redirected.


## [2.0.1] - 2020-01-10
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#607](https://github.com/shlinkio/shlink/issues/607) Added missing info on UPGRADE.md doc.
* [#610](https://github.com/shlinkio/shlink/issues/610) Fixed use of hardcoded quotes on a database migration which makes it fail on postgres.
* [#605](https://github.com/shlinkio/shlink/issues/605) Fixed crashes occurring when migrating from old Shlink versions with nullable DB columns that are assigned to non-nullable entity typed props.


## [2.0.0] - 2020-01-08
### Added
* [#429](https://github.com/shlinkio/shlink/issues/429) Added support for PHP 7.4
* [#529](https://github.com/shlinkio/shlink/issues/529) Created an UPGRADING.md file explaining how to upgrade from v1.x to v2.x
* [#594](https://github.com/shlinkio/shlink/issues/594) Updated external shlink packages, including installer v4.0, which adds the option to ask for the redis cluster config.

### Changed
* [#592](https://github.com/shlinkio/shlink/issues/592) Updated coding styles to use [shlinkio/php-coding-standard](https://github.com/shlinkio/php-coding-standard) v2.1.0.
* [#530](https://github.com/shlinkio/shlink/issues/530) Migrated project from deprecated `zendframework` components to the new `laminas` and `mezzio` ones.

### Deprecated
* *Nothing*

### Removed
* [#429](https://github.com/shlinkio/shlink/issues/429) Dropped support for PHP 7.2 and 7.3

* [#229](https://github.com/shlinkio/shlink/issues/229) Remove everything which was deprecated, including:

    * Preview generation feature completely removed.
    * Authentication against REST API using JWT is no longer supported.

  See [UPGRADE](UPGRADE.md#from-v1x-to-v2x) doc in order to get details on how to migrate to this version.

### Fixed
* [#600](https://github.com/shlinkio/shlink/issues/600) Fixed health action so that it works with and without version in the path.
