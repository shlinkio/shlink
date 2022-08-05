# Support multi-segment custom slugs

* Status: Accepted
* Date: 2022-08-05

## Context and problem statement

There's a new requirement to support multi-segment custom slugs (as in `https://exam.ple/foo/bar/baz`).

The internal router does not support this at the moment, as it only matches the shortCode in one of the segments.

## Considered options

* Tweak the internal router, so that it is capable of matching multiple segments for the slug, in every route that requires it.
* Define a new set of routes with a short prefix that allows configuring multi-segment in those, without touching the existing routes.
* Let the router fail, and use a middleware to fall back to the proper route (similar to what was done for the extra path forwarding feature).

## Decision outcome

Even though I was initially inclined to use a fallback middleware, that has turned out to be harder than anticipated, because there are several possible routes where the slug is used, and we would still need some kind of router to determine which one matches.

Because of that, the selected approach has been to tweak the existing router, so that it can match multiple segments, and moving the configuration of routes to a common place so that they can be defined in the proper order that prevents conflicts.

## Pros and Cons of the Options

### Tweaking the router

* Bad: It requires routes to be defined in a specific order, and remember it in the future if more routes are added.
* Good: It initially requires fewer changes.
* Good: Once routes are defined in the proper order, all the internal logic works out of the box.

### Defining new routes

* Bad: The end-user experience gets affected.
* Bad: Probably a lot of side effects would happen when it comes to assembling short URLs.
* Bad: Routing needs to be configured twice, resolving the same logic.
* Bad: It turns out to still conflict with some routes, even with the prefix, which defeats what looked like its main benefit.

### Let routing fail and fall back in middleware

* Good: Does not require changing routes configuration, which means less side effects.
* Bad: Since many routes can potentially end up in the middleware, there's still the need to have some kind of routing logic.
