# Shlink

[![Build Status](https://img.shields.io/travis/shlinkio/shlink.svg?style=flat-square)](https://travis-ci.org/shlinkio/shlink)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/release/shlinkio/shlink.svg?style=flat-square)](https://packagist.org/packages/shlinkio/shlink)
[![License](https://img.shields.io/github/license/shlinkio/shlink.svg?style=flat-square)](https://github.com/shlinkio/shlink/blob/master/LICENSE)
[![Paypal donate](https://img.shields.io/badge/Donate-paypal-blue.svg?style=flat-square&logo=paypal&colorA=aaaaaa)](https://acel.me/donate)

A PHP-based self-hosted URL shortener that can be used to serve shortened URLs under your own custom domain.

## Table of Contents

- [Installation](#installation)
- [Update to new version](#update-to-new-version)
- [Using a docker image](#using-a-docker-image)
- [Using shlink](#using-shlink)
  - [Shlink CLI Help](#shlink-cli-help)

## Installation

First make sure the host where you are going to run shlink fulfills these requirements:

* PHP 7.2 or greater with JSON, APCu, intl, curl, PDO and gd extensions enabled.
* MySQL, PostgreSQL or SQLite.
* The web server of your choice with PHP integration (Apache or Nginx recommended).

Then, you will need a built version of the project. There are a few ways to get it.

* **Using a dist file**

    The easiest way to install shlink is by using one of the pre-bundled distributable packages.

    Just go to the [latest version](https://github.com/shlinkio/shlink/releases/latest) and download the `shlink_X.X.X_dist.zip` file you will find there.

    Finally, decompress the file in the location of your choice.

* **Building from sources**

    If for any reason you want to build the project yourself, follow these steps:

    * Clone the project with git (`git clone https://github.com/shlinkio/shlink.git`), or download it by clicking the **Clone or download** green button.
    * Download the [Composer](https://getcomposer.org/download/) PHP package manager inside the project folder.
    * Run `./build.sh 1.0.0`, replacing the version with the version number you are going to build (the version number is only used for the generated dist file).

    After that, you will have a `shlink_x.x.x_dist.zip` dist file inside the `build` directory.

    This is the process used when releasing new shlink versions. After tagging the new version with git, the Github release is automatically created by [travis](https://travis-ci.org/shlinkio/shlink), attaching generated dist file to it.

Despite how you built the project, you are going to need to install it now, by following these steps:

* If you are going to use MySQL or PostgreSQL, create an empty database with the name of your choice.
* Recursively grant write permissions to the `data` directory. Shlink uses it to cache some information.
* Setup the application by running the `bin/install` script. It is a command line tool that will guide you through the installation process. **Take into account that this tool has to be run directly on the server where you plan to host Shlink. Do not run it before uploading/moving it there.**
* Expose shlink to the web, either by using a traditional web server + fast CGI approach, or by using a [swoole](https://www.swoole.co.uk/) non-blocking server.

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

        **Important!** Swoole support is still experimental. Use it with care, and report any found issue.

        First you need to install the swoole PHP extension with [pecl](https://pecl.php.net/package/swoole), `pecl install swoole`.

        Once installed, it's actually pretty easy to get shlink up and running with swoole. Just run `./vendor/bin/zend-expressive-swoole start -d` and you will get shlink running on port 8080.

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

        SCRIPT=/path/to/shlink/vendor/bin/zend-expressive-swoole\ start
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

* Generate your first API key by running `bin/cli api-key:generate`. You will need the key in order to interact with shlink's API.
* Finally access to [https://app.shlink.io](https://app.shlink.io) and configure your server to start creating short URLs.

**Bonus**

There are a couple of time-consuming tasks that shlink expects you to do manually, or at least it is recommended, since it will improve runtime performance.

Those tasks can be performed using shlink's CLI, so it should be easy to schedule them to be run in the background (for example, using cron jobs):

* Resolve IP address locations: `/path/to/shlink/bin/cli visit:process`

    If you don't run this command regularly, the stats will say all visits come from *unknown* locations.

* Update IP geolocation database: `/path/to/shlink/bin/cli visit:update-db`

    When shlink is installed it downloads a fresh [GeoLite2](https://dev.maxmind.com/geoip/geoip2/geolite2/) db file. Running this command will update this file.

    The file is updated the first Tuesday of every month, so it should be enough running this command the first Wednesday.

* Generate website previews: `/path/to/shlink/bin/cli short-url:process-previews`

    Running this will improve the performance of the `doma.in/abc123/preview` URLs, which return a preview of the site.

*Any of those commands accept the `-q` flag, which makes it not display any output. This is recommended when configuring the commands as cron jobs.*

In future versions, it is planed that, when using **swoole** to serve shlink, some of these tasks are automatically run without blocking the request and also, without having to configure cron jobs. Probably resolving IP locations and generating previews.

## Update to new version

When a new Shlink version is available, you don't need to repeat the entire process yourself. Instead, follow these steps:

1. Rename your existing Shlink directory to something else (ie. `shlink` ---> `shlink-old`).
2. Download and extract the new version of Shlink, and set the directories name to that of the old version. (ie. `shlink`).
3. Run the `bin/update` script in the new version's directory to migrate your configuration over.
4. If you are using shlink with swoole, restart the service by running `/etc/init.d/shlink_swoole restart`.

The `bin/update` script will ask you for the location from previous shlink version, and use it in order to import the configuration. It will then update the database and generate some assets shlink needs to work.

Right now, it does not import cached info (like website previews), but it will. For now you will need to regenerate them again.

**Important!** It is recommended that you don't skip any version when using this process. The update gets better on every version, but older versions might make assumptions.

## Using a docker image

Starting with version 1.15.0, an official docker image is provided. You can find the docs on how to use it [here](https://hub.docker.com/r/shlinkio/shlink/).

The idea is that you can just generate a container using the image and provide custom config via env vars.

## Using shlink

Once shlink is installed, there are two main ways to interact with it:

* **The command line**. Try running `bin/cli` and see all the [available commands](#shlink-cli-help).

    All of those commands can be run with the `--help`/`-h` flag in order to see how to use them and all the available options.

    It is probably a good idea to symlink the CLI entry point (`bin/cli`) to somewhere in your path, so that you can run shlink from any directory.

* **The REST API**. The complete docs on how to use the API can be found [here](https://shlink.io/api-docs), and a sandbox which also documents every endpoint can be found [here](https://shlink.io/swagger-ui/index.html).

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
  help                        Displays help for a command
  list                        Lists commands
 api-key
  api-key:disable             Disables an API key.
  api-key:generate            Generates a new valid API key.
  api-key:list                Lists all the available API keys.
 config
  config:generate-charset     [DEPRECATED] Generates a character set sample just by shuffling the default one, "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ". Then it can be set in the SHORTCODE_CHARS environment variable
  config:generate-secret      [DEPRECATED] Generates a random secret string that can be used for JWT token encryption
 short-url
  short-url:delete            [short-code:delete] Deletes a short URL
  short-url:generate          [shortcode:generate|short-code:generate] Generates a short URL for provided long URL and returns it
  short-url:list              [shortcode:list|short-code:list] List all short URLs
  short-url:parse             [shortcode:parse|short-code:parse] Returns the long URL behind a short code
  short-url:process-previews  [shortcode:process-previews|short-code:process-previews] Processes and generates the previews for every URL, improving performance for later web requests.
  short-url:visits            [shortcode:visits|short-code:visits] Returns the detailed visits information for provided short code
 tag
  tag:create                  Creates one or more tags.
  tag:delete                  Deletes one or more tags.
  tag:list                    Lists existing tags.
  tag:rename                  Renames one existing tag.
 visit
  visit:process               Processes visits where location is not set yet
  visit:update-db             Updates the GeoLite2 database file used to geolocate IP addresses
```

> This product includes GeoLite2 data created by MaxMind, available from [https://www.maxmind.com](https://www.maxmind.com)
