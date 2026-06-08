#!/bin/sh
composer install --no-interaction
echo "DROP DATABASE IF EXISTS antragsgruen; CREATE DATABASE antragsgruen" | mysql -h mysql -u root -proot
if [ "$INSTALL_OPENTELEMETRY" = "true" ]; then
    echo "Installing OpenTelemetry dependencies..."
    composer config --no-plugins allow-plugins.php-http/discovery true
    composer config --no-plugins allow-plugins.tbachert/spi true
    composer require --no-interaction open-telemetry/sdk open-telemetry/exporter-otlp open-telemetry/opentelemetry-auto-pdo open-telemetry/opentelemetry-auto-curl open-telemetry/opentelemetry-auto-guzzle php-http/guzzle7-adapter
fi
/var/www/antragsgruen/yii database/create-test --interactive=0
