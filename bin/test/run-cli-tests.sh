#!/usr/bin/env sh

export APP_ENV=test
export TEST_ENV=cli
export DB_DRIVER="${DB_DRIVER:-"maria"}"
export GENERATE_COVERAGE="${GENERATE_COVERAGE:-"no"}"

[ "$GENERATE_COVERAGE" != 'no' ] && export XDEBUG_MODE=coverage

vendor/bin/phpunit --order-by=random --testdox --testdox-summary -c phpunit-cli.xml $*
TESTS_EXIT_CODE=$?

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit $TESTS_EXIT_CODE
