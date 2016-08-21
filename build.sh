#!/usr/bin/env bash
set -e

if [ "$#" -ne 1 ]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
builtcontent=$(readlink -f '../shlink_build_tmp')
projectdir=$(pwd)

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir "${builtcontent}"
cp -R "${projectdir}"/* "${builtcontent}"
cd "${builtcontent}"

# Install dependencies
rm -r vendor
rm composer.lock
composer self-update
composer install --no-dev --optimize-autoloader

# Delete development files
echo 'Deleting dev files...'
rm build.sh
rm CHANGELOG.md
rm composer.*
rm LICENSE
rm php*
rm README.md
rm -r build
rm -f data/database.sqlite
rm -rf data/{cache,log,proxies}/{*,.gitignore}
rm -rf config/params/{*,.gitignore}
rm -rf config/autoload/{{,*.}local.php{,.dist},.gitignore}

# Compressing file
rm -f "${projectdir}"/build/shlink_${version}_dist.zip
zip -r "${projectdir}"/build/shlink_${version}_dist.zip .
rm -rf "${builtcontent}"
