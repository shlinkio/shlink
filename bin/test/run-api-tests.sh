#!/usr/bin/env sh
set -e

export APP_ENV=test

# Try to stop server just in case it hanged in last execution
vendor/bin/zend-expressive-swoole stop

echo 'Starting server...'
vendor/bin/zend-expressive-swoole start -d
sleep 2

APP_ENV=test DB_DRIVER=mysql vendor/bin/phpunit --order-by=random -c phpunit-api.xml --testdox --colors=always
vendor/bin/zend-expressive-swoole stop
