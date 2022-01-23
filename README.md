![Shlink](https://raw.githubusercontent.com/shlinkio/shlink.io/main/public/images/shlink-hero.png)

[![Build Status](https://img.shields.io/github/workflow/status/shlinkio/shlink/Continuous%20integration/develop?logo=github&style=flat-square)](https://github.com/shlinkio/shlink/actions?query=workflow%3A%22Continuous+integration%22)
[![Code Coverage](https://img.shields.io/codecov/c/gh/shlinkio/shlink/develop?style=flat-square)](https://app.codecov.io/gh/shlinkio/shlink)
[![Infection MSI](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fshlinkio%2Fshlink%2Fdevelop)](https://dashboard.stryker-mutator.io/reports/github.com/shlinkio/shlink/develop)
[![Latest Stable Version](https://img.shields.io/github/release/shlinkio/shlink.svg?style=flat-square)](https://packagist.org/packages/shlinkio/shlink)
[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?logo=docker&style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)
[![License](https://img.shields.io/github/license/shlinkio/shlink.svg?style=flat-square)](https://github.com/shlinkio/shlink/blob/main/LICENSE)
[![Twitter](https://img.shields.io/twitter/follow/shlinkio?color=blue&label=follow&logo=twitter&style=flat-square)](https://twitter.com/shlinkio)
[![Paypal donate](https://img.shields.io/badge/Donate-paypal-blue.svg?style=flat-square&logo=paypal&colorA=aaaaaa)](https://slnk.to/donate)

A PHP-based self-hosted URL shortener that can be used to serve shortened URLs under your own domain.

## Table of Contents

- [Full documentation](#full-documentation)
- [Docker image](#docker-image)
- [Self hosted](#self-hosted)
    - [Download](#download)
    - [Configure](#configure)
- [Using shlink](#using-shlink)
- [Contributing](#contributing)

## Full documentation

This document contains the very basics to get started with Shlink. If you want to learn everything you can do with it, visit the [full searchable documentation](https://shlink.io/documentation/).

## Docker image

You can learn how to use the official docker image by reading [the docs](https://shlink.io/documentation/install-docker-image/).

The idea is that you can just generate a container using the image and provide the custom config via env vars.

## Self hosted

First, make sure the host where you are going to run shlink fulfills these requirements:

* PHP 8.0 or 8.1
* The next PHP extensions: json, curl, pdo, intl, gd and gmp.
    * apcu extension is recommended if you don't plan to use openswoole.
    * xml extension is required if you want to generate QR codes in svg format.
    * sockets and bcmath extensions are required if you want to integrate with a RabbitMQ instance.
* MySQL, MariaDB, PostgreSQL, Microsoft SQL Server or SQLite.
* [Openswoole](https://openswoole.com/) or the web server of your choice with PHP integration (Apache or Nginx recommended).

### Download

In order to run Shlink, you will need a built version of the project. There are two ways to get it.

* **Using a dist file**

    The easiest way to install shlink is by using one of the pre-bundled distributable packages.

    Go to the [latest version](https://github.com/shlinkio/shlink/releases/latest) and download the `shlink*_dist.zip` file that suits your needs. You will find one for every supported PHP version and with/without openswoole integration.

    Finally, decompress the file in the location of your choice.

* **Building from sources**

    If for any reason you want to build the project yourself, follow these steps:

    * Clone the project with git (`git clone https://github.com/shlinkio/shlink.git`), or download it by clicking the **Clone or download** green button.
    * Download the [Composer](https://getcomposer.org/download/) PHP package manager inside the project folder.
    * Run `./build.sh 3.0.0`, replacing the version with the version number you are going to build (the version number is used as part of the generated dist file name, and to set the value returned when running `shlink -V` from the command line).

    After that, you will have a dist file inside the `build` directory, that you need to decompress in the location of your choice.

    > This is the process used when releasing new shlink versions. After tagging the new version with git, the Github release is automatically created by a [GitHub workflow](https://github.com/shlinkio/shlink/actions?query=workflow%3A%22Publish+release%22), attaching the generated dist file to it.

### Configure

Despite how you built the project, you now need to configure it, by following these steps:

* If you are going to use MySQL, MariaDB, PostgreSQL or Microsoft SQL Server, create an empty database with the name of your choice.
* Recursively grant write permissions to the `data` directory. Shlink uses it to cache some information.
* Set up the application by running the `vendor/bin/shlink-installer install` script. It is a command line tool that will guide you through the installation process. **Take into account that this tool has to be run directly on the server where you plan to host Shlink. Do not run it before uploading/moving it there.**
* Generate your first API key by running `bin/cli api-key:generate`. You will need the key in order to interact with Shlink's API.

## Using shlink

Once shlink is installed, there are two main ways to interact with it:

* **The command line**: Try running `bin/cli` to see all the available commands.

    All of them can be run with the `--help`/`-h` flag in order to see how to use them and all the available options.

    It is probably a good idea to symlink the CLI entry point (`bin/cli`) to somewhere in your path, so that you can run shlink from any directory.

* **The REST API**: The complete docs on how to use the API can be found [here](https://shlink.io/documentation/api-docs), and a sandbox which also documents every endpoint can be found in the [API Spec](https://api-spec.shlink.io/) portal.

    However, you probably don't want to consume the raw API yourself. That's why a nice [web client](https://github.com/shlinkio/shlink-web-client) is provided that can be directly used from [https://app.shlink.io](https://app.shlink.io), or hosted by yourself.

Both the API and CLI allow you to do mostly the same operations, except for API key management, which can be done from the command line interface only.

## Contributing

If you are trying to find out how to run the project in development mode or how to provide contributions, read the [CONTRIBUTING](CONTRIBUTING.md) doc.

---

> This product includes GeoLite2 data created by MaxMind, available from [https://www.maxmind.com](https://www.maxmind.com)
