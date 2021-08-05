# Migrate to a new caching library

* Status: Accepted
* Date: 2021-08-05

## Context and problem statement

Shlink has always used the `doctrine/cache` library to handle anything related with cache.

It was convenient, as it provided several adapters, and it was the library used by other doctrine packages.

However, after the creation of the caching PSRs ([PSR-6 - Cache](https://www.php-fig.org/psr/psr-6) and [PSR-16 - Simple cache](https://www.php-fig.org/psr/psr-16)), most library authors have moved to those interfaces, and the doctrine team has decided to recommend using any other existing package and decommission their own solution.

Also, Shlink needs support for Redis clusters and Redis sentinels, which is not supported by `doctrine/cache` Redis adapters.

## Considered option

After some research, the only packages that seem to support the capabilities required by Shlink and also seem healthy, are these:

* [Symfony cache](https://symfony.com/doc/current/components/cache.html)
    * 🟢 PSR-6 compliant: **yes**
    * 🟢 PSR-16 compliant: **yes**
    * 🟢 APCu support: **yes**
    * 🟢 Redis support: **yes**
    * 🟢 Redis cluster support: **yes**
    * 🟢 Redis sentinel support: **yes**
    * 🟢 Can use redis through Predis: **yes**
    * 🔴 Individual packages per adapter: **no**
* [Laminas cache](https://docs.laminas.dev/laminas-cache/)
    * 🟢 PSR-6 compliant: **yes**
    * 🟢 PSR-16 compliant: **yes**
    * 🟢 APCu support: **yes**
    * 🟢 Redis support: **yes**
    * 🟢 Redis cluster support: **yes**
    * 🔴 Redis sentinel support: **no**
    * 🔴 Can use redis through Predis: **no**
    * 🟢 Individual packages per adapter: **yes**

## Decision outcome

Even though Symfony packs all their adapters in a single component, which means we will install some code that will never be used, Laminas relies on the native redis extension for anything related with redis.

That would make Shlink more complex to install, so it seems Symfony's package is the option where it's easier to migrate to.

Also, it's important that the cache component can share the Redis integration (through `Predis`, in this case), as it's also used by other components (the lock component, to name one).

## Pros and Cons of the Options

### Symfony cache

* Good because it supports Redis Sentinel.
* Good because it allows using a external `Predis` instance.
* Bad because it packs all the adapters in a single component.

### Laminas cache

* Good because allows installing only the adapters you are going to use, through separated packages.
* Bad because it requires the php-redis native extension in order to interact with Redis.
* Bad because it does ot seem to support Redis Sentinels.
