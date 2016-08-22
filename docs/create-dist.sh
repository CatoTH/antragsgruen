#!/usr/bin/env bash

# Releasing:
# - Increase version number in config/defines.php
# - Write Changelog
# - Execute this script (bin/create-dist.sh)
# - Upload the generated .tar.bz2-file
# - Update README.md
# - Commit this changes to repository and tag the new version

if [[ ! -d ./controllers ]]; then
    echo "Please run this script from the project's root directory"
    exit
fi

export ANTRAGSGRUEN_VERSION=$(cat config/defines.php | grep "ANTRAGSGRUEN_VERSION" | cut -d \' -f 4)

if [[ -d ./local/antragsgruen ]]; then
    rm -R ./local/antragsgruen
fi

mkdir ./local
mkdir ./local/antragsgruen
if [[ ! -d ./local/antragsgruen ]]; then
    echo "Could not create the temporary directory"
    exit
fi

rsync -av --exclude='local' --exclude='./dist' --exclude='node_modules' --exclude='bower' --exclude='runtime' --exclude='vendor' --exclude='.git' . ./local/antragsgruen

cd local/antragsgruen

curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.2.1"
./composer.phar install --no-dev

rm -R local dist docker-vagrant
rm composer.phar composer.json composer.lock codeception.yml phpci.yml .gitignore .travis.yml
rm web/index-test.php
mv web/index-production.php web/index.php

mkdir runtime
chmod 775 runtime
chmod 775 web/assets

cd web/js/bower/intl/locale-data
find . -type f ! -name "de*" -exec rm {} \;
cd ../../../../../
rm -R web/js/bower/moment/src/
rm -R vendor/phpoffice/phpexcel/unitTests/
rm -R vendor/phpoffice/phpexcel/Examples/
rm -R vendor/fzaninotto/faker/
rm -R tests/
find . -name ".git" -exec rm -rf {} \;
rm config/config.json
rm config/config_tests.json
touch config/INSTALLING

cd ..
tar cfj ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.tar.bz2 antragsgruen
