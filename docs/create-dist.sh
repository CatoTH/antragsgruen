if [[ ! -d ./controllers ]]; then
    echo "Please run this script from the project's root directory"
    exit
fi

if [[ -d ./local/build-dist ]]; then
    rm -R ./local/build-dist
fi

mkdir ./local
mkdir ./local/build-dist
if [[ ! -d ./local/build-dist ]]; then
    echo "Could not create the temporary directory"
    exit
fi

rsync -av --exclude='./local' --exclude='.git' . ./local/build-dist

cd local/build-dist

curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.1.2"
./composer.phar install --prefer-dist

rm -R local dist docker-vagrant
rm composer.phar composer.json composer.lock codeception.yml phpci.yml .gitignore .travis.yml
rm web/index-test.php
mv web/index-production.php web/index.php

cd web/js/bower/intl/locale-data
find . -type f ! -name "de*" -exec rm {} \;
cd ../../../../../
rm -R web/js/bower/moment/src/
rm -R vendor/phpoffice/phpexcel/unitTests/
rm -R vendor/phpoffice/phpexcel/Examples/
rm -R vendor/fzaninotto/faker/
find . -name ".git" -exec rm -r {} \;
rm config/config.json
rm config/config/config_tests.json
touch config/INSTALLING

