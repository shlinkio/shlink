#!/usr/bin/env sh

export APP_ENV=test
export TEST_ENV=cli
export DB_DRIVER=maria

# Load and export test env vars
set -a
. ./config/test/shlink-test.env
set +a

vendor/bin/phpunit --order-by=random --testdox --testdox-summary -c phpunit-cli.xml $*
TESTS_EXIT_CODE=$?

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit $TESTS_EXIT_CODE
