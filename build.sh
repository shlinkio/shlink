#!/usr/bin/env bash
set -e

if [ "$#" -ne 1 ]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
builtcontent=$(readlink -f "../shlink_${version}_dist")
projectdir=$(pwd)

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir "${builtcontent}"
sudo chmod -R 777 "${projectdir}"/data/infra/{database,nginx}
cp -R "${projectdir}"/* "${builtcontent}"
cd "${builtcontent}"

# Install dependencies
rm -rf vendor
rm -f composer.lock
composer self-update
composer install --no-dev --optimize-autoloader --no-progress --no-interaction

# Delete development files
echo 'Deleting dev files...'
rm build.sh
rm CHANGELOG.md
rm composer.*
rm LICENSE
rm indocker
rm docker-compose.yml
rm docker-compose.override.yml
rm docker-compose.override.yml.dist
rm func_tests_bootstrap.php
rm php*
rm README.md
rm infection.json
rm -rf build
rm -ff data/database.sqlite
rm -rf data/infra
rm -rf data/{cache,log,proxies}/{*,.gitignore}
rm -rf config/params/{*,.gitignore}
rm -rf config/autoload/{{,*.}local.php{,.dist},.gitignore}

# Update shlink version in config
latestShlinkVersion=$(git tag -l --sort=-v:refname | head -n 1)
computedVersion=${latestShlinkVersion:1:${#latestShlinkVersion}}
sed -i "s/%SHLINK_VERSION%/${computedVersion}/g" config/autoload/app_options.global.php

# Compressing file
rm -f "${projectdir}"/build/shlink_${version}_dist.zip
zip -ry "${projectdir}"/build/shlink_${version}_dist.zip "../shlink_${version}_dist"
rm -rf "${builtcontent}"
