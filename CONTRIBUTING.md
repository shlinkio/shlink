# Contributing

This file will guide you through the process of getting to project up and running, in case you want to provide coding contributions.

You will also see how to ensure the code fulfills the expected code checks, and how to creating a pull request.

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

    The first time this command is run, it will create several containers that are used during development, so it may take some time.

    It will also create some empty databases and install the project dependencies with composer.

* Run `./indocker bin/cli db:create` to create the initial database.
* Run `./indocker bin/cli db:migrate` to get database migrations up to date.
* Run `./indocker bin/cli api-key:generate` to get your first API key generated.

Once you finish this, you will have the project exposed in ports `8000` through nginx+php-fpm and `8080` through swoole.

> Note: The `indocker` shell script is a helper used to run commands inside the main docker container.

## Project tests

In order to ensure stability and no regressions are introduced while developing new features, this project has three types of tests.

* **Unit tests**: These are the simplest to run, and usually test individual pieces of code, replacing any external dependency by mocks.

    The code coverage of unit tests is pretty high, and only entity repositories are excluded because of their nature.

* **Database tests**: These are integration tests that run against a real database, and only cover entity repositories.

    Its purpose is to verify all the database queries behave as expected and return what's expected.

    The project provides some tooling to run them against any of the supported database engines.

* **API tests**: These are E2E tests that spin up an instance of the app and test it from the outside, by interacting with the REST API.

    These are the best tests to catch regressions, and to verify everything interacts as expected.

* **CLI tests**: *TBD. Once included, its purpose will be the same as API tests, but running through the command line*

Depending on the kind of contribution, maybe not all kinds of tests are needed, but the more you provide, the better.

## Running code checks

* Run `./indocker composer cs` to check coding styles are fulfilled.
* Run `./indocker composer cs:fix` to fix coding styles (some may not be fixable from the CLI)
* Run `./indocker composer stan` to statically analyze the code with [phpstan](https://phpstan.org/). This tool is the closest to "compile" PHP and verify everything would work as expected.
* Run `./indocker composer test:unit` to run the unit tests.
* Run `./indocker composer test:db` to run the database integration tests.

    This command runs the same test suite against all supported database engines. If you just want to run one of them, you can add one of `:sqlite`, `:mysql`, `:maria`, `:postgres`, `:mssql` at the end of the command.
    
    For example, `test:db:postgres`.

* Run `./indocker composer test:api` to run API E2E tests. For these, the MySQL database engine is used.
* Run `./indocker composer infect:test` ti run both unit and database tests (over sqlite) and then apply mutations to them with [infection](https://infection.github.io/).
* Run `./indocker composer ci` to run all previous commands together. This command is run during the project's continuous integration.

> Note: Due to some limitations in the tooling used by shlink, the testing databases need to exist beforehand, both for db and api tests (except sqlite).
>
> However, they just need to be created empty, with no tables. Also, once created, they are automatically reset before every new execution.
>
> The testing database is always called `shlink_test`. You can create it using the database client of your choice. [DBeaver](https://dbeaver.io/) is a good multi-platform desktop database client which supports all the engines supported by shlink.

## Pull request process

In order to provide pull requests to this project, you should always start by creating a new branch, where you will make all desired changes.

The base branch should always be `develop`, and the target branch for the pull request should also be `develop`.

Before your branch can be merged, all the checks described in [Running code checks](#running-code-checks) have to be passing. You can verify that manually by running `./indocker composer ci`, or wait for the build to be run automatically after the pull request is created.
