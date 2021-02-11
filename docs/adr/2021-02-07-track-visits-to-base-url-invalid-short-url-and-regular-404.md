# Track visits to 'base_url', 'invalid_short_url' and 'regular_404'

* Status: Accepted
* Date: 2021-02-07

## Context and problem statement

Shlink has the mechanism to return either custom errors or custom redirects when visiting the instance's base URL, an invalid short URL, or any other kind of URL that would result in a "Not found" error.

However, it does not track visits to any of those, just to valid short URLs.

The intention is to change that, and allow users to track the cases mentioned above.

## Considered option

* Create a new table to track visits o this kind.
* Reuse the existing `visits` table, by making `short_url_id` nullable and adding a couple of other fields.

## Decision outcome

The decision is to use the existing table, as making the short URL nullable can be handled seamlessly by using named constructors, and it has a lot of benefits on regards of reusing existing components.

Also, the domain name this kind of visits will receive is "Orphan Visits", as they are detached from any existing short URL.

## Pros and Cons of the Options

### New table

* Good because we don't touch existing models and tables, reducing the risk to introduce a backwards compatibility break.
* Bad because we will have to repeat data modeling and logic, or refactor some components to support both contexts. This in turn increases the options to introduce a BC break.

### Reuse existing table

* Good because all the mechanisms in place to handle visits will work out of the box, including locating visits and such.
* Bad because we will have more optional properties, which means more double checks in many places.
