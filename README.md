Antragsgrün
===========

Antragsgrün 3 is the third generation of the Content Management System built for the german green party.
It's a complete rewrite of the second generation and has some major advantages:
* It's much more flexible on the structure of motions and wording. It even supports image uploading, e.g. for applications.
* It supports multiple layouts for the website and the PDF-Export.
* If follows test-driven design, using both unit and acceptance tests
* The internal text is based completely on HTML and gets rid of the obsolete BBCode
* The design of the motion supporter table is much cleaner and does not depend on the user database anymore
* It's based on the more modern Yii2-framework
* Many more small improvements


Required Software (Debian Linux)
--------------------------------
```bash
# Using PHP7-packages from [DotDeb](https://www.dotdeb.org/instructions/):
apt-get install php7.0 php7.0-cli php7.0-fpm php7.0-intl php7.0-json php7.0-mcrypt php7.0-mysql php7.0-opcache php7.0-curl

# Using PHP5-packages from Debian:
apt-get install php5-cli php5-fpm php5-mysqlnd php5-mcrypt php5-intl php5-curl
```

Optional, for LaTeX/XeTeX-based PDFs:
```bash
apt-get install texlive-lang-german texlive-latex-base texlive-latex-recommended \
                texlive-latex-extra texlive-humanities texlive-fonts-recommended \
                texlive-xetex poppler-utils
```

Required Software (Mac OS X)
----------------------------

LaTeX/XeTeX-based PDFs:
* [MacTeX](http://www.tug.org/mactex/)
* Poppler ([Homebrew](http://brew.sh/)-Package)


Installation from the repository
--------------------------------

```bash
git clone https://github.com/CatoTH/antragsgruen.git
cd antragsgruen
curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.1.1"
./composer.phar install --prefer-dist
sudo npm install -g gulp
npm install gulp gulp-uglify gulp-concat gulp-concat-css gulp-minify-css gulp-sass gulp-sourcemaps
```

If you want to use the web-based installer (recommended):
```bash
touch config/INSTALLING
```

If you don't want to use the web-based installer:
```bash
cp config/config.template.json config/config.json
vi config/config.json # you're on your own now :-)
```

Set the permissions (example for Debian Linux):
```bash
sudo chown -R www-data:www-data web/assets
sudo chown -R www-data:www-data runtime
sudo chown -R www-data:www-data config #Can be skipped if you don't use the Installer
```

Set the permissions (example for Mac OS X):
```bash
sudo chown -R _www:_www web/assets
sudo chown -R _www:_www runtime
sudo chown -R _www:_www config #Can be skipped if you don't use the Installer
```

Set up the virtual host of your web server. Example files are provided here:
* Example configuration for [nginx](docs/nginx.sample_single_site.conf)
* Example configuration for [apache](docs/apache.sample.conf)

Command Line Commands
---------------------

Force a new password for an user:
```bash
./yii admin/set-user-password user@example.org mynewpassword
```


Running using docker
--------------------

NOT WORKING RIGHT NOW!

```bash
cd docker-vagrant/
docker build -t antragsgruen1 -f Dockerfile .
docker run -p 80:80 --name antragsgruen1 -d antragsgruen1
```


Developing
----------

You can enable debug mode by creating an empty file config/DEBUG.

After updating the source code from git, do:
```bash
./composer.phar update
./yii migrate
gulp
```

Testing
-------

* Create a separate (MySQL-)database for testing
* Set up the configuration file: ```bash
cp config/config_tests.template.json config/config_tests.json
vi config/config_tests.json```
* Install [PhantomJS](http://phantomjs.org/download.html)
* For the automatical HTML validation, Java needs to be installed and the vnu.jar file from the [Nu Html Checker](https://validator.github.io/validator/) located at /usr/local/bin/vnu.jar.
* For the automatical accessibility validation, [Pa11y](http://pa11y.org/) needs to be installed.
* Start PhantomJS: ```bash
phantomjs --webdriver=4444```
* Start debug server: ```bash
tests/start_debug_server.sh```
* Run all tests: ```bash
vendor/bin/codecept run```
* Run a single acceptence-test: ```bash
vendor/bin/codecept run acceptance MotionCreateCept```


