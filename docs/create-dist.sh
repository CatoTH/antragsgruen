#!/usr/bin/env bash

# Releasing:
# - Increase version number in config/defines.php
# - Write Changelog
# - Update README.md
# - Commit this changes to repository
# - Execute this script (docs/create-dist.sh)
# - Create the new release on Github, attaching the .tar.bz2- and the .zip-file

if [[ ! -d ./controllers ]]; then
    echo "Please run this script from the project's root directory"
    exit
fi

export ANTRAGSGRUEN_VERSION=$(cat config/defines.php | grep "ANTRAGSGRUEN_VERSION" | cut -d \' -f 4)

if [[ -d ./local/antragsgruen-$ANTRAGSGRUEN_VERSION ]]; then
    rm -R ./local/antragsgruen-$ANTRAGSGRUEN_VERSION
fi

mkdir ./local
mkdir ./local/antragsgruen-$ANTRAGSGRUEN_VERSION
if [[ ! -d ./local/antragsgruen-$ANTRAGSGRUEN_VERSION ]]; then
    echo "Could not create the temporary directory"
    exit
fi

npm install
gulp

rsync -av --exclude='local' --exclude='./dist' --exclude='node_modules' --exclude='bower' --exclude='runtime' --exclude='vendor' --exclude='.git' . ./local/antragsgruen-$ANTRAGSGRUEN_VERSION

cd local/antragsgruen-$ANTRAGSGRUEN_VERSION

curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.2.2"
./composer.phar install --no-dev

rm -R local dist docker-vagrant .DS_Store .idea tsconfig.json package.json gulpfile.js
rm config/DEBUG config/config.template.json
rm composer.phar composer.json composer.lock codeception.yml phpci.yml .gitignore .travis.yml
rm web/index-test.php
mv web/index-production.php web/index.php

mkdir runtime
chmod 775 runtime
chmod 775 web/assets

find ./web/ -name "*\.map" -exec rm {} \;
rm -R web/js/src
rm -R web/js/bower
rm -R web/typescript
rm -R vendor/tecnickcom/tcpdf/examples
rm -R vendor/phpoffice/phpexcel/unitTests/
rm -R vendor/phpoffice/phpexcel/Examples/
rm -R vendor/fzaninotto/faker/
find vendor -type l -exec rm {} \;
find vendor/zendframework -name "doc" -exec rm -R {} \;
rm -R vendor/cebe/markdown/tests
rm -R tests/
find . -name ".git" -exec rm -rf {} \;
rm config/config.json
rm config/config_tests.json
touch config/INSTALLING

cd ..
tar cfj ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.tar.bz2 antragsgruen-$ANTRAGSGRUEN_VERSION
zip -r ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.zip antragsgruen-$ANTRAGSGRUEN_VERSION