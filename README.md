Antragsgrün
===========

Antragsgrün offers a clear and efficient tool for the effective administration of motions, amendments and candidacies: from submission to administration and print template.

A number of organisations are using the tool successfully such as the federal association of the german Green Party, the German Federal Youth Council or the austrian NEOS.
It can be easily adapted to a variety of scenarios.

Core functions:
- Submit motions, proposals and discussion papers online
- Clear amendment
- Submitted amendments are displayed directly in the relevant text section.
- Discuss motions
- Sophisticated administration tools
- Diverse export options
- Great flexibility - it adapts to a lot of different use cases
- Technically mature, data privacy-friendly
- Accessible, following WCAG AA

Using the hosted version / testing it
-------------------------------------

- German: [https://antragsgruen.de](https://antragsgruen.de/)
- English: [https://motion.tools](https://motion.tools/), [https://discuss.green](https://discuss.green/)
- French: [https://discuss.green](https://discuss.green/)

Installation
------------------------------------------

### Using the pre-bundled package

#### Requirements:

- A MySQL-database
- PHP >= 7.1. Recommended: 7.2+. Required packages for Debian Linux:

```bash
# Using PHP7-packages from [deb.sury.org](https://deb.sury.org/):
apt-get install php7.4 php7.4-cli php7.4-fpm php7.4-intl php7.4-json php7.4-gd \
                php7.4-mysql php7.4-opcache php7.4-curl php7.4-xml php7.4-mbstring php7.4-zip
```

- Apache or nginx. Example files are provided here:
  - Example configuration for [nginx](docs/nginx.sample_single_site.conf)
  - Example configuration for [apache](docs/apache.sample.conf)

#### Installation:

- Download the [latest ZIP/BZIP2-package of Antragsgrün](https://github.com/CatoTH/antragsgruen/releases/latest).
- Extract the contents into your web folder
- Access the "antragsgruen/"-folder of your web server, e.g. if you extracted the package into the web root of your host named www.example.org/, then access www.example.org/antragsgruen/
- Use the web-based installer to configure the database and further settings

### From the sources

Install the sources and dependencies from the repository:

```bash
git clone https://github.com/CatoTH/antragsgruen.git
cd antragsgruen
curl -sS https://getcomposer.org/installer | php
./composer.phar global require "fxp/composer-asset-plugin:1.4.6"
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

### Using Docker

A Dockerfile to compile and run the latest development version of Antragsgrün is provided by [Jugendpresse Deutschland e.V.](https://www.jugendpresse.de) at this repository:

[https://github.com/jugendpresse/docker-antragsgruen](https://github.com/jugendpresse/docker-antragsgruen)

## Updating

### Using the web-based updater

Site administrators of an installation will see a Update-Box at the right side of the administration page of a consultation. The box indicates if an update is available. If so, you can switch the whole installation into Update mode. While the update mode is active, the whole site will not be available to other users.

Once the update mode is active, the ``/update.php`` script will be available to the site administrator. Here, the update can be performed in two to three steps:

- Download the update file
- Install the new files
- Apply database updates (this is usually only necessary when upgrading to a new minor version, e.g. from 3.9 to 3.10.)

Before using the updater, it is generally a good idea to back up all files and especially the database.

If you encounter any problem using the web-based updater, please consult the [Update Troubleshooting FAQ](docs/update-troubleshooting.md).

### Using the pre-bundled package

- Download the latest package of Antragsgrün (see "Installation")
- Extract the files to your web folder, overwriting all existing files. The configuration (in config/config.json) will not be affected by this.
- Remove the ``config/INSTALLING`` file
- If you have shell access to your server: execute ``./yii migrate`` on the command line to apply database changes
- If you don't have shell access to your server: please refer to [UPGRADING.md](docs/UPGRADING.md) on how to upgrade your database

## Deployment techniques

### LaTeX/XeTeX-based PDF-rendering:

Necessary packets on Linux (Debian):
```bash
apt-get install texlive-lang-german texlive-latex-base texlive-latex-recommended \
                texlive-latex-extra texlive-humanities texlive-fonts-recommended \
                texlive-xetex texlive-luatex poppler-utils
```

Necessary packets on Mac OS X:
* [MacTeX](http://www.tug.org/mactex/)
* Poppler ([Homebrew](http://brew.sh/)-Package)

Add the following settings to your config.json (and adapt them to your needs):

```json
{
    "lualatexPath": "/usr/bin/lualatexPath",
    "pdfunitePath": "/usr/bin/pdfunite"
}
```

### FPDI-PDF

If you run into the error "This PDF document probably uses a compression technique which is not supported by the free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)" and decide to use the commercial plugin, you can install the package using the following steps:
- Extract the content of the package into the directory ``components/fpdi``, so there exists a sub-directory ``src``.
- Run the command ``./composer.phar dump-autoload``

After that, newer PDF files should be able to be parsed as well.


### Multisite-Mode

There are two ways to deploy multiple sites using one codebase, each site allowing multiple consultations. However, both of them are non-trivial.

#### Using a completely separate configuration and database

If you want to use two completely different databases, or a different set of active plugins, you can create a separate ``config.json`` for each installation and name them like ``config.db1.json``, ``config.db2.json``, etc. Which one is used on a request depends on the environment variable ``ANTRAGSGRUEN_CONFIG``that is provided to the PHP process. For example, to use ``config.db1.json`` on the hostname ``db1.antragsgruen.local`` on Apache, you can use the following line in the Apache configuration:

``SetEnvIf Host "db1.antragsgruen.local" ANTRAGSGRUEN_CONFIG=/var/www/antragsgruen/config/config.db1.json``

For command line commands, you can set this variable like this:

``ANTRAGSGRUEN_CONFIG=/var/www/antragsgruen/config/config.db1.json ./yii database/migrate``

#### Using the same database, plugin configuration and a site manager

[Antragsgruen.de](https://www.antragsgruen.de/) uses a site manager module on the home page that allows users to create their own sites using a web form. This is done using the ``multisideMode`` and a plugin for the site manager. Relevant entries in the ``config.json`` for this are:

```json
{
    "multisiteMode": true,
    "siteSubdomain": null,
    "domainPlain": "https://antragsgruen.de/",
    "domainSubdomain": "https://<subdomain:[\\w_-]+>.antragsgruen.de/",
    "plugins": ["antragsgruen_sites"]
}
```

Instead of "antragsgruen_sites", a custom plugin managing the authentication and authorization process and providing the custom home page is necessary for this use case. The default manager [antragsgruen_sites](plugins/antragsgruen_sites/) can be used as an example for this


### Using Redis

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


### Command Line Commands

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

### Creating custom language variants

Every single message in the user interface can be modified using the web-based translation tool. Just log in as admin and go to Settings / Einstellungen -> Edit the language / Sprache anpassen.

In multi-site-instances, there might be a need to share language variante between different sites. In that case, file-based modifications are necessary:

- Create a directory ```messages/en-variant```
- Copy the contents of the base language (messages/en, in this case) to this directory and edit the translated strings. If a string is missing, the messages of the directory named by the first part before the dash will be used as fallback ("en", in this case).
- Add a ```localMessages```-configuration to your config/config.json as shown below.
- Now this language variant is selectable in the "Edit the language"-settings-page.

```json
{
    "localMessages": {
        "en": {
            "en-variant": "My new language variant"
        }
    }
}
```

### Updating PDF.JS

* Download the [latest release](https://github.com/mozilla/pdf.js/releases)
* `npm install`
* `gulp dist-install`
* Copy relevant files, redo changes in `viewer.html` and `viewer.css` (look for "Antragsgrün" in the comments)

### Accessibility

The goal is to comply with both WCAG 2.0 AA and BITV2.0.

Testing is currently done the following ways:

- Ensuring that all functionality is accessible with the keyboard.
- Screenreader functionality is currently tested using VoiceOver.
- For validation, [Total Validator](https://www.totalvalidator.com/) and the Mozilla Firefox accessibility validation is used. **Known limitations** of Total Validator here are the inaccurate contrast checking for gradients in headlines and buttons. For Firefox, the main limitation is that it inaccurately classifys elements as interactive that have a `$(element).on("click", ".subselecor", handler)`-listener that is actually targeted to dynamic child elements.

Known limitations:

- Reordering objects (like agenda items) does not work yet using the keyboard
- When developer mode is activated, the debug bar produces several accessibility issues

## Plugins

**The plugin system is still under heavy development.**

* The plugin system is based on Yii2's [module system](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) and [asset bundles](https://www.yiiframework.com/doc/guide/2.0/en/structure-assets). 
* Each plugins has a directory under [plugins/](plugins/). It requires at least a ``Module.php`` which inherits from [ModuleBase.php](plugins/ModuleBase.php).
* Custom URLs can be defined in the Modules.php, the corresponding controllers are in the ``controller``-subdirectory, the views in ``views``, custom commands need to be in a ``commands``-directory. A rather complex exmple containing a bit of everything can be seen in [member_petitions](plugins/member_petitions/).
* Each plugin has a unique ID that is equivalent to the name of the directory. To activate a plugin, the ID has to be added to the ``plugins``-list in the ``config.json``:

```json
{
    "plugins": [
        "mylayoutPlugin",
        "someExtraBehavior"
    ]
}
```

### Custom themes as plugin

The most frequent use case for plugins are custom themes / layouts. You can develop a custom theme using SASS/SCSS for Antragsgrün using the following steps:

- Create a directory for the plugin with a ``Module.php`` and ``Assets.php``. If your directory / plugin ID is ``mylayout``, the namespace of these classes needs to be ``app\plugins\mylayout``.
- The ``Module.php`` needs the static method ``getProvidedLayout`` that returns the asset bundle. See the [gruen_ci](plugins/gruen_ci/Module.php) or [neos](plugins/neos/Module.php) for examples.
- Create a file ```plugins/mylayout/assets/mylayout.scss```. Again, use the existing plugins as an example to get the imports right.
- Adapt the SCSS variables and add custom styles
- Run ```gulp``` to compile the SCSS into CSS
- Activate the plugin as said above.
- Now, you can choose your new theme in the consultation settings

A hint regarding the AGPL license and themes: custom stylesheets and images and changes to the standard stylesheets of
Antragsgrün do not have to be redistributed under an AGPL license like other changes to the Antragsgrün codebase.

## Testing

#### Installation

* Create a separate (MySQL-)database for testing
* Set up the configuration file: ```
cp config/config_tests.template.json config/config_tests.json && vi config/config_tests.json```
* Download [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/) and move the binary into the PATH (e.g. /usr/local/bin/)
* Download the [Selenium Standalone Server](http://www.seleniumhq.org/download/)
* For the automatical HTML validation, Java needs to be installed and the vnu.jar file from the [Nu Html Checker](https://validator.github.io/validator/) located at /usr/local/bin/vnu.jar.
* For the automatical accessibility validation, [Pa11y](http://pa11y.org/) needs to be installed. (is done by ``npm install``)
* The host name ``antragsgruen-test.local`` must point to localhost (by adding an entry to /etc/hosts) and a VirtualHost in your Apache/Nginx-Configuration pointing to the ``web/``-directory of this installation has to be configured. If another host name is to be used, it has to be changed in the [config/TEST_DOMAIN](config/TEST_DOMAIN) and [tests/acceptance.suite.yml](tests/acceptance.suite.yml).

#### Running

* Start Selenium: ```
java -jar selenium-server-standalone-3.141.59.jar```
* Run all acceptance tests: ```
vendor/bin/codecept run acceptance```
* Run all unit tests: ```
vendor/bin/codecept run unit```
* Run a single acceptence-test: ```
vendor/bin/codecept run acceptance motions/CreateCept```


Reporting security issues
-------------------------

If you found a security problem with Antragsgrün, please report it to: tobias@hoessl.eu. If you want to encrypt the mail, you can use this [PGP-Key](https://www.hoessl.eu/PGP-Key-tobias-hoessl-eu-99C2D2A2.txt).


[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)
