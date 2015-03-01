Antragsgrün v3
==============

[Etwas über Antragsgrün]

***Current Build-Status***

[![Build Status](http://phpci.hoessl.eu/build-status/image/1?branch=v3)](http://phpci.hoessl.eu/build-status/view/1?branch=v3)


Development Setup
-----------------

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install

composer global require "fxp/composer-asset-plugin:1.0.0"
composer install --dev
```


```bash
scss --precision 9
```


Testing
-------

* [PhantomJS](http://phantomjs.org/download.html) installieren
* PhantomJS starten: phantomjs --webdriver=4444
* Debug-Server starten: tests/start_debug_server.sh
* Alle Tests ausführen: tests/run.sh
* Einzelnen Acceptence-Test ausführen: cd tests && ../vendor/bin/codecept run acceptance MotionCreateCept

