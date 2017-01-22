## CHANGELOG

### 1.3.1

**Tasks**

* [82: Enable FastRoute routes cache](https://github.com/shlinkio/shlink/issues/82)
* [85: Update year in license file](https://github.com/shlinkio/shlink/issues/85)
* [81: Add docker containers config](https://github.com/shlinkio/shlink/issues/81)

**Bugs**

* [83: Short codes list: search in tags when filtering by query string](https://github.com/shlinkio/shlink/issues/83)
* [79: Increase the number of followed redirects](https://github.com/shlinkio/shlink/issues/79)
* [75: Apply PathVersionMiddleware only to rest routes defining it by configuration instead of code](https://github.com/shlinkio/shlink/issues/75)
* [77: Allow defining database server hostname and port](https://github.com/shlinkio/shlink/issues/77)

### 1.3.0

**Enhancements:**

* [67: Allow to order the short codes list](https://github.com/shlinkio/shlink/issues/67)
* [60: Accept JSON requests in REST and use a body parser middleware to set the parsedBody](https://github.com/shlinkio/shlink/issues/60)
* [72: When listing API keys from CLI, display in yellow color enabled keys that have expired](https://github.com/shlinkio/shlink/issues/72)
* [58: Allow to filter short URLs by tag](https://github.com/shlinkio/shlink/issues/58)
* [69: Allow to filter short codes by text query](https://github.com/shlinkio/shlink/issues/69)

**Tasks**

* [73: Tag endpoints in swagger file](https://github.com/shlinkio/shlink/issues/73)
* [71: Separate swagger docs into multiple files](https://github.com/shlinkio/shlink/issues/71)
* [63: Add path versioning to REST API routes](https://github.com/shlinkio/shlink/issues/63)

### 1.2.2

**Bugs**

* Fixed minor bugs on CORS requests

### 1.2.1

**Bugs**

* [62: Fix cross-domain requests in REST API](https://github.com/shlinkio/shlink/issues/62)

### 1.2.0

**Features**

* [45: Allow to define tags on short codes, to improve filtering and classification](https://github.com/shlinkio/shlink/issues/45)
* [7: Add website previews while listing available URLs](https://github.com/shlinkio/shlink/issues/7)

**Enhancements:**

* [57: Add database migrations system to improve updating between versions](https://github.com/shlinkio/shlink/issues/57)
* [31: Add support for other database management systems by improving the EntityManager factory](https://github.com/shlinkio/shlink/issues/31)
* [51: Generate build process to paquetize the app and ease distribution](https://github.com/shlinkio/shlink/issues/51)
* [38: Define installation script. It will request dynamic data on the fly so that there is no need to define env vars](https://github.com/shlinkio/shlink/issues/38)

**Tasks**

* [55: Create update script which does not try to create a new database](https://github.com/shlinkio/shlink/issues/55)
* [54: Add cache namespace to prevent name collisions with other apps in the same environment](https://github.com/shlinkio/shlink/issues/54)
* [29: Use the acelaya/ze-content-based-error-handler package instead of custom error handler implementation](https://github.com/shlinkio/shlink/issues/29)

**Bugs**

* [53: Fix entities database interoperability](https://github.com/shlinkio/shlink/issues/53)
* [52: Add missing htaccess file for apache environments](https://github.com/shlinkio/shlink/issues/52)

### 1.1.0

**Features**

* [46: Define a route that returns a QR code representing the shortened URL](https://github.com/shlinkio/shlink/issues/46)

**Enhancements:**

* [32: Add support for other cache adapters by improving the Cache factory](https://github.com/shlinkio/shlink/issues/32)
* [14: https://github.com/shlinkio/shlink/issues/14](https://github.com/shlinkio/shlink/issues/14)
* [41: Cache the "short code" => "URL" map to prevent extra DB hits](https://github.com/shlinkio/shlink/issues/41)
* [13: Improve REST authentication](https://github.com/shlinkio/shlink/issues/13)

**Tasks**

* [39: Change copyright from "Alejandro Celaya" to "Shlink" in error pages](https://github.com/shlinkio/shlink/issues/39)
* [42: Make REST endpoints that need to find something return a 404 when "something" is not found](https://github.com/shlinkio/shlink/issues/42)
* [35: Make CLI commands to use the same PHP namespace as the one used for the command name](https://github.com/shlinkio/shlink/issues/35)

**Bugs**

* [40: Take into account the X-Forwarded-For header in order to get the visitor information, in case the server is behind a load balancer or proxy](https://github.com/shlinkio/shlink/issues/40)

### 1.0.0

**Enhancements:**

* [33: Create a command to generate a short code charset by randomizing the default one](https://github.com/shlinkio/shlink/issues/33)
* [15: Return JSON/HTML responses for errors (4xx and 5xx) based on accept header (content negotiation)](https://github.com/shlinkio/shlink/issues/15)
* [23: Translate application literals](https://github.com/shlinkio/shlink/issues/23)
* [21: Allow to filter visits by date range](https://github.com/shlinkio/shlink/issues/21)
* [22: Save visits locations data on a visit_locations table](https://github.com/shlinkio/shlink/issues/22)
* [20: Inject cross domain headers in response only if the Origin header is present in the request](https://github.com/shlinkio/shlink/issues/20)
* [11: Separate code into multiple modules](https://github.com/shlinkio/shlink/issues/11)
* [18: Group routable middleware in an Action namespace](https://github.com/shlinkio/shlink/issues/18)

**Tasks**

* [36: Remove hhvm from the CI matrix since it doesn't support array constants and will fail](https://github.com/shlinkio/shlink/issues/36)
* [4: Installation steps](https://github.com/shlinkio/shlink/issues/4)
* [6: Remove dependency on expressive helpers package](https://github.com/shlinkio/shlink/issues/6)
* [30: Replace the "services" first level config entry by "dependencies", in order to fulfill default Expressive name](https://github.com/shlinkio/shlink/issues/30)
* [12: Improve code coverage](https://github.com/shlinkio/shlink/issues/12)
* [25: Replace "Middleware" suffix on routable middlewares by "Action"](https://github.com/shlinkio/shlink/issues/25)
* [19: Update the vendor and app namespace from Acelaya\UrlShortener to Shlinkio\Shlink](https://github.com/shlinkio/shlink/issues/19)

**Bugs**

* [24: Prevent duplicated shortcodes errors because of the case insensitive behavior on MySQL](https://github.com/shlinkio/shlink/issues/24)

### 0.2.0

**Enhancements:**

* [9: Use symfony/console to dispatch console requests, instead of trying to integrate the process with expressive](https://github.com/shlinkio/shlink/issues/9)
* [8: Create a REST API](https://github.com/shlinkio/shlink/issues/8)
* [10: Add more CLI functionality](https://github.com/shlinkio/shlink/issues/10)

**Tasks**

* [5: Create CHANGELOG file](https://github.com/shlinkio/shlink/issues/5)
