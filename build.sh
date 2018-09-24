#!/usr/bin/env bash
set -e

if [ "$#" -ne 1 ]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
builtcontent="./build/shlink_${version}_dist"
projectdir=$(pwd)
[ -f ./composer.phar ] && composerBin='./composer.phar' || composerBin='composer'

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir -p "${builtcontent}"
rsync -av * "${builtcontent}" \
    --exclude=data/infra \
    --exclude=**/.gitignore \
    --exclude=CHANGELOG.md \
    --exclude=composer.lock \
    --exclude=vendor \
    --exclude=docs \
    --exclude=indocker \
    --exclude=docker* \
    --exclude=func_tests_bootstrap.php \
    --exclude=php* \
    --exclude=infection.json \
    --exclude=phpstan.neon \
    --exclude=config/autoload/*local* \
    --exclude=**/test* \
    --exclude=build*
cd "${builtcontent}"

# Install dependencies
echo "Installing dependencies with $composerBin..."
${composerBin} self-update
${composerBin} install --no-dev --optimize-autoloader --no-progress --no-interaction

# Delete development files
echo 'Deleting dev files...'
rm composer.*
rm -f data/database.sqlite

# Update shlink version in config
sed -i "s/%SHLINK_VERSION%/${version}/g" config/autoload/app_options.global.php

# Compressing file
echo 'Compressing files...'
cd "${projectdir}"/build
rm -f ./shlink_${version}_dist.zip
zip -ry ./shlink_${version}_dist.zip ./shlink_${version}_dist
cd "${projectdir}"
rm -rf "${builtcontent}"

echo 'Done!'
