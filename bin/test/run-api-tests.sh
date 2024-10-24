#!/usr/bin/env sh

export APP_ENV=test
export TEST_ENV=api
export TEST_RUNTIME="${TEST_RUNTIME:-"rr"}" # rr is the only runtime currently supported
export DB_DRIVER="${DB_DRIVER:-"postgres"}"
export GENERATE_COVERAGE="${GENERATE_COVERAGE:-"no"}"

[ "$GENERATE_COVERAGE" != 'no' ] && export XDEBUG_MODE=coverage

# Reset logs
OUTPUT_LOGS=data/log/api-tests/output.log
rm -rf data/log/api-tests
mkdir data/log/api-tests
touch $OUTPUT_LOGS

# Try to stop server just in case it hanged in last execution
[ "$TEST_RUNTIME" = 'rr' ] && bin/rr stop -f -w .

echo 'Starting server...'
[ "$TEST_RUNTIME" = 'rr' ] && bin/rr serve -p -w . -c=config/roadrunner/.rr.test.yml \
  -o=logs.output="${PWD}/${OUTPUT_LOGS}" \
  -o=logs.channels.http.output="${PWD}/${OUTPUT_LOGS}" \
  -o=logs.channels.server.output="${PWD}/${OUTPUT_LOGS}" &
sleep 2 # Let's give the server a couple of seconds to start

vendor/bin/phpunit --order-by=random -c phpunit-api.xml --testdox --testdox-summary $*
TESTS_EXIT_CODE=$?

[ "$TEST_RUNTIME" = 'rr' ] && bin/rr stop -w .

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit $TESTS_EXIT_CODE
