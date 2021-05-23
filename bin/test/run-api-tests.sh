#!/usr/bin/env sh
export APP_ENV=test
export DB_DRIVER=postgres
export TEST_ENV=api

rm -rf data/log/api-tests

# Try to stop server just in case it hanged in last execution
vendor/bin/laminas mezzio:swoole:stop

echo 'Starting server...'
vendor/bin/laminas mezzio:swoole:start -d
sleep 2

vendor/bin/phpunit --order-by=random -c phpunit-api.xml --testdox --colors=always --log-junit=build/coverage-api/junit.xml $*
testsExitCode=$?

vendor/bin/laminas mezzio:swoole:stop

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit $testsExitCode
