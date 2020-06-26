# Contributing

This file will guide you through the process of getting to project up and running, in order to provide coding contributions.

You will also see how to ensure the code fulfills the expected code checks, and how to end up creating a pull request.

## System dependencies

The project provides all its dependencies as docker containers through a docker-compose configuration.

Because of this, the only actual dependencies are [docker](https://docs.docker.com/get-docker/) and [docker-compose](https://docs.docker.com/compose/install/).

## Setting up the project

The first thing you need to do is fork the repository, and clone it in your local machine.

Then you will have to follow these steps:

* Copy all files with `.local.php.dist` extension from `config/autoload` by removing the dist extension.

    For example the `common.local.php.dist` file should be copied as `common.local.php`.

* Copy the file `docker-compose.override.yml.dist` by also removing the `dist` extension.
* Start-up the project by running `docker-compose up`.

    The first time this command is run, it will create several containers that are used during development, and may tike some time.

    It will also create some empty databases and install the project dependencies with composer.

* Run `./indocker bin/cli db:create` to create an empty database.
* Run `./indocker bin/cli db:migrate` to get database migrations up to date.
* Run `./indocker bin/cli api-key:generate` to get your first API key generated.

Once you finish this, you will have the project exposed in ports `8080` through nginx+php-fpm and `8000` through swoole.

> Note: The `indocker` shell script is a helper used to run commands inside the main docker container.

## Running code checks

* Run `./indocker composer cs` to check coding styles are fulfilled.
* Run `./indocker composer cs:fix` to fix coding styles (some may not be fixeable from the CLI)
* Run `./indocker composer stan` to check the code with phpstan. This tool is the closest you have to "compile" PHP and verify everything would work as expected.
* Run `./indocker composer test:unit` to run the unit tests.
* Run `./indocker composer test:db` to run integration tests with the database.

    This command runs the same test suite against all supported database engines. If you just want to run one of them, you can add one of `:sqlite`, `:mysql`, `:maria`, `:postgres`, `:mssql` to the command to run just one of them.
    
    For example, `test:db:postgres`.

* Run `./indocker composer test:api` to run API E2E tests. For these, the MySQL database engine is used.

> Note: Due to some limitations in the tooling used by shlink, the testing databases need to exist first, both for db and api tests (except sqlite).
>
> However, they just need to be created empty, with no tables. Also, once created, they are automatically reset before each execution.
>
> The testing database is always called `shlink_testing`. You can create it using the database client of your choice. [DBeaver](https://dbeaver.io/) is a good multi-platform desktop database client which supports all the engines supported by shlink.

## Pull request process
