# Shlink

[![Build Status](https://img.shields.io/travis/shlinkio/shlink.svg?style=flat-square)](https://travis-ci.org/shlinkio/shlink)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/shlinkio/shlink.svg?style=flat-square)](https://scrutinizer-ci.com/g/shlinkio/shlink/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/release/shlinkio/shlink.svg?style=flat-square)](https://packagist.org/packages/shlinkio/shlink)
[![License](https://img.shields.io/github/license/shlinkio/shlink.svg?style=flat-square)](https://github.com/shlinkio/shlink/blob/master/LICENSE)
[![Paypal donate](https://img.shields.io/badge/Donate-paypal-blue.svg?style=flat-square&logo=paypal&colorA=aaaaaa)](https://acel.me/donate)

A PHP-based self-hosted URL shortener that can be used to serve shortened URLs under your own custom domain.

## Installation

First make sure the host where you are going to run shlink fulfills these requirements:

* PHP 7.1 or greater with JSON, APCu, intl, curl, PDO and gd extensions enabled.
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
* Configure the web server of your choice to serve shlink using your short domain.

    For example, assuming your domain is doma.in and shlink is in the `/path/to/shlink` folder, this would be the basic configuration for Nginx and Apache.

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
            fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
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

* Generate your first API key by running `bin/cli api-key:generate`. You will need the key in order to interact with shlink's API.
* Finally access to [https://app.shlink.io](https://app.shlink.io) and configure your server to start creating short URLs.

**Bonus**

There are a couple of time-consuming tasks that shlink expects you to do manually, or at least it is recommended, since it will improve runtime performance.

Those tasks can be performed using shlink's CLI, so it should be easy to schedule them to be run in the background (for example, using cron jobs):

* Resolve IP address locations: `/path/to/shlink/bin/cli visit:process`

    If you don't run this command regularly, the stats will say all visits come from *unknown* locations.

* Generate website previews: `/path/to/shlink/bin/cli short-url:process-previews`

    Running this will improve the performance of the `doma.in/abc123/preview` URLs, which return a preview of the site.

## Update to new version

When a new Shlink version is available, you don't need to repeat the whole process yourself.

Instead, download the new version to a new directory, rename the old directory to something else, then rename the new directory to the previous name of the old directory, and then inside the new directory (which now has the name of the old directory), run the script `bin/update`.

The script will ask you for the location from previous shlink version, and use it in order to import the configuration.

It will then update the database and generate some assets.

Right now, it does not import cached info (like website previews), but it will. By now you will need to regenerate them again.

**Important!** It is recommended that you don't skip any version when using this process. The update gets better on every version, but older versions might make assumptions.

## Using a docker image

Currently there's no official docker image, but there's a work in progress alpha version you can find [here](https://hub.docker.com/r/shlinkio/shlink/).

The idea will be that you can just generate a container using the image and provide predefined config files via volumes or CLI arguments, so that you get shlink up and running.

Currently the image does not expose an entry point which let's you interact with shlink's CLI interface, nor allows configuration to be passed.

## Using shlink

Once shlink is installed, there are two main ways to interact with it:

* **The command line**. Try running `bin/cli` and see all the available commands.

    All of those commands can be run with the `--help`/`-h` flag in order to see how to use them and all the available options.

    It is probably a good idea to symlink the CLI entry point (`bin/cli`) to somewhere in your path, so that you can run shlink from any directory.

* **The REST API**. The complete docs on how to use the API can be found [here](https://shlink.io/api-docs), and a sandbox which also documents every endpoint can be found [here](https://shlink.io/swagger-ui/index.html).

    However, you probably don't want to consume the raw API yourself. That's why a nice [web client](https://github.com/shlinkio/shlink-web-client) is provided that can be directly used from [https://app.shlink.io](https://app.shlink.io), or you can host it yourself too.

Both the API and CLI allow you to do the same operations, except for API key management, which can be done from the command line interface only.


## Shlink CLI Help
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
  config:generate-charset     Generates a character set sample just by shuffling the default one, "123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ". Then it can be set in the SHORTCODE_CHARS environment variable
  config:generate-secret      Generates a random secret string that can be used for JWT token encryption
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
```
