#!/usr/bin/env bash
set -e

if [ "$#" -lt 1 ]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
phpVersion=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
distId="shlink${version}_php${phpVersion}_dist"
builtContent="./build/${distId}"
projectdir=$(pwd)
[[ -f ./composer.phar ]] && composerBin='./composer.phar' || composerBin='composer'

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtContent}"
mkdir -p "${builtContent}"
rsync -av * "${builtContent}" \
    --exclude=*docker* \
    --exclude=Dockerfile \
    --include=.htaccess \
    --include=config/roadrunner/.rr.yml \
    --exclude-from=./.dockerignore
cd "${builtContent}"

# Install dependencies
echo "Installing dependencies with $composerBin..."
${composerBin} self-update
${composerBin} install --no-dev --prefer-dist --optimize-autoloader --no-progress --no-interaction

# Delete development files
echo 'Deleting dev files...'
rm composer.*

# Update Shlink version
sed -i "s/%SHLINK_VERSION%/${version}/g" module/Core/src/Config/Options/AppOptions.php

# Compressing file
echo 'Compressing files...'
cd "${projectdir}"/build
rm -f ./${distId}.zip
zip -ry ./${distId}.zip ./${distId}
cd "${projectdir}"
rm -rf "${builtContent}"

echo 'Done!'
