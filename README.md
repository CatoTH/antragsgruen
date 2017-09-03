Antragsgrün
===========

The Online Motion Administration/Facilitator for Associations Conventions, General Assemblies and Party Conventions.

Antragsgrün offers a clear and efficient tool for the effective administration of motions, amendments and candidacies: from submission to administration and print template.

A number of organisations are already using the tool successfully such as the federal association of the German Green Party or the German Federal Youth Council. It can be easily adapted to a variety of scenarios.

Core functions:
- Submit motions, proposals and discussion papers online
- Clear amendment
- Submitted amendments are displayed directly in the relevant text section.
- Discuss motions
- Sophisticated administration tools
- Diverse export options
- Great flexibility - it adapts to a lot of different use cases
- Technically mature, data privacy-friendly

Using the hosted version / testing it
-------------------------------------

- German: [https://antragsgruen.de](https://antragsgruen.de/)
- English: [https://motion.tools](https://motion.tools/)
- French (test version only): [http://motion.tools](http://sandbox.motion.tools/createsite?language=fr)

Installation using the pre-bundled package
------------------------------------------

Requirements:
- A MySQL-database
- A fully configured web server running PHP

Installation:
- Download the latest package of Antragsgrün: [antragsgruen-3.7.0.tar.bz2](https://www.hoessl.eu/antragsgruen/antragsgruen-3.7.0.tar.bz2)
- Extract the contents into your web folder
- Access the "antragsgruen/"-folder of your web server, e.g. if you extracted the package into the web root of your host named www.example.org/, then access www.example.org/antragsgruen/
- Use the web-based installer to configure the database and further settings

Updating a existing installation using the pre-bundled package
--------------------------------------------------------------

- Download the latest package of Antragsgrün
- Extract the files to your web folder, overwriting all existing files. The configuration (in config/config.json) will not be affected by this.
- Remove the ``config/INSTALLING`` file
- If you have shell access to your server: execute ``./yii migrate`` on the command line to apply database changes
- If you don't have shell access to your server: please refer to [UPGRADING.md](docs/UPGRADING.md) on how to upgrade your database


Installation
------------


Required Software (Debian Linux):
```bash
# Using PHP7-packages from [deb.sury.org](https://deb.sury.org/):
apt-get install php7.1 php7.1-cli php7.1-fpm php7.1-intl php7.1-json php7.1-mcrypt \
                php7.1-mysql php7.1-opcache php7.1-curl php7.1-xml php7.1-mbstring php7.1-zip

# Using PHP5-packages from Debian:
apt-get install php5-cli php5-fpm php5-mysqlnd php5-mcrypt php5-intl php5-curl
```

Install the sources and dependencies from the repository:
```bash
git clone https://github.com/CatoTH/antragsgruen.git
cd antragsgruen
curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.3.1"
./composer.phar install --prefer-dist
npm install
npm run build
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



Developing custom themes
------------------------

You can develop a custom theme using SASS/SCSS for Antragsgrün using the following steps:
* Create a file ```web/css/layout-my-layout.scss``` using layout-classic.scss as a template
* Adapt the SCSS variables and add custom styles
* Run ```gulp``` to compile the SCSS into CSS
* Add a line ```'layout-my-layout' => 'My cool new layout'``` to the "localLayouts"-object in config/config.json
* Now, you can choose your new theme in the consultation settings

A hint regarding the AGPL license and themes: custom stylesheets and images and changes to the standard stylesheets of
Antragsgrün do not have to be redistributed under an AGPL license like other changes to the Antragsgrün codebase.


Creating custom language variants
---------------------------------

Every single message in the user interface can be modified using the web-based translation tool. Just log in as admin and go to Settings / Einstellungen -> Edit the language / Sprache anpassen.

In multi-site-instances, there might be a need to share language variante between different sites. In that case, file-based modifications are necessary:
* Create a directory ```messages/en-variant```
* Copy the contents of the base language (messages/en, in this case) to this directory and edit the translated strings. If a string is missing, the messages of the directory named by the first part before the dash will be used as fallback ("en", in this case).
* Add a ```localMessages```-configuration to your config/config.json as shown below.
* Now this language variant is selectable in the "Edit the language"-settings-page.
```json
{
    "localMessages": {
        "en": {
            "en-variant": "My new language variant"
        }
    }
}
```

LaTeX/XeTeX-based PDF-rendering:
--------------------------------

Necessary packets on Linux (Debian):
```bash
apt-get install texlive-lang-german texlive-latex-base texlive-latex-recommended \
                texlive-latex-extra texlive-humanities texlive-fonts-recommended \
                texlive-xetex poppler-utils
```

Necessary packets on Mac OS X:
* [MacTeX](http://www.tug.org/mactex/)
* Poppler ([Homebrew](http://brew.sh/)-Package)

Add the following settings to your config.json (and adapt them to your needs):

```json
{
    "xelatexPath": "/usr/bin/xelatex",
    "xdvipdfmx": "/usr/bin/xdvipdfmx",
    "pdfunitePath": "/usr/bin/pdfunite"
}
```

Using Redis
-----------

Install the Yii2-Redis-package:
```bash
./composer.phar require composer require yiisoft/yii2-redis
```

Add the following settings to your config.json (and adapt them to your needs):
```json
{
    "redis": {
        "hostname": "localhost",
        "port": 6379,
        "database": 0
    }
}
```


Command Line Commands
---------------------

Force a new password for an user:
```bash
./yii admin/set-user-password user@example.org mynewpassword
```


Developing
----------

You can enable debug mode by creating an empty file config/DEBUG.

To compile the JavaScript- and CSS-Files, you need to install Gulp:
```bash
npm install # Installs all required packages 

npm run build # Compiles the regular JS/CSS-files
npm run watch # Listens for changes in JS/CSS-files and compiles them immediatelly
```

After updating the source code from git, do:
```bash
./composer.phar install
./yii migrate
gulp
```

Testing
-------

### Installation

* Create a separate (MySQL-)database for testing
* Set up the configuration file: ```
cp config/config_tests.template.json config/config_tests.json && vi config/config_tests.json```
* Download [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/) and move the binary into the PATH (e.g. /usr/local/bin/)
* Download the [Selenium Standalone Server](http://www.seleniumhq.org/download/)
* For the automatical HTML validation, Java needs to be installed and the vnu.jar file from the [Nu Html Checker](https://validator.github.io/validator/) located at /usr/local/bin/vnu.jar.
* For the automatical accessibility validation, [Pa11y](http://pa11y.org/) needs to be installed. (is done by ``npm install``)
* The host name ``antragsgruen-test.local`` must point to localhost (by adding an entry to /etc/hosts) and a VirtualHost in your Apache/Nginx-Configuration pointing to the ``web/``-directory of this installation has to be configured 

### Running

* Start Selenium: ```
java -jar selenium-server-standalone-3.5.3.jar```
* Run all acceptance tests: ```
run run test:acceptance```
* Run all unit tests: ```
run run test:unit```
* Run a single acceptence-test: ```
npm run test:acceptance MotionCreateCept```


[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)