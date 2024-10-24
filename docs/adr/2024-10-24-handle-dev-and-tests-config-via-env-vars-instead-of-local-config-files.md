# Handle dev and tests config via env vars instead of local config files

* Status: Accepted
* Date: 2024-10-24

## Context and problem statement

Due to the tools used by Shlink (Zend Expressive first and Mezzio later), configuration has always been handled via the config aggregator, which is a package that continues with Zend Framework 2 config management philosophy: 

1. Define multiple config files, scoped to their own context, that are merge at runtime.
2. Overwrite with so-called "local" config files, which define values used only during development, and should not be shipped to production.

However, since Shlink started to support other runtimes and added an official docker image, env vars have started to become a central part of the config definition system.

That has evolved into a system where production config can be read from env vars, but dev config is expected to be defined via local config files, forcing to maintain two approaches to load config that need to coexist.

On top of that, keeping dev configs in multiple files makes it harder to keep track of everything.

Because of that, I'm proposing to switch to an env-var-based approach for dev custom configs, and get rid of local config files.

## Considered options

1. Define dev env vars in a single `.env` file which is loaded to containers via docker compose `env-file` option.
2. Define dev env vars in a single `.env` file which is loaded via RoadRunner config.
3. Define dev env vars in a single PHP file returning a map that's then loaded with `loadEnvVarsFromConfig`.
4. Keep local config files and don't change anything.

## Decision outcome

Defining env vars in a PHP file has the benefit that any change will take effect immediately, so the decision is to go with option 3.

## Pros and Cons of the Options

### 1 - .env file via docker compose

* Good: because it does not require any special mechanism to feed the env vars into the app.
* Good: because it's a standard format known by many.
* Bad: because dev config gets leaked to tests when run inside the container, breaking some existing ones, and forcing to remember this for future tests.
* Bad: because any change to the env file requires the containers to be manually restarted, or putting some new mechanism in place to restart them automatically.

### 2 - .env file via RoadRunner

* Good: because it does not require any special mechanism to feed the env vars into the app.
* Good: because it's a standard format known by many.
* Good: because dev config does not get leaked into tests.
* Bad: because any change to the env file requires the containers to be manually restarted, or putting some new mechanism in place to restart them automatically.

### 3 - PHP file via `loadEnvVarsFromConfig`

* Good: because the existing call to `loadEnvVarsFromConfig` can be reused by tweaking a bit the glob pattern, so no new dependencies are needed.
* Good: because dev config does not get leaked into tests, and test-specific env vars can be fed using the same mechanism.
* Good: because changes are picked up instantly by both RoadRunner and php-fpm.
* Good: because env vars can be imported from `EnvVars` class, removing the chances of human mistakes and typos.
* Bad: because people not familiar with the project may not expect env vars to be defined in that format.

### 4 - keep local config

* Good: because no changes are needed in the project.
* Bad: because managing multiple local config files makes things harder to maintain.
* Bad: because setting-up the project from scratch requires more steps, or an external package to handle config files.
* Bad: because the project needs to keep two ways to load dev configs, and reading an env var does not warranty you are getting the single source of truth.
