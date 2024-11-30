#!/usr/bin/env bash

# Releasing:
# - Increase version number in config/defines.php
# - Write Changelog
# - Update SECURITY.md
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

npm ci
npx gulp

rsync -av --exclude='local' --exclude='/dist' --exclude='/updates' --exclude='/plugins' --exclude='node_modules' --exclude='bower' --exclude='runtime' --exclude='vendor' --exclude='.git' . ./local/antragsgruen-$ANTRAGSGRUEN_VERSION

cd local/antragsgruen-$ANTRAGSGRUEN_VERSION

curl -sS https://getcomposer.org/installer | php
./composer.phar install --no-dev

rm -R local dist updates docker-vagrant .DS_Store .idea tsconfig.json package.json gulpfile.js phpstan.neon
rm config/DEBUG config/config.template.json
rm composer.phar composer.lock codeception.yml phpci.yml .gitignore .travis.yml .editorconfig
rm package-lock.json composer.json
rm config/TEST_DOMAIN
rm assets/phpstan-helper.php
rm -R assets/OpenOffice-Template-ods
rm -R assets/OpenOffice-Template-odt
mv web/index-production.php web/index.php
rm docs/create-dist.sh docs/create-update.php

mkdir plugins
cp ../../plugins/*php plugins/

mkdir runtime
chmod 775 runtime
chmod 775 web/assets

find ./web/ -name "*\.map" -exec rm {} \;
chmod -R u+rwx web/js/bower/yii2-pjax/.git
rm -R web/js/src
rm -R web/js/bootstrap-datetimepicker.js
rm -R web/js/bower
rm -R web/typescript
rm -R vendor/tecnickcom/tcpdf/examples
rm -R vendor/swiftmailer/swiftmailer/tests
rm -R vendor/doctrine/lexer/tests
rm -R vendor/ezyang/htmlpurifier/tests
rm -R vendor/mailjet/mailjet-apiv3-php/test
rm -R vendor/symfony/http-client-contracts/Test
rm -R vendor/symfony/service-contracts/Test
rm -R vendor/scssphp/scssphp/bin
rm -R vendor/cebe/markdown/bin
rm -R vendor/predis/predis/docker
rm -R vendor/yiisoft/yii2/i18n/migrations
rm -R vendor/yiisoft/yii2/log/migrations
rm -R vendor/bower-asset
rm -Rf vendor/s1syphos/php-simple-captcha/demo/
rm -Rf vendor/s1syphos/php-simple-captcha/tests/
find vendor/ -name "README.md" -exec rm {} \;
find vendor/ -name "CHANGELOG.md" -exec rm {} \;
rm ./vendor/s1syphos/php-simple-captcha/fonts/LinLibertine_Rah.ttf
rm ./vendor/s1syphos/php-simple-captcha/fonts/Hack-Regular.ttf
rm ./vendor/s1syphos/php-simple-captcha/fonts/Bitter-Bold.ttf
rm ./vendor/s1syphos/php-simple-captcha/fonts/Vollkorn-Regular.ttf
rm -R vendor/endroid/qr-code/assets/ # Replaced by bundled TTF
find vendor -type l -exec rm {} \;
rm -R vendor/cebe/markdown/tests
rm -R tests/
rm migrations/m1*
rm migrations/m20*
rm migrations/m21*
rm migrations/m22*
find . -name ".DS_Store" -exec rm {} \;
find . -name ".git" -exec rm -rf {} \;
find . -name ".github" -exec rm -rf {} \;
find . -name ".gitignore" -exec rm {} \;
find . -name ".gitattributes" -exec rm {} \;
find . -name ".php-cs-fixer.php" -exec rm {} \;
find . -name ".editorconfig" -exec rm {} \;
find . -name ".woodpecker.yml" -exec rm {} \;
find . -name ".travis" -exec rm {} \;
find . -name ".travis.yml" -exec rm {} \;
find . -name ".scrutinizer.yml" -exec rm {} \;
find . -name ".php-cs-fixer.dist.php" -exec rm {} \;
find . -name "phpunit9.xml.dist" -exec rm {} \;
find . -name ".php_cs.dist" -exec rm {} \;
find . -name ".phpstorm.meta.php" -exec rm {} \;
find . -name "phpunit.xml" -exec rm {} \;
find . -name ".eslintrc.js" -exec rm {} \;
find . -name "phpstan-baseline.neon" -exec rm {} \;
find . -name "phpstan.neon.dist" -exec rm {} \;
find . -name ".readthedocs.yaml" -exec rm {} \;
find . -name ".phpstan.neon.dist" -exec rm {} \;
find . -name ".phpcs.xml.dist" -exec rm {} \;
find . -name ".phpcs.xml" -exec rm {} \;
find . -name "CONTRIBUTING.md" -exec rm {} \;
find . -name "CHANGELOG.TXT" -exec rm {} \;
find . -name "composer.json" -exec rm {} \;
find . -name "Makefile" -exec rm {} \;
find . -name "UPGRADE.md" -exec rm {} \;
rm runtime/logs/app.log
rm ./vendor/bin/json5
rm vendor/colinodell/json5/bin/json5
rm config/config.json
rm config/config_tests.json
touch config/INSTALLING
cp config/.htaccess runtime/
cp config/.htaccess plugins/
cp config/.htaccess vendor/
sed -i -e 's/repository\-source/dist/g' config/defines.php
rm config/defines.php-e
rm phpstan.use-baseline.neon
rm eslint.config.mjs
rm .eslintrc.cjs
rm .eslintrc.js

cd ..
tar cfj ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.tar.bz2 antragsgruen-$ANTRAGSGRUEN_VERSION
zip -r ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.zip antragsgruen-$ANTRAGSGRUEN_VERSION
mv antragsgruen-$ANTRAGSGRUEN_VERSION ../dist/
