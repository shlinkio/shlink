#!/usr/bin/env bash

builtcontent=$(readlink -f '../shlink_build_tmp')
projectdir=$(pwd)

# Copy project content to temp dir
echo 'Copying project files...'
rm -rf "${builtcontent}"
mkdir "${builtcontent}"
cp -R "${projectdir}"/* "${builtcontent}"
cd ${builtcontent}

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
rm data/database.sqlite
rm data/{cache,log,proxies}/{*,.gitignore}
rm config/params/{*,.gitignore}
rm config/autoload/{*.local.php{,.dist},.gitignore}

# Compressing file
