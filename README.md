![Shlink](https://raw.githubusercontent.com/shlinkio/shlink.io/master/public/images/shlink-hero.png)

[![Build Status](https://img.shields.io/travis/shlinkio/shlink.svg?style=flat-square)](https://travis-ci.org/shlinkio/shlink)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/release/shlinkio/shlink.svg?style=flat-square)](https://packagist.org/packages/shlinkio/shlink)
[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)
[![License](https://img.shields.io/github/license/shlinkio/shlink.svg?style=flat-square)](https://github.com/shlinkio/shlink/blob/master/LICENSE)
[![Paypal donate](https://img.shields.io/badge/Donate-paypal-blue.svg?style=flat-square&logo=paypal&colorA=aaaaaa)](https://acel.me/donate)

A PHP-based self-hosted URL shortener that can be used to serve shortened URLs under your own custom domain.

> This document references Shlink 2.x. If you are using an older version and want to upgrade, follow the [UPGRADE](UPGRADE.md) doc.

## Table of Contents

- [Installation](#installation)
    - [Download](#download)
    - [Configure](#configure)
    - [Serve](#serve)
    - [Bonus](#bonus)
- [Update to new version](#update-to-new-version)
- [Using a docker image](#using-a-docker-image)
- [Using shlink](#using-shlink)
    - [Shlink CLI Help](#shlink-cli-help)
- [Multiple domains](#multiple-domains)
    - [Management](#management)
    - [Visits](#visits)
    - [Special redirects](#special-redirects)

## Installation

> These are the steps needed to install Shlink if you plan to manually host it. 
>
> Alternatively, you can use the official docker image. If that's your intention, jump directly to [Using a docker image](#using-a-docker-image)

First, make sure the host where you are going to run shlink fulfills these requirements:

* PHP 7.4 or greater with JSON, curl, PDO and gd extensions enabled.
* MySQL, MariaDB, PostgreSQL, Microsoft SQL Server or SQLite.
* The web server of your choice with PHP integration (Apache or Nginx recommended).

### Download

In order to run Shlink, you will need a built version of the project. There are two ways to get it.

* **Using a dist file**

    The easiest way to install shlink is by using one of the pre-bundled distributable packages.

    Go to the [latest version](https://github.com/shlinkio/shlink/releases/latest) and download the `shlink_x.x.x_dist.zip` file you will find there.

    Finally, decompress the file in the location of your choice.

* **Building from sources**

    If for any reason you want to build the project yourself, follow these steps:

    * Clone the project with git (`git clone https://github.com/shlinkio/shlink.git`), or download it by clicking the **Clone or download** green button.
    * Download the [Composer](https://getcomposer.org/download/) PHP package manager inside the project folder.
    * Run `./build.sh 1.0.0`, replacing the version with the version number you are going to build (the version number is only used for the generated dist file).

    After that, you will have a `shlink_x.x.x_dist.zip` dist file inside the `build` directory, that you need to decompress in the location fo your choice.

    > This is the process used when releasing new shlink versions. After tagging the new version with git, the Github release is automatically created by [travis](https://travis-ci.org/shlinkio/shlink), attaching the generated dist file to it.

### Configure

Despite how you built the project, you now need to configure it, by following these steps:

* If you are going to use MySQL, MariaDB, PostgreSQL or Microsoft SQL Server, create an empty database with the name of your choice.
* Recursively grant write permissions to the `data` directory. Shlink uses it to cache some information.
* Setup the application by running the `bin/install` script. It is a command line tool that will guide you through the installation process. **Take into account that this tool has to be run directly on the server where you plan to host Shlink. Do not run it before uploading/moving it there.**
* Generate your first API key by running `bin/cli api-key:generate`. You will need the key in order to interact with shlink's API.

### Serve

Once Shlink is configured, you need to expose it to the web, either by using a traditional web server + fast CGI approach, or by using a [swoole](https://www.swoole.co.uk/) non-blocking server.

* **Using a web server:**

    For example, assuming your domain is doma.in and shlink is in the `/path/to/shlink` folder, these would be the basic configurations for Nginx and Apache.

    *Nginx:*

    ```nginx
    server {
        server_name doma.in;
        listen 80;
        root /path/to/shlink/public;
        index index.php;
        charset utf-8;

        location / {
            try_files $uri $uri/ /index.php$is_args$args;
        }

        location ~ \.php$ {
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
            fastcgi_index index.php;
            include fastcgi.conf;
        }

        location ~ /\.ht {
            deny all;
        }
    }
    ```

    *Apache:*

    ```apache
    <VirtualHost *:80>
        ServerName doma.in
        DocumentRoot "/path/to/shlink/public"

        <Directory "/path/to/shlink/public">
            Options FollowSymLinks Includes ExecCGI
            AllowOverride all
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>
    ```

* **Using swoole:**

    First you need to install the swoole PHP extension with [pecl](https://pecl.php.net/package/swoole), `pecl install swoole`.

    Once installed, it's actually pretty easy to get shlink up and running with swoole. Run `./vendor/bin/mezzio-swoole start -d` and you will get shlink running on port 8080.

    However, by doing it this way, you are loosing all the access logs, and the service won't be automatically run if the server has to be restarted.

    For that reason, you should create a daemon script, in `/etc/init.d/shlink_swoole`, like this one, replacing `/path/to/shlink` by the path to your shlink installation:

    ```bash
    #!/bin/bash
    ### BEGIN INIT INFO
    # Provides:          shlink_swoole
    # Required-Start:    $local_fs $network $named $time $syslog
    # Required-Stop:     $local_fs $network $named $time $syslog
    # Default-Start:     2 3 4 5
    # Default-Stop:      0 1 6
    # Description:       Shlink non-blocking server with swoole
    ### END INIT INFO

    SCRIPT=/path/to/shlink/vendor/bin/mezzio-swoole\ start
    RUNAS=root

    PIDFILE=/var/run/shlink_swoole.pid
    LOGDIR=/var/log/shlink
    LOGFILE=${LOGDIR}/shlink_swoole.log

    start() {
      if [[ -f "$PIDFILE" ]] && kill -0 $(cat "$PIDFILE"); then
        echo 'Shlink with swoole already running' >&2
        return 1
      fi
      echo 'Starting shlink with swoole' >&2
      mkdir -p "$LOGDIR"
      touch "$LOGFILE"
      local CMD="$SCRIPT &> \"$LOGFILE\" & echo \$!"
      su -c "$CMD" $RUNAS > "$PIDFILE"
      echo 'Shlink started' >&2
    }

    stop() {
      if [[ ! -f "$PIDFILE" ]] || ! kill -0 $(cat "$PIDFILE"); then
        echo 'Shlink with swoole not running' >&2
        return 1
      fi
      echo 'Stopping shlink with swoole' >&2
      kill -15 $(cat "$PIDFILE") && rm -f "$PIDFILE"
      echo 'Shlink stopped' >&2
    }

    case "$1" in
      start)
        start
        ;;
      stop)
        stop
        ;;
      restart)
        stop
        start
        ;;
      *)
        echo "Usage: $0 {start|stop|restart}"
    esac
    ```

    Then run these commands to enable the service and start it:

    * `sudo chmod +x /etc/init.d/shlink_swoole`
    * `sudo update-rc.d shlink_swoole defaults`
    * `sudo update-rc.d shlink_swoole enable`
    * `/etc/init.d/shlink_swoole start`

    Now again, you can access shlink on port 8080, but this time the service will be automatically run at system start-up, and all access logs will be written in `/var/log/shlink/shlink_swoole.log` (you will probably want to [rotate those logs](https://www.digitalocean.com/community/tutorials/how-to-manage-logfiles-with-logrotate-on-ubuntu-16-04). You can find an example logrotate config file [here](data/infra/examples/shlink-daemon-logrotate.conf)).

Finally access to [https://app.shlink.io](https://app.shlink.io) and configure your server to start creating short URLs.

### Bonus

Geo-locating visits to your short links is a time-consuming task. When serving Shlink with swoole, the geo-location task is automatically run asynchronously just after a visit to a short URL happens.

However, if you are not serving Shlink with swoole, you will have to schedule the geo-location task to be run regularly in the background (for example, using cron jobs):

The command you need to run is `/path/to/shlink/bin/cli visit:locate`, and you can optionally provide the `-q` flag to remove any output and avoid your cron logs to be polluted.

## Update to new version

When a new Shlink version is available, you don't need to repeat the entire process. Instead, follow these steps:

1. Rename your existing Shlink directory to something else (ie. `shlink` ---> `shlink-old`).
2. Download and extract the new version of Shlink, and set the directory name to that of the old version (ie. `shlink`).
3. Run the `bin/update` script in the new version's directory to migrate your configuration over. You will be asked to provide the path to the old instance (ie. `shlink-old`).
4. If you are using shlink with swoole, restart the service by running `/etc/init.d/shlink_swoole restart`.

The `bin/update` will use the location from previous shlink version to import the configuration. It will then update the database and generate some assets shlink needs to work.

**Important!** It is recommended that you don't skip any version when using this process. The update tool gets better on every version, but older versions might make assumptions.

## Using a docker image

Starting with version 1.15.0, an official docker image is provided. You can learn how to use it by reading [the docs](docker/README.md).

The idea is that you can just generate a container using the image and provide custom config via env vars.

## Using shlink

Once shlink is installed, there are two main ways to interact with it:

* **The command line**. Try running `bin/cli` and see all the [available commands](#shlink-cli-help).

    All of those commands can be run with the `--help`/`-h` flag in order to see how to use them and all the available options.

    It is probably a good idea to symlink the CLI entry point (`bin/cli`) to somewhere in your path, so that you can run shlink from any directory.

* **The REST API**. The complete docs on how to use the API can be found [here](https://shlink.io/documentation/api-docs), and a sandbox which also documents every endpoint can be found in the [API Spec](https://api-spec.shlink.io/) portal.

    However, you probably don't want to consume the raw API yourself. That's why a nice [web client](https://github.com/shlinkio/shlink-web-client) is provided that can be directly used from [https://app.shlink.io](https://app.shlink.io), or you can host it yourself too.

Both the API and CLI allow you to do the same operations, except for API key management, which can be done from the command line interface only.

### Shlink CLI Help

```
Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help                Displays help for a command
  list                Lists commands
 api-key
  api-key:disable     Disables an API key.
  api-key:generate    Generates a new valid API key.
  api-key:list        Lists all the available API keys.
 db
  db:create           Creates the database needed for shlink to work. It will do nothing if the database already exists
  db:migrate          Runs database migrations, which will ensure the shlink database is up to date.
 short-url
  short-url:delete    Deletes a short URL
  short-url:generate  Generates a short URL for provided long URL and returns it
  short-url:list      List all short URLs
  short-url:parse     Returns the long URL behind a short code
  short-url:visits    Returns the detailed visits information for provided short code
 tag
  tag:create          Creates one or more tags.
  tag:delete          Deletes one or more tags.
  tag:list            Lists existing tags.
  tag:rename          Renames one existing tag.
 visit
  visit:locate        Resolves visits origin locations.
```

## Multiple domains

While in many cases you will just have one short domain and you'll want all your short URLs to be served from it, there are some cases in which you might want to have multiple short domains served from the same Shlink instance.

If that's the case, you need to understand how Shlink will behave when managing your short URLs or any of them is visited.

### Management

When you create a short URL it is possible to optionally pass a `domain` param. If you don't pass it, the short URL will be created for the default domain (the one provided during Shlink's installation or in the `SHORT_DOMAIN_HOST` env var when using the docker image).

However, if you pass it, the short URL will be "linked" to that domain.

> Note that, if the default domain is passed, Shlink will ignore it and will behave as if no `domain` param was provided.

The main benefit of being able to pass the domain is that Shlink will allow the same custom slug to be used in multiple short URLs, as long as the domain is different (like `example.com/my-compaign`, `another.com/my-compaign` and `foo.com/my-compaign`).

Then, each short URL will be tracked separately and you will be able to define specific tags and metadata for each one of them.

However, this has a side effect. When you try to interact with an existing short URL (editing tags, editing meta, resolving it or deleting it), either from the REST API or the CLI tool, you will have to provide the domain appropriately.

Let's imagine this situation. Shlink's default domain is `example.com`, and you have the next short URLs:

* `https://example.com/abc123` -> a regular short URL where no domain was provided.
* `https://example.com/my-campaign` -> a regular short URL where no domain was provided, but it has a custom slug.
* `https://another.com/my-campaign` -> a short URL where the `another.com` domain was provided, and it has a custom slug.
* `https://another.com/def456` -> a short URL where the `another.com` domain was provided.

These are some of the results you will get when trying to interact with them, depending on the params you provide:

* Providing just the `abc123` short code -> the first URL will be matched.
* Providing just the `my-campaign` short code -> the second URL will be matched, since you did not specify a domain, therefor, Shlink looks for the one with the short code/slug `my-campaign` which is also linked to default domain (or not linked to any domain, to be more accurate).
* Providing the `my-campaign` short code and the `another.com` domain -> The third one will be matched.
* Providing just the `def456` short code -> Shlink will fail/not find any short URL, since there's none with the short code `def456` linked to default domain.
* Providing the `def456` short code and the `another.com` domain -> The fourth short URL will be matched.
* Providing any short code and the `foo.com` domain -> Again, no short URL will be found, as there's none linked to `foo.com` domain.

### Visits

Before adding support for multiple domains, you could point as many domains as you wanted to Shlink, and they would have always worked for existing short codes/slugs.

In order to keep backwards compatibility, Shlink's behavior when a short URL is visited is slightly different, getting to fallback in some cases.

Let's continue with previous example, and also consider we have three domains that will resolve to our Shlink instance, which are `example.com`, `another.com` and `foo.com`.

With that in mind, this is how Shlink will behave when the next short URLs are visited:

* `https://another.com/abc123` -> There was no short URL specifically defined for domain `another.com` and short code `abc123`, but it exists for default domain (`example.com`), so it will fall back to it and redirect to where `example.com/abc123` is configured to redirect.
* `https://example.com/def456` -> The fall-back does not happen from default domain to specific ones, only the other way around (like in previous case). Because of that, this one will result in a not-found URL, even though the `def456` short code exists for `another.com` domain.
* `https://foo.com/abc123` -> This will also fall-back to `example.com/abc123`, like in the first case.
* `https://another.com/non-existing` -> The combination of `another.com` domain with the `non-existing` slug does not exist, so Shlink will try to fall-back to the same but for default domain (`example.com`). However, since that combination does not exist either, it will result in a not-found URL.
* Any other short URL visited exactly as it was configured will, of course, resolve as expected.

### Special redirects

It is currently possible to configure some special redirects when the base domain is visited, a URL does not match, or an invalid/disabled short URL is visited.

Those are configured during Shlink's installation or via env vars when using the docker image.

Currently those are all shared for all domains serving the same Shlink instance, but the plan is to update that and allow specific ones for every existing domain.

---

> This product includes GeoLite2 data created by MaxMind, available from [https://www.maxmind.com](https://www.maxmind.com)
