#!/bin/sh
composer install
echo "CREATE DATABASE antragsgruen" | mysql -h mysql -u root -proot
/var/www/antragsgruen/yii database/create-test --interactive=0
