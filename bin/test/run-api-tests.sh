#!/usr/bin/env sh
export APP_ENV=test
export DB_DRIVER=mysql

# Try to stop server just in case it hanged in last execution
vendor/bin/zend-expressive-swoole stop

echo 'Starting server...'
vendor/bin/zend-expressive-swoole start -d
sleep 2

vendor/bin/phpunit --order-by=random -c phpunit-api.xml --testdox --colors=always

# Capture tests exit code
testsExitCode=$?

vendor/bin/zend-expressive-swoole stop

# Exit this script with the same code as the tests. If tests failed, this script has to fail
exit testsExitCode
