## CHANGELOG

### 1.1.0

**Features**

* [46: Define a route that returns a QR code representing the shortened URL](https://github.com/acelaya/url-shortener/issues/46)

**Enhancements:**

* [32: Add support for other cache adapters by improving the Cache factory](https://github.com/acelaya/url-shortener/issues/32)
* [14: https://github.com/shlinkio/shlink/issues/14](https://github.com/acelaya/url-shortener/issues/14)
* [41: Cache the "short code" => "URL" map to prevent extra DB hits](https://github.com/acelaya/url-shortener/issues/41)
* [13: Improve REST authentication](https://github.com/acelaya/url-shortener/issues/13)

**Tasks**

* [39: Change copyright from "Alejandro Celaya" to "Shlink" in error pages](https://github.com/acelaya/url-shortener/issues/39)
* [42: Make REST endpoints that need to find something return a 404 when "something" is not found](https://github.com/acelaya/url-shortener/issues/42)
* [35: Make CLI commands to use the same PHP namespace as the one used for the command name](https://github.com/acelaya/url-shortener/issues/35)

**Bugs**

* [40: Take into account the X-Forwarded-For header in order to get the visitor information, in case the server is behind a load balancer or proxy](https://github.com/acelaya/url-shortener/issues/40)

### 1.0.0

**Enhancements:**

* [33: Create a command to generate a short code charset by randomizing the default one](https://github.com/acelaya/url-shortener/issues/33)
* [15: Return JSON/HTML responses for errors (4xx and 5xx) based on accept header (content negotiation)](https://github.com/acelaya/url-shortener/issues/15)
* [23: Translate application literals](https://github.com/acelaya/url-shortener/issues/23)
* [21: Allow to filter visits by date range](https://github.com/acelaya/url-shortener/issues/21)
* [22: Save visits locations data on a visit_locations table](https://github.com/acelaya/url-shortener/issues/22)
* [20: Inject cross domain headers in response only if the Origin header is present in the request](https://github.com/acelaya/url-shortener/issues/20)
* [11: Separate code into multiple modules](https://github.com/acelaya/url-shortener/issues/11)
* [18: Group routable middleware in an Action namespace](https://github.com/acelaya/url-shortener/issues/18)

**Tasks**

* [36: Remove hhvm from the CI matrix since it doesn't support array constants and will fail](https://github.com/acelaya/url-shortener/issues/36)
* [4: Installation steps](https://github.com/acelaya/url-shortener/issues/4)
* [6: Remove dependency on expressive helpers package](https://github.com/acelaya/url-shortener/issues/6)
* [30: Replace the "services" first level config entry by "dependencies", in order to fulfill default Expressive name](https://github.com/acelaya/url-shortener/issues/30)
* [12: Improve code coverage](https://github.com/acelaya/url-shortener/issues/12)
* [25: Replace "Middleware" suffix on routable middlewares by "Action"](https://github.com/acelaya/url-shortener/issues/25)
* [19: Update the vendor and app namespace from Acelaya\UrlShortener to Shlinkio\Shlink](https://github.com/acelaya/url-shortener/issues/19)

**Bugs**

* [24: Prevent duplicated shortcodes errors because of the case insensitive behavior on MySQL](https://github.com/acelaya/url-shortener/issues/24)

### 0.2.0

**Enhancements:**

* [9: Use symfony/console to dispatch console requests, instead of trying to integrate the process with expressive](https://github.com/acelaya/url-shortener/issues/9)
* [8: Create a REST API](https://github.com/acelaya/url-shortener/issues/8)
* [10: Add more CLI functionality](https://github.com/acelaya/url-shortener/issues/10)

**Tasks**

* [5: Create CHANGELOG file](https://github.com/acelaya/url-shortener/issues/5)
