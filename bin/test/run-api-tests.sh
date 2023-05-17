#!/usr/bin/env sh

export APP_ENV=test
export TEST_ENV=api
export TEST_RUNTIME="${TEST_RUNTIME:-"rr"}"
export DB_DRIVER="${DB_DRIVER:-"postgres"}"
export GENERATE_COVERAGE="${GENERATE_COVERAGE:-"no"}"

# Reset logs
OUTPUT_LOGS=data/log/api-tests/output.log
rm -rf data/log/api-tests
mkdir data/log/api-tests
touch $OUTPUT_LOGS

# Try to stop server just in case it hanged in last execution
[ "$TEST_RUNTIME" = 'openswoole' ] && vendor/bin/laminas mezzio:swoole:stop
[ "$TEST_RUNTIME" = 'rr' ] && bin/rr stop -f

echo 'Starting server...'
[ "$TEST_RUNTIME" = 'openswoole' ] && vendor/bin/laminas mezzio:swoole:start -d
[ "$TEST_RUNTIME" = 'rr' ] && bin/rr serve -p -c=config/roadrunner/.rr.dev.yml \
  -o=http.address=0.0.0.0:9999 \
  -o=logs.encoding=json \
  -o=logs.channels.http.encoding=json \
  -o=logs.channels.server.encoding=json \
  -o=logs.output="${PWD}/${OUTPUT_LOGS}" \
  -o=logs.channels.http.output="${PWD}/${OUTPUT_LOGS}" \
  -o=logs.channels.server.output="${PWD}/${OUTPUT_LOGS}" &
sleep 2 # Let's give the server a couple of seconds to start

vendor/bin/phpunit --order-by=random -c phpunit-api.xml --testdox --colors=always --log-junit=build/coverage-api/junit.xml $*
testsExitCode=$?

[ "$TEST_RUNTIME" = 'openswoole' ] && vendor/bin/laminas mezzio:swoole:stop
[ "$TEST_RUNTIME" = 'rr' ] && bin/rr stop -c config/roadrunner/.rr.dev.yml -o=http.address=0.0.0.0:9999

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit $testsExitCode
