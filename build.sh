#!/usr/bin/env bash
set -e

if [[ "$#" -ne 1 ]]; then
  echo "Usage:" >&2
  echo "   $0 {version}" >&2
  exit 1
fi

version=$1
builtcontent="./build/shlink_${version}_dist"
projectdir=$(pwd)
[[ -f ./composer.phar ]] && composerBin='./composer.phar' || composerBin='composer'

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir -p "${builtcontent}"
rsync -av * "${builtcontent}" \
    --exclude=*docker* \
    --exclude=Dockerfile \
    --include=.htaccess \
    --exclude-from=./.dockerignore
cd "${builtcontent}"

# Install dependencies
echo "Installing dependencies with $composerBin..."
${composerBin} self-update
${composerBin} install --no-dev --optimize-autoloader --prefer-dist --no-progress --no-interaction

# Copy mezzio helper script to vendor (deprecated - Remove with Shlink 3.0.0)
cp "${projectdir}/bin/helper/mezzio-swoole" "./vendor/bin"

# Delete development files
echo 'Deleting dev files...'
rm composer.*

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
