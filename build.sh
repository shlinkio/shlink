#!/usr/bin/env bash
set -e

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ] || ([ "$#" == 2 ] && [ "$2" != "--no-swoole" ]); then
  echo "Usage:" >&2
  echo "   $0 {version} [--no-swoole]" >&2
  exit 1
fi

version=$1
noSwoole=$2
phpVersion=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
[[ $noSwoole ]] && swooleSuffix="" || swooleSuffix="_openswoole"
distId="shlink${version}_php${phpVersion}${swooleSuffix}_dist"
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
    --exclude-from=./.dockerignore
cd "${builtContent}"

# Install dependencies
echo "Installing dependencies with $composerBin..."
composerFlags="--optimize-autoloader --no-progress --no-interaction"
${composerBin} self-update
${composerBin} install --no-dev --prefer-dist $composerFlags

if [[ $noSwoole ]]; then
  # If generating a dist not for openswoole, uninstall mezzio-swoole
  ${composerBin} remove mezzio/mezzio-swoole --with-all-dependencies --update-no-dev $composerFlags
fi

# Delete development files
echo 'Deleting dev files...'
rm composer.*

# Update shlink version in config
sed -i "s/%SHLINK_VERSION%/${version}/g" config/autoload/app_options.global.php

# Compressing file
echo 'Compressing files...'
cd "${projectdir}"/build
rm -f ./${distId}.zip
zip -ry ./${distId}.zip ./${distId}
cd "${projectdir}"
rm -rf "${builtContent}"

echo 'Done!'
