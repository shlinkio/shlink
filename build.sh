#!/usr/bin/env bash
set -e

if [ "$#" -ne 1 ]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
builtcontent=$(readlink -f '../shlink_${version}_dist')
projectdir=$(pwd)

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir "${builtcontent}"
sudo chmod -R 777 "${projectdir}"/data/infra/{database,nginx}
cp -R "${projectdir}"/* "${builtcontent}"
cd "${builtcontent}"

# Install dependencies
rm -r vendor
rm composer.lock
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
rm php*
rm README.md
rm -r build
rm -f data/database.sqlite
rm -rf data/infra
rm -rf data/{cache,log,proxies}/{*,.gitignore}
rm -rf config/params/{*,.gitignore}
rm -rf config/autoload/{{,*.}local.php{,.dist},.gitignore}

# Compressing file
rm -f "${projectdir}"/build/shlink_${version}_dist.zip
zip -ry "${projectdir}"/build/shlink_${version}_dist.zip .
rm -rf "${builtcontent}"
