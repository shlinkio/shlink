# CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org).


## 1.13.2 - 2018-10-18

#### Added

* [#233](https://github.com/shlinkio/shlink/issues/233) Added PHP 7.3 to build matrix allowing its failure.

#### Changed

* [#235](https://github.com/shlinkio/shlink/issues/235) Improved update instructions (thanks to [tivyhosting](https://github.com/tivyhosting)).

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#237](https://github.com/shlinkio/shlink/issues/233) Solved errors when trying to geo-locate `null` IP addresses.

    Also improved how visitor IP addresses are discovered, thanks to [akrabat/ip-address-middleware](https://github.com/akrabat/ip-address-middleware) package.


## 1.13.1 - 2018-10-16

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#231](https://github.com/shlinkio/shlink/issues/197) Fixed error when processing visits.


## 1.13.0 - 2018-10-06

#### Added

* [#197](https://github.com/shlinkio/shlink/issues/197) Added [cakephp/chronos](https://book.cakephp.org/3.0/en/chronos.html) library for date manipulations.
* [#214](https://github.com/shlinkio/shlink/issues/214) Improved build script, which allows builds to be done without "jumping" outside the project directory, and generates smaller dist files.

    It also allows automating the dist file generation in travis-ci builds.

* [#207](https://github.com/shlinkio/shlink/issues/207) Added two new config options which are asked during installation process. The config options already existed in previous shlink version, but you had to manually set their values.

    These are the new options:

    * Visits threshold to allow short URLs to be deleted.
    * Check the visits threshold when trying to delete a short URL via REST API.

#### Changed

* [#211](https://github.com/shlinkio/shlink/issues/211) Extracted installer to its own module, which will simplify moving it to a separated package in the future.
* [#200](https://github.com/shlinkio/shlink/issues/200) and [#201](https://github.com/shlinkio/shlink/issues/201) Renamed REST Action classes and CLI Command classes to use the concept of `ShortUrl` instead of the concept of `ShortCode` when referring to the entity, and left the `short code` concept to the identifier which is used as a unique code for a specific `Short URL`.
* [#181](https://github.com/shlinkio/shlink/issues/181) When importing the configuration from a previous shlink installation, it no longer asks to import every block. Instead, it is capable of detecting only new config options introduced in the new version, and ask only for those.

    If no new options are found and you have selected to import config, no further questions will be asked and shlink will just import the old config.

#### Deprecated

* [#205](https://github.com/shlinkio/shlink/issues/205) Deprecated `[POST /authenticate]` endpoint, and allowed any API request to be automatically authenticated using the `X-Api-Key` header with a valid API key.

    This effectively deprecates the `Authorization: Bearer <JWT>` authentication form, but it will keep working.

* As of [#200](https://github.com/shlinkio/shlink/issues/200) and [#201](https://github.com/shlinkio/shlink/issues/201) REST urls have changed from `/short-codes/...` to `/short-urls/...`, and the command namespaces have changed from `short-code:...` to `short-url:...`.

    In both cases, backwards compatibility has been retained and the old ones are aliases for the new ones, but the old ones are considered deprecated.

#### Removed

* *Nothing*

#### Fixed

* [#203](https://github.com/shlinkio/shlink/issues/203) Fixed some warnings thrown while unzipping distributable files.
* [#206](https://github.com/shlinkio/shlink/issues/206) An error is now thrown during installation if any required param is left empty, making the installer display a message and ask again until a value is set.


## 1.12.0 - 2018-09-15

#### Added

* [#187](https://github.com/shlinkio/shlink/issues/187) Included an API endpoint and a CLI command to delete short URLs.

    Due to the implicit danger of this operation, the deletion includes a safety check. URLs cannot be deleted if they have more than a specific amount of visits.

    The visits threshold is set to **15** by default and currently it has to be manually changed. In future versions the installation/update process will ask you about the value of the visits threshold.

    In order to change it, open the `config/autoload/delete_short_urls.global.php` file, which has this structure:

    ```php
    return [

        'delete_short_urls' => [
            'visits_threshold' => 15,
            'check_visits_threshold' => true,
        ],

    ];
    ```

    Properties are self explanatory. Change `check_visits_threshold` to `false` to completely disable this safety check, and change the value of `visits_threshold` to allow short URLs with a different number of visits to be deleted.

    Once changed, delete the `data/cache/app_config.php` file (if any) to let shlink know about the new values.

    This check is implicit for the API endpoint, but can be "disabled" for the CLI command, which will ask you when trying to delete a URL which has reached to threshold in order to force the deletion.

* [#183](https://github.com/shlinkio/shlink/issues/183) and [#190](https://github.com/shlinkio/shlink/issues/190) Included important documentation improvements in the repository itself. You no longer need to go to the website in order to see how to install or use shlink.
* [#186](https://github.com/shlinkio/shlink/issues/186) Added a small robots.txt file that prevents 404 errors to be logged due to search engines trying to index the domain where shlink is located. Thanks to [@robwent](https://github.com/robwent) for the contribution.

#### Changed

* [#145](https://github.com/shlinkio/shlink/issues/145) Shlink now obfuscates IP addresses from visitors by replacing the latest octet by `0`, which does not affect geolocation and allows it to fulfil the GDPR.

    Other known services follow this same approach, like [Google Analytics](https://support.google.com/analytics/answer/2763052?hl=en) or [Matomo](https://matomo.org/docs/privacy/#step-1-automatically-anonymize-visitor-ips)

* [#182](https://github.com/shlinkio/shlink/issues/182) The short URL creation API endpoints now return the same model used for lists and details endpoints.

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#188](https://github.com/shlinkio/shlink/issues/188) Shlink now allows multiple short URLs to be created that resolve to the same long URL.


## 1.11.0 - 2018-08-13

#### Added

* [#170](https://github.com/shlinkio/shlink/issues/170) and [#171](https://github.com/shlinkio/shlink/issues/171) Updated `[GET /short-codes]` and `[GET /short-codes/{shortCode}]` endpoints to return more meaningful information and make their response consistent.

    The short URLs are now represented by this object in both cases:

    ```json
    {
        "shortCode": "12Kb3",
        "shortUrl": "https://doma.in/12Kb3",
        "longUrl": "https://shlink.io",
        "dateCreated": "2016-05-01T20:34:16+02:00",
        "visitsCount": 1029,
        "tags": [
            "shlink"
        ],
        "originalUrl": "https://shlink.io"
    }
    ```

    The `originalUrl` property is considered deprecated and has been kept for backward compatibility purposes. It holds the same value as the `longUrl` property.

#### Changed

* *Nothing*

#### Deprecated

* The `originalUrl` property in `[GET /short-codes]` and `[GET /short-codes/{shortCode}]` endpoints is now deprecated and replaced by the `longUrl` property.

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.10.2 - 2018-08-04

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#177](https://github.com/shlinkio/shlink/issues/177) Fixed `[GET] /short-codes` endpoint returning a 500 status code when trying to filter by `tags` and `searchTerm` at the same time.
* [#175](https://github.com/shlinkio/shlink/issues/175) Fixed error introduced in previous version, where you could end up banned from the service used to resolve IP address locations.

    In order to fix that, just fill [this form](http://ip-api.com/docs/unban) including your server's IP address and your server should be unbanned.
    
    In order to prevent this, after resolving 150 IP addresses, shlink now waits 1 minute before trying to resolve any more addresses.


## 1.10.1 - 2018-08-02

#### Added

* *Nothing*

#### Changed

* [#167](https://github.com/shlinkio/shlink/issues/167) Shlink version is now set at build time to avoid older version numbers to be kept in newer builds.

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#165](https://github.com/shlinkio/shlink/issues/165) Fixed custom slugs failing when they are longer than 10 characters.
* [#166](https://github.com/shlinkio/shlink/issues/166) Fixed unusual edge case in which visits were not properly counted when ordering by visit and filtering by search term in `[GET] /short-codes` API endpoint.
* [#174](https://github.com/shlinkio/shlink/issues/174) Fixed geolocation not working due to a deprecation on used service.
* [#172](https://github.com/shlinkio/shlink/issues/172) Documented missing filtering params for `[GET] /short-codes/{shortCode}/visits` API endpoint, which allow the list to be filtered by date range.

    For example: `https://doma.in/rest/v1/short-urls/abc123/visits?startDate=2017-05-23&endDate=2017-10-05`

* [#169](https://github.com/shlinkio/shlink/issues/169) Fixed unhandled error when parsing `ShortUrlMeta` and date fields are already `DateTime` instances.


## 1.10.0 - 2018-07-09

#### Added

* [#161](https://github.com/shlinkio/shlink/issues/161) AddED support for shlink to be run with [swoole](https://www.swoole.co.uk/) via [zend-expressive-swoole](https://github.com/zendframework/zend-expressive-swoole) package

#### Changed

* [#159](https://github.com/shlinkio/shlink/issues/159) Updated CHANGELOG to follow the [keep-a-changelog](https://keepachangelog.com) format
* [#160](https://github.com/shlinkio/shlink/issues/160) Update infection to v0.9 and phpstan to v 0.10

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.9.1 - 2018-06-18

#### Added

* [#155](https://github.com/shlinkio/shlink/issues/155) Improved the pagination object returned in lists, including more meaningful properties.
    
    * Old structure:
    
    ```json
    {
      "pagination": {
        "currentPage": 1,
        "pagesCount": 2
      }
    }
    ```
    
    * New structure:
    
    ```json
    {
      "pagination": {
        "currentPage": 2,
        "pagesCount": 13,
        "itemsPerPage": 10,
        "itemsInCurrentPage": 10,
        "totalItems": 126
      }
    }
    ```

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#154](https://github.com/shlinkio/shlink/issues/154) Fixed sizes of every result page when filtering by searchTerm
* [#157](https://github.com/shlinkio/shlink/issues/157) Background commands executed by installation process now respect the originally used php binary


## 1.9.0 - 2018-05-07

#### Added

* [#147](https://github.com/shlinkio/shlink/issues/147) Allowed short URLs to be created on the fly using a single API request, including the API key in a  query param.

    This eases integration with third party services.
    
    With this feature, a simple request to a URL like `https://doma.in/rest/v1/short-codes/shorten?apiKey=[YOUR_API_KEY]&longUrl=[URL_TO_BE_SHORTENED]` would return the shortened one in JSON or plain text format.

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#139](https://github.com/shlinkio/shlink/issues/139) Ensured all core actions log exceptions


## 1.8.1 - 2018-04-07

#### Added

* *Nothing*

#### Changed

* [#141](https://github.com/shlinkio/shlink/issues/141) Removed workaround used in `PathVersionMiddleware`, since the bug in zend-stratigility has been fixed.

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#140](https://github.com/shlinkio/shlink/issues/140) Fixed warning thrown during installation while trying to include doctrine script


## 1.8.0 - 2018-03-29

#### Added

* [#125](https://github.com/shlinkio/shlink/issues/125) Implemented a path which returns a 1px image instead of a redirection.

    Useful to track emails. Just add an image pointing to a URL like `https://doma.in/abc123/track` to any email and an invisible image will be generated tracking every time the email is opened.

* [#132](https://github.com/shlinkio/shlink/issues/132) Added infection to improve tests

#### Changed

* [#130](https://github.com/shlinkio/shlink/issues/130) Updated to Expressive 3
* [#137](https://github.com/shlinkio/shlink/issues/137) Updated symfony components to v4

#### Deprecated

* *Nothing*

#### Removed

* [#131](https://github.com/shlinkio/shlink/issues/131) Dropped support for PHP 7

#### Fixed

* *Nothing*


## 1.7.2 - 2018-03-26

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#135](https://github.com/shlinkio/shlink/issues/135) Fixed `PathVersionMiddleware` being ignored when using expressive 2.2


## 1.7.1 - 2018-03-21

#### Added

* *Nothing*

#### Changed

* [#128](https://github.com/shlinkio/shlink/issues/128) Upgraded to expressive 2.2

    This will ease the upcoming update to expressive 3

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#126](https://github.com/shlinkio/shlink/issues/126) Fixed `E_USER_DEPRECATED` errors triggered when using Expressive 2.2


## 1.7.0 - 2018-01-21

#### Added

* [#88](https://github.com/shlinkio/shlink/issues/88) Allowed tracking of short URLs to be disabled by including a configurable query param
* [#108](https://github.com/shlinkio/shlink/issues/108) Allowed metadata to be defined when creating short codes

#### Changed

* [#113](https://github.com/shlinkio/shlink/issues/113) Updated CLI commands to use `SymfonyStyle`
* [#112](https://github.com/shlinkio/shlink/issues/112) Enabled Lazy loading in CLI commands
* [#117](https://github.com/shlinkio/shlink/issues/117) Every module which throws exceptions has now its own `ExceptionInterface` extending `Throwable`
* [#115](https://github.com/shlinkio/shlink/issues/115) Added phpstan to build matrix on PHP >=7.1 envs
* [#114](https://github.com/shlinkio/shlink/issues/114) Replaced [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) dev requirement by [symfony/dotenv](https://github.com/symfony/dotenv)

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.6.2 - 2017-10-25

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#109](https://github.com/shlinkio/shlink/issues/109) Fixed installation error due to typo in latest migration


## 1.6.1 - 2017-10-24

#### Added

* *Nothing*

#### Changed

* [#110](https://github.com/shlinkio/shlink/issues/110) Created `.gitattributes` file to define files to be excluded from distributable package

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.6.0 - 2017-10-23

#### Added

* [#44](https://github.com/shlinkio/shlink/issues/44) Now it is possible to set custom slugs for short URLs instead of using a generated short code
* [#47](https://github.com/shlinkio/shlink/issues/47) Allowed to limit short URLs availability by date range
* [#48](https://github.com/shlinkio/shlink/issues/48) Allowed to limit the number of visits to a short URL
* [#105](https://github.com/shlinkio/shlink/pull/105) Added option to enable/disable URL validation by response status code

#### Changed

* [#27](https://github.com/shlinkio/shlink/issues/27) Added repository functional tests with dbunit
* [#101](https://github.com/shlinkio/shlink/issues/101) Now specific actions just capture very specific exceptions, and let the `ErrorHandler` catch any other unhandled exception
* [#104](https://github.com/shlinkio/shlink/issues/104) Used different templates for *requested-short-code-does-not-exist* and *route-could-not-be-match*
* [#99](https://github.com/shlinkio/shlink/issues/99) Replaced usages of `AnnotatedFactory` by `ConfigAbstractFactory`
* [#100](https://github.com/shlinkio/shlink/issues/100) Updated templates engine. Replaced twig by plates
* [#102](https://github.com/shlinkio/shlink/issues/102) Improved coding standards strictness

#### Deprecated

* *Nothing*

#### Removed

* [#86](https://github.com/shlinkio/shlink/issues/86) Dropped support for PHP 5

#### Fixed

* [#103](https://github.com/shlinkio/shlink/issues/103) `NotFoundDelegate` now returns proper content types based on accepted content


## 1.5.0 - 2017-07-16

#### Added

* [#95](https://github.com/shlinkio/shlink/issues/95) Added tags CRUD to CLI
* [#59](https://github.com/shlinkio/shlink/issues/59) Added tags CRUD to REST
* [#66](https://github.com/shlinkio/shlink/issues/66) Allowed certain information to be imported from and older shlink instance directory when updating

#### Changed

* [#96](https://github.com/shlinkio/shlink/issues/96) Added namespace to functions
* [#76](https://github.com/shlinkio/shlink/issues/76) Added response examples to swagger docs
* [#93](https://github.com/shlinkio/shlink/issues/93) Improved cross domain management by using the `ImplicitOptionsMiddleware`

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#92](https://github.com/shlinkio/shlink/issues/92) Fixed formatted dates, using an ISO compliant format


## 1.4.0 - 2017-03-25

#### Added

* *Nothing*

#### Changed

* [#89](https://github.com/shlinkio/shlink/issues/89) Updated to expressive 2

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.3.1 - 2017-01-22

#### Added

* *Nothing*

#### Changed

* [#82](https://github.com/shlinkio/shlink/issues/82) Enabled `FastRoute` routes cache
* [#85](https://github.com/shlinkio/shlink/issues/85) Updated year in license file
* [#81](https://github.com/shlinkio/shlink/issues/81) Added docker containers config

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#83](https://github.com/shlinkio/shlink/issues/83) Fixed short codes list: search in tags when filtering by query string
* [#79](https://github.com/shlinkio/shlink/issues/79) Increased the number of followed redirects
* [#75](https://github.com/shlinkio/shlink/issues/75) Applied `PathVersionMiddleware` only to rest routes defining it by configuration instead of code
* [#77](https://github.com/shlinkio/shlink/issues/77) Allowed defining database server hostname and port


## 1.3.0 - 2016-10-23

#### Added

* [#67](https://github.com/shlinkio/shlink/issues/67) Allowed to order the short codes list
* [#60](https://github.com/shlinkio/shlink/issues/60) Accepted JSON requests in REST and used a body parser middleware to set the request's `parsedBody`
* [#72](https://github.com/shlinkio/shlink/issues/72) When listing API keys from CLI, use yellow color for enabled keys that have expired
* [#58](https://github.com/shlinkio/shlink/issues/58) Allowed to filter short URLs by tag
* [#69](https://github.com/shlinkio/shlink/issues/69) Allowed to filter short URLs by text query
* [#73](https://github.com/shlinkio/shlink/issues/73) Added tag-related endpoints to swagger file
* [#63](https://github.com/shlinkio/shlink/issues/63) Added path versioning to REST API routes

#### Changed

* [#71](https://github.com/shlinkio/shlink/issues/71) Separated swagger docs into multiple files

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*


## 1.2.2 - 2016-08-29

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* Fixed minor bugs on CORS requests


## 1.2.1 - 2016-08-21

#### Added

* *Nothing*

#### Changed

* *Nothing*

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#62](https://github.com/shlinkio/shlink/issues/62) Fixed cross-domain requests in REST API


## 1.2.0 - 2016-08-21

#### Added

* [#45](https://github.com/shlinkio/shlink/issues/45) Allowed to define tags on short codes, to improve filtering and classification
* [#7](https://github.com/shlinkio/shlink/issues/7) Added website previews while listing available URLs
* [#57](https://github.com/shlinkio/shlink/issues/57) Added database migrations system to improve updating between versions
* [#31](https://github.com/shlinkio/shlink/issues/31) Added support for other database management systems by improving the `EntityManager` factory
* [#51](https://github.com/shlinkio/shlink/issues/51) Generated build process to create app package and ease distribution
* [#38](https://github.com/shlinkio/shlink/issues/38) Defined installation script. It will request dynamic data on the fly so that there is no need to define env vars
* [#55](https://github.com/shlinkio/shlink/issues/55) Created update script which does not try to create a new database

#### Changed

* [#54](https://github.com/shlinkio/shlink/issues/54) Added cache namespace to prevent name collisions with other apps in the same environment
* [#29](https://github.com/shlinkio/shlink/issues/29) Used the [acelaya/ze-content-based-error-handler](https://github.com/acelaya/ze-content-based-error-handler) package instead of custom error handler implementation

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#53](https://github.com/shlinkio/shlink/issues/53) Fixed entities database interoperability
* [#52](https://github.com/shlinkio/shlink/issues/52) Added missing htaccess file for apache environments


## 1.1.0 - 2016-08-09

#### Added

* [#46](https://github.com/shlinkio/shlink/issues/46) Defined a route that returns a QR code representing the shortened URL.

    In order to get the QR code URL, use a pattern like `https://doma.in/abc123/qr-code`

* [#32](https://github.com/shlinkio/shlink/issues/32) Added support for other cache adapters by improving the Cache factory
* [#14](https://github.com/shlinkio/shlink/issues/14) Added logger and enabled errors logging
* [#13](https://github.com/shlinkio/shlink/issues/13) Improved REST authentication

#### Changed

* [#41](https://github.com/shlinkio/shlink/issues/41) Cached the "short code" => "URL" map to prevent extra DB hits
* [#39](https://github.com/shlinkio/shlink/issues/39) Changed copyright from "Alejandro Celaya" to "Shlink" in error pages
* [#42](https://github.com/shlinkio/shlink/issues/42) REST endpoints that need to find *something* now return a 404 when it is not found
* [#35](https://github.com/shlinkio/shlink/issues/35) Updated CLI commands to use the same PHP namespace as the one used for the command name

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* [#40](https://github.com/shlinkio/shlink/issues/40) Taken into account the `X-Forwarded-For` header in order to get the visitor information, in case the server is behind a load balancer or proxy


## 1.0.0 - 2016-08-01

#### Added

* [#33](https://github.com/shlinkio/shlink/issues/33) Created a command that generates a short code charset by randomizing the default one
* [#23](https://github.com/shlinkio/shlink/issues/23) Translated application literals
* [#21](https://github.com/shlinkio/shlink/issues/21) Allowed to filter visits by date range
* [#4](https://github.com/shlinkio/shlink/issues/4) Added installation steps
* [#12](https://github.com/shlinkio/shlink/issues/12) Improved code coverage

#### Changed

* [#15](https://github.com/shlinkio/shlink/issues/15) HTTP requests now return JSON/HTML responses for errors (4xx and 5xx) based on `Accept` header
* [#22](https://github.com/shlinkio/shlink/issues/22) Now visits locations data is saved on a `visit_locations` table
* [#20](https://github.com/shlinkio/shlink/issues/20) Injected cross domain headers in response only if the `Origin` header is present in the request
* [#11](https://github.com/shlinkio/shlink/issues/11) Separated code into multiple modules
* [#18](https://github.com/shlinkio/shlink/issues/18) Grouped routable middleware in an Action namespace
* [#6](https://github.com/shlinkio/shlink/issues/6) Project no longer depends on [zendframework/zend-expressive-helpers](https://github.com/zendframework/zend-expressive-helpers) package
* [#30](https://github.com/shlinkio/shlink/issues/30) Replaced the "services" first level config entry by "dependencies", in order to fulfill default Expressive naming
* [#25](https://github.com/shlinkio/shlink/issues/25) Replaced "Middleware" suffix on routable middlewares by "Action"
* [#19](https://github.com/shlinkio/shlink/issues/19) Changed the vendor and app namespace from `Acelaya\UrlShortener` to `Shlinkio\Shlink`

#### Deprecated

* *Nothing*

#### Removed

* [#36](https://github.com/shlinkio/shlink/issues/36) Removed hhvm from the CI matrix since it doesn't support array constants and will fail

#### Fixed

* [#24](https://github.com/shlinkio/shlink/issues/24) Prevented duplicated short codes errors because of the case insensitive behavior on MySQL


## 0.2.0 - 2016-08-01

#### Added

* [#8](https://github.com/shlinkio/shlink/issues/8) Created a REST API
* [#10](https://github.com/shlinkio/shlink/issues/10) Added more CLI functionality
* [#5](https://github.com/shlinkio/shlink/issues/5) Created a CHANGELOG file

#### Changed

* [#9](https://github.com/shlinkio/shlink/issues/9) Used [symfony/console](https://github.com/symfony/console) to dispatch console requests, instead of trying to integrate the process with expressive

#### Deprecated

* *Nothing*

#### Removed

* *Nothing*

#### Fixed

* *Nothing*
