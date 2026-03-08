#!/bin/sh
composer install
echo "CREATE DATABASE antragsgruen" | mysql -h mysql -u root -proot
composer require open-telemetry/sdk open-telemetry/exporter-otlp open-telemetry/opentelemetry-auto-pdo open-telemetry/opentelemetry-auto-curl open-telemetry/opentelemetry-auto-guzzle php-http/guzzle7-adapter -y
/var/www/antragsgruen/yii database/create-test --interactive=0
