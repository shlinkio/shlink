# Support any HTTP method in short URLs

* Status: Accepted
* Date: 2023-01-06

## Context and problem statement

There has been a report that Shlink behaves as if a short URL was not found when the request HTTP method is not `GET`.

They want it to accept other methods so that they can do things like POSTing stuff that then gets "redirected" to the original URL.

This presents two main problems:

* Changing this could be considered a breaking change, in case someone is relying on this behavior (Shlink to only redirect on `GET`).
* Shlink currently supports two redirect statuses ([301](https://httpwg.org/specs/rfc9110.html#status.301) and [302](https://httpwg.org/specs/rfc9110.html#status.302)), which can be configured by the server admin.

  For historical reasons, a client might switch from the original method to `GET` when any of these is returned, not resulting in the desired behavior anyway.

  Instead, statuses [308](https://httpwg.org/specs/rfc9110.html#status.308) and [307](https://httpwg.org/specs/rfc9110.html#status.307) should be used.

## Considered options

There's actually two problems to solve here. Some combinations are implicitly required:

* **To support other HTTP methods in short URLs**
  * Start supporting all HTTP methods.
  * Introduce a feature flag to allow users decide if they want to support all methods or just `GET`.
* **To support other redirects statuses (308 and 307)**
  * Switch to status 308 and 307 and stop using 301 and 302.
  * Allow users to configure which of the 4 status codes they want to use, insteadof just supporting 301 and 302.
  * Allow users to configure between two combinations: 301+308 and 302+307, using 301 or 302 for `GET` requests, and 308 or 307 for the rest.

> **Note**
> I asked on social networks, and these were the results (not too many answers though):
> * https://fosstodon.org/@shlinkio/109626773392324128
> * https://twitter.com/shlinkio/status/1610347091741507585

## Decision outcome

Because of backwards compatibility, it feels like the bets option is allowing to configure between 301, 302, 308 and 307.

This has the benefit that we can keep existing behavior intact. Existing instances will continue working only on `GET`, with statuses 301 or 302.

Anyone who wants to opt-in, can switch to 308 or 307, and the short URLs will transparently work on other HTTP methods in that case.

The only drawback is that this difference in the behavior when 308 or 307 are configured needs to be documented, and explained in shlink-installer.

## Pros and Cons of the Options

### Start supporting all HTTP methods

* Good: Because the change in code is pretty simple.
* Bad: Because it would be potentially a breaking change for anyone trusting current behavior for anything.

### Support HTTP methods via feature flag

* Good: because it would be safer for existing instances and opt-in for anyone interested in this change of behavior.
* Bad: Because it requires more changes in code.
* Bad: Because it requires a new config entry in the shlink-installer.

### Switch to statuses 308 and 307

* Good: Because we keep supporting just two status codes.
* Bad: Because it requires applying mapping/transformation to convert old configurations.
* Bad: Because it requires changes in shlink-installer.

### Allow users to configure between 301, 302, 308 and 307

* Good: Because it's fully backwards compatible with existing configs.
* Good: Because it would implicitly allow enabling all HTTP methods if 308 or 307 are selected, and keep only `GET` for 301 and 302, without the need for a separated feature flag.
* Bad: Because it requires dynamically supporting only `GET` or all methods, depending on the selected status.

### Allow users to configure between 301+308 or 302+307

* Good: Because it would allow a more explicit redirects config, where values are not 301 and 302, but something like "permanent" and "temporary".
* Bad: Because it implicitly changes the behavior of existing instances, making them respond to redirects with a method other than `GET`, and with a status code other than the one they explicitly configured.
* Bad: because existing `REDIRECT_STATUS_CODE` env var might not make sense anymore, requiring a new one and logic to map from one to another.
