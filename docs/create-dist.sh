#!/usr/bin/env bash

# Releasing:
# - Increase version number in config/defines.php
# - Write Changelog
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

rsync -av --exclude='local' --exclude='/dist' --exclude='/updates' --exclude='/plugins' --exclude='node_modules' --exclude='bower' --exclude='runtime' --exclude='vendor' --exclude='.git' . ./local/antragsgruen-$ANTRAGSGRUEN_VERSION

cd local/antragsgruen-$ANTRAGSGRUEN_VERSION

curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.4.5"
./composer.phar install --no-dev

rm -R local dist updates docker-vagrant .DS_Store .idea tsconfig.json package.json gulpfile.js
rm config/DEBUG config/config.template.json
rm composer.phar composer.lock codeception.yml phpci.yml .gitignore .travis.yml .editorconfig
rm config/TEST_DOMAIN
mv web/index-production.php web/index.php
rm docs/create-dist.sh docs/create-update.php

mkdir plugins
cp ../../plugins/*php plugins/

mkdir runtime
chmod 775 runtime
chmod 775 web/assets

find ./web/ -name "*\.map" -exec rm {} \;
rm -R web/js/src
rm -R web/js/bower
rm -R web/typescript
rm -R vendor/tecnickcom/tcpdf/examples
rm -R vendor/swiftmailer/swiftmailer/tests
rm -R vendor/doctrine/lexer/tests
rm -R vendor/ezyang/htmlpurifier/tests
rm -R vendor/yiisoft/yii2/test
rm -R vendor/mailjet/mailjet-apiv3-php/test
rm -R vendor/fxp/composer-asset-plugin/Tests
rm -R vendor/leafo/scssphp/bin
rm -R vendor/cebe/markdown/bin
rm -R vendor/yiisoft/yii2/i18n/migrations
rm -R vendor/yiisoft/yii2/log/migrations
find vendor -type l -exec rm {} \;
rm -R vendor/cebe/markdown/tests
rm -R tests/
find . -name ".DS_Store" -exec rm {} \;
find . -name ".git" -exec rm -rf {} \;
find . -name ".gitignore" -exec rm {} \;
find . -name ".gitattributes" -exec rm {} \;
find . -name ".travis" -exec rm {} \;
rm config/config.json
rm config/config_tests.json
touch config/INSTALLING
cp config/.htaccess runtime/
cp config/.htaccess plugins/
cp config/.htaccess vendor/
cp config/.htaccess node_modules/
sed -i -e 's/repository\-source/dist/g' config/defines.php
rm config/defines.php-e

cd ..
tar cfj ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.tar.bz2 antragsgruen-$ANTRAGSGRUEN_VERSION
zip -r ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.zip antragsgruen-$ANTRAGSGRUEN_VERSION
mv antragsgruen-$ANTRAGSGRUEN_VERSION ../dist/
