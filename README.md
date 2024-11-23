Antragsgrün
===========

Antragsgrün is an easy-to-use online tool for NGOs, political parties, and social initiatives to collaboratively discuss resolutions, party platforms, and amendments. It helps to manage candidacies and supports meetings by providing online votings, speaking lists, and many more features.

A number of organisations are using the tool successfully such as the federal association of the European and German Green Party, the German Federal Youth Council, the European Youth Forum or the National Council of German Women's Organizations.
It can be easily adapted to a variety of scenarios.

Core functions:
- Submit motions, proposals and discussion papers online
- Clear amendming process for users and administrators
- Discuss motions
- Draft resolutions
- Votings
- Speaking lists
- Diverse export options
- Great flexibility - it adapts to a lot of different use cases
- Technically mature, data privacy-friendly
- Accessible, following WCAG AA

Using the hosted version / testing it
-------------------------------------

- German: [https://antragsgruen.de](https://antragsgruen.de/)
- English: [https://motion.tools](https://motion.tools/), [https://discuss.green](https://discuss.green/)
- French, Dutch, Catalan: [https://discuss.green](https://discuss.green/)

Installation
------------------------------------------

### Using the pre-bundled package

#### Requirements:

- A MySQL/MariaDB-database
- PHP >= 8.1. Recommended: 8.3+. Required packages for Debian / Ubuntu Linux:

```bash
# Using PHP8-packages from [deb.sury.org](https://deb.sury.org/):
apt-get install php8.3 php8.3-cli php8.3-fpm php8.3-intl php8.3-gd php8.3-mysql \
                php8.3-opcache php8.3-curl php8.3-xml php8.3-mbstring php8.3-zip php8.3-iconv
```

- Apache or nginx. Example files are provided here:
  - Example configuration for [nginx](docs/nginx.sample.conf)
  - Example configuration for [apache](docs/apache.sample.conf)

#### Installation:

- Download the [latest ZIP/BZIP2-package of Antragsgrün](https://github.com/CatoTH/antragsgruen/releases/latest).
- Extract the contents into your web folder
- Access the "antragsgruen/"-folder of your web server, e.g. if you have extracted the package into the web root of your host named www.example.org/, then access www.example.org/antragsgruen/
- Use the web-based installer to configure the database and further settings

### From the sources

Install the sources and dependencies from the repository:

```bash
git clone https://github.com/CatoTH/antragsgruen.git
cd antragsgruen
curl -sS https://getcomposer.org/installer | php
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

### Using container images – Docker and other container orchestrations

[Jugendpresse Deutschland e.V.](https://www.jugendpresse.de) developed a container image, which is now maintained as an open source / collaborative project at [github.com/devops-ansible/docker-antragsgruen](https://github.com/devops-ansible/docker-antragsgruen).

The repository is maintained to run its workflows once a week to build the `devopsansiblede/antragsgruen` image.  
The latest contents of the `master` branch will result in a `dev_YYYYMMDD-HHII` image-tag with the build date and time mentioned as well as the `development` image-tag.  
The actual (new) releases by git tags in the official [Motiontool repository](https://github.com/CatoTH/antragsgruen) are built into images as well. They result in the tags `latest` being the latest (highest) released version, and semantic version partials mapping the corresponding versions, so `vA.B.C` is the current patch level version, `vA.B` is the latest released patch of the minor version, and `vA` is the latest released patch of the major version.

You can find the container images published on [DockerHub](https://hub.docker.com/repository/docker/devopsansiblede/antragsgruen).

## Updating

### Using the web-based updater

Site administrators of an installation will see an Update-Box on the right side of the administration page of a consultation. The box indicates if an update is available. If so, you can switch the whole installation into Update mode. While the update mode is active, the whole site will not be available to other users.

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

## PDF-Rendering

Generating PDFs is performed by the PHP-Library [TCPDF](https://github.com/tecnickcom/tcpdf) by default.
In some cases, nicer and easier to customize PDFs can be generated though by using a separate command line tool to generate them. They need to be set up and configured by hand on the server though.

### PHP-Based PDF-Rendering

The PHP-processes need writing permissions to the folder.
If this is not possible, you need to specify an alternative writable folder by hand by adding the following line to the beginning of `web/index.php`:
```php
define("K_PATH_FONTS", "/path/to/writable/directory/");
```

### FPDI-PDF

If you run into the error "This PDF document probably uses a compression technique which is not supported by the free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)" and decide to use the commercial plugin, you can install the package using the following steps:
- Extract the content of the package into the directory ``components/fpdi``, so there exists a subdirectory ``src``.
- Run the command ``./composer.phar dump-autoload``

After that, newer PDF files should be able to be parsed as well.

### Weasyprint-based PDF-rendering

Variant 1, for a distribution with a reasonably recent version of Weasyprint (60+):
```bash
apt-get install weasyprint
```

Variant 2, installation using pip (requires Python 3 including VirtualEnv support):
```bash
python3 -m venv venv
source venv/bin/activate
pip install weasyprint
weasyprint --info
```
(Refer to: https://doc.courtbouillon.org/weasyprint/stable/first_steps.html#linux)

Add the following settings to your config.json (and adapt them to your needs):

```json
{
    "weasyprintPath": "/usr/bin/weasyprint"
}
```

### LaTeX/XeTeX-based PDF-rendering (deprecated)

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

When LaTeX complains about `scrlayer2.sty` not found, executing the SQL statement `UPDATE texTemplate SET texLayout = REPLACE(texLayout, 'scrpage2', 'scrlayer-scrpage');` followed by clearing all caches (`./yii cache/flush-all`) should fix this problem.

## Deployment and Performance Optimization

### Setting Super-Admins

Super-Admins are administrators with some additional set of privileges not available to regular site administrators:
- They can modify the user data of registered users (setting the name, organization and a new password).
- They can download and install new versions of Antragsgrün and set the whole site into maintenance mode.
- On Multisite installations, they are automatically set as administrator for every site.

The list of super-admins cannot (on purpose) be changed using the Web-UI,
but has to be manually changed in the `config/config.json` by adding and removing the user IDs in the `adminUserIds` array.

### Securing Accounts

Antragsgrün comes with built-in support for protecting user accounts from brute-force accounts. By default:
- A CAPTCHA needs to be solved after three unsuccessful login attempts for every further login attempt.
- Users can opt in to protect their accounts using a second factor authentication app (TOTP).

#### Configuring the CAPTCHA

The default behavior of the CAPTCHA can be modified in the `config.json`:
- The `mode` indicates when a CAPTCHA is shown. The default  `throttle` requires it after three unsuccessful attempts, balancing security with trying not to bother users too much. `always` always requires entering a CAPTCHA, `never` disables it entirely.
- `difficulty` defaults to `normal`, which should be solvable by most users. To make it easier (no distortion of image), set it to `easy`.
- `ignoredIps` is a list of IP addresses that will never receive a CAPTCHA. This is often necessary on conventions where all delegates are sharing one WiFi IP address and unsuccessful login attempts of one delegate would otherwise trigger CAPTCHA-behavior for all others.

```json
{
    "captcha": {
        "mode": "always", // Options: "never", "throttle", "always"
        "ignoredIps": [
            "127.0.0.1",
            "::1"
        ],
        "difficulty": "easy" // Options: "easy", "normal"
    }
}
```

#### Configuring / Enforcing 2FA

By default, users have the option to secure their account with a TOTP-based second factor (supported by many apps like Authy, Google Authenticator, FreeOTP or password managers). Super-Admins can change this behavior on an *per-user-basis*:
- Setting a second factor can be enforced.
- Setting a second factor can be disabled (changing passwords can be prevented too, e.g. for accounts meant to be shared).
- A second factor can be removed, e.g. if the user lost access to their 2FA-app.

#### Integration Single-Sign-On-Providers

The user administration of Antragsgrün can be connected to a SSO provider, for example using SAML. However, this is not part of the core distribution, as the requirements are typically too organization-specific.

### ImageMagick

To resize uploaded images in applications on the server side, and to enable uploading PDFs as images, ImageMagick needs to be installed as command line tool:
- Install the packages. On Debian-based systems, `apt-get install imagemagick` should do the trick.
- Make sure PDF operations are allowed. On Debian-based systems, check the file `/etc/ImageMagick-6/policy.xml` and comment out `<policy domain="coder" rights="none" pattern="PDF" />` if necessary.
- Set the path to the binary in `imageMagickPath` in `config/config.json`.

### Multisite-Mode

There are two ways to deploy multiple sites using one codebase, each site allowing multiple consultations. However, both of them are non-trivial.

#### Using a completely separate configuration and database

If you want to use two completely different databases, or a different set of active plugins, you can create a separate ``config.json`` for each installation and name them like ``config.db1.json``, ``config.db2.json``, etc. Which one is used on a request depends on the environment variable ``ANTRAGSGRUEN_CONFIG``that is provided to the PHP process. For example, to use ``config.db1.json`` on the hostname ``db1.antragsgruen.local`` on Apache, you can use the following line in the Apache configuration:

``SetEnvIf Host "db1.antragsgruen.local" ANTRAGSGRUEN_CONFIG=/var/www/antragsgruen/config/config.db1.json``

For command line commands, you can set this variable like this:

``ANTRAGSGRUEN_CONFIG=/var/www/antragsgruen/config/config.db1.json ./yii database/migrate``

#### Using the same database, plugin configuration and a site manager

[Antragsgruen.de](https://antragsgruen.de/) uses a site manager module on the home page that allows users to create their own sites using a web form. This is done using the ``multisideMode`` and a plugin for the site manager. Relevant entries in the ``config.json`` for this are:

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


### Increasing performance by caching in Redis

Redis can be used to cache the changes in amendments, user sessions, and many other aspects of the site. To enable redis, simply add a `redis` configuration key to the `config.json` and point it to your setup:

Add the following settings to your config.json (and adapt them to your needs):
```json
{
    "redis": {
        "hostname": "localhost",
        "port": 6379,
        "database": 0,
        "password": "mysecret" // optional
    }
}
```

### File-based View Caching (very large consultations)

Antragsgrün already does a decent amount of caching by default, and even more when enabling Redis. An even more aggressive caching mode that caches some fully rendered HTML pages and PDFs can be enabled by enabling the following option in the `config.json`:

```json
{
    "viewCacheFilePath": "/tmp/some-viewcache-directory/"
}
```

Note that this might in some edge case lead to old information being shown and is only meant as a last resort if hundreds to thousands of users are accessing large motions in parallel.

As a rule of thumb, this setting should be considered if you expect close to 1.000 motions and amendments or more in one consultation.

### JWT Key Signing

Some of the more advanced features of Antragsgrün need JWT signing set up. Right now, this is only the integration of the Live Server, but in the future this will also enable logged in access to the REST API.

First, a Public/Private key pair used for JWT authentication needs to be generated:
```shell
ssh-keygen -t rsa -b 4096 -m PEM -f bundle.pem
openssl rsa -in bundle.pem -pubout -outform PEM -out public.key
openssl pkcs8 -topk8 -inform PEM -outform PEM -in bundle.pem -out private.key -nocrypt
```

Move the keys to a safe place and point the `jwtPrivateKey` parameter in `config.json` to its absolute location, like:
```json
{
    "jwtPrivateKey": "/var/www/antragsgruen/config/jwt.key"
}
```

### Enabling the Live Server

The optional [Live Server](https://github.com/CatoTH/antragsgruen-live) can be installed to enable live updates for speaking lists (and potentially more components in the future).

As a prerequisite, JWT Signing needs to be enabled (see above). Then, the location of the RabbitMQ server, the credentials of the management API and the name of the exchange needs to configured, along with the absolute URI of the Websocket endpoint the Live Server exposes:

```json5
{
    "live": {
        "installationId": "std", // The ID identifying this installation at the Live Server
        "wsUri": "ws://localhost:8080/websocket", // The full URI of the websocket endpoint of the Live Server
        "stompJsUri": "http://localhost:8080/stomp.umd.min.js", // The full URI of a hosted StompJS library
        "rabbitMqUri": "http://localhost:15672", // Base URI to the REST API of RabbitMQ
        "rabbitMqExchangeName": "antragsgruen-exchange", // Created by the Live Server
        "rabbitMqUsername": "guest", // Default username of RabbitMQ
        "rabbitMqPassword": "guest" // Default password of RabbitMQ
    }
}
```

Developing
----------

### Technical considerations

- PHP version support: Antragsgrün supports PHP versions until its [end of life](https://www.php.net/supported-versions.php) (that is, if PHP 8.1 is supported until end 2025, the first major version of Antragsgrün of 2026 will drop support for PHP 8.1).
- PHP Framework: [Yii2](https://www.yiiframework.com/) is used. While it would not be the framework of choice for a fresh start anymore, it works sufficiently well since its introduction in 2015 and is still supported, so there is no plan to migrate to Symfony of Laravel yet.
- JavaScript: Good old [JQuery](https://jquery.com/) is used for simple interactions, though written in TypeScript and loaded via [RequireJS](https://requirejs.org/). For more complex widgets like voting, speaking lists or amendment merging, [Vue.JS](https://vuejs.org/) is used. There is no plan to redesign Antragsgrün into being a Single-Page-App.
- REST API: The API is documented below. There will be more development regarding the REST API, including authorized endpoints using JWT based authentication.

### Compiling from source

You can enable debug mode by creating an empty file config/DEBUG.

To compile the JavaScript- and CSS-Files, you need to install Gulp:
```bash
npm install # Installs all required packages

npm run build # Compiles the regular JS/CSS-files
npm run watch # Listens for changes in JS/CSS-files and compiles them immediately
```

After updating the source code from git, do:
```bash
./composer.phar install
./yii migrate
gulp
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
- For validation, [Total Validator](https://www.totalvalidator.com/), [WAVE](https://wave.webaim.org/) and the Mozilla Firefox accessibility validation is used. **Known limitations** of Total Validator and WAVE here are the inaccurate contrast checking for gradients in headlines and buttons. Firefox checks them correctly. For Firefox, the main limitation is that it inaccurately classifies elements as interactive that have a `$(element).on("click", ".subselecor", handler)`-listener that is actually targeted to dynamic child elements.

Known limitations:

- Reordering objects (like agenda items) does not work yet using the keyboard
- When developer mode is activated, the debug bar produces several accessibility issues

## Plugins

* Each plugin has a directory under [plugins/](plugins/). It requires at least a ``Module.php`` which inherits from [ModuleBase.php](plugins/ModuleBase.php).
* Custom URLs can be defined in the Modules.php, the corresponding controllers are in the ``controller``-subdirectory, the views in ``views``, custom commands need to be in a ``commands``-directory. A rather complex example containing a bit of everything can be seen in [member_petitions](plugins/member_petitions/).
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

The most frequent use case for plugins is custom themes / layouts. You can develop a custom theme using SASS/SCSS for Antragsgrün using the following steps:

- Create a directory for the plugin with a ``Module.php`` and ``Assets.php``. If your directory / plugin ID is ``mylayout``, the namespace of these classes needs to be ``app\plugins\mylayout``.
- The ``Module.php`` needs the static method ``getProvidedLayout`` that returns the asset bundle. See the [gruen_ci](plugins/gruen_ci/Module.php) or [neos](plugins/neos/Module.php) for examples.
- Create a file ```plugins/mylayout/assets/mylayout.scss```. Again, use the existing plugins as an example to get the imports right.
- Adapt the SCSS variables and add custom styles
- Run ```gulp``` to compile the SCSS into CSS
- Activate the plugin as said above.
- Now, you can choose your new theme in the consultation settings

A hint regarding the AGPL license and themes: custom stylesheets and images and changes to the standard stylesheets of
Antragsgrün do not have to be redistributed under an AGPL license like other changes to the Antragsgrün codebase.

### Custom language variants as plugin

Every single message in the user interface can be modified using the web-based translation tool, without having to use plugins. Just log in as admin and go to Settings -> Edit the language.

On larger setups, there might be a need to share language variants between different consultations or installations. In that case, it is possible to define language variants as plugin:

- Create a directory for the plugin with a ``Module.php`` and ``Assets.php``. If your directory / plugin ID is ``mylayout``, the namespace of these classes needs to be ``app\plugins\mylayout``.
- The ``Module.php`` needs the static method ``getProvidedMessagesForLanguage`` that returns the languages that the plugin returns translations / adaptions for.
- The plugin needs the directory `messages/[language]` with the appropriate overrides, e.g. `messages/en/motion.php`.
- Now this language variant is selectable in the "Edit the language"-settings-page.

## REST-API

An optional API is under development for Antragsgrün, extended by functionality as needed by external applications. Currently, starting with version 4.7.0, it gives read-only access to consultations, motions, amendments and the proposed procedure of consultations.

The API is disabled by default and can be enabled under "Settings" -> "Appearance and components of this site" -> "Enable the REST-API".

All endpoints of the API are located under `/rest`. An OpenAPI-based description of the API can be found at [docs/openapi.yaml](docs/openapi.yaml). A [SwaggerUI](https://swagger.io/tools/swagger-ui/)-based viewer of the documentation can be installed by uploading the [swagger_ui](plugins/swagger_ui) plugin to `/plugins/` and adding it to the list of plugins in `config/config.json`.

## Testing

### Codecept (acceptance & unit test)

#### Installation

* Create a separate (MySQL/MariaDB-)database for testing (`antragsgruen_tests`)
* Set up the configuration file: ```
cp config/config_tests.template.json config/config_tests.json && vi config/config_tests.json```
* Download [ChromeDriver](https://sites.google.com/chromium.org/driver/) and move the binary into the PATH (e.g. /usr/local/bin/)
* Download the [Selenium Standalone Server](https://www.selenium.dev/downloads/)
* For the automated HTML validation, Java needs to be installed and the vnu.jar file from the [Nu Html Checker](https://validator.github.io/validator/) located at /usr/local/bin/vnu.jar.
* For the automated accessibility validation, [Pa11y](https://pa11y.org/) needs to be installed. (is done by ``npm install``)
* The host name ``test.antragsgruen.test`` must point to localhost (by adding an entry to /etc/hosts) and a VirtualHost in your Apache/Nginx-Configuration pointing to the ``web/``-directory of this installation has to be configured. If another host name is to be used, it has to be changed in the [config/TEST_DOMAIN](config/TEST_DOMAIN) and [tests/acceptance.suite.yml](tests/acceptance.suite.yml).

#### Running

* Start Selenium:
```java -jar selenium-server-standalone-3.141.59.jar```
* Run all acceptance tests:
```vendor/bin/codecept run Acceptance```
* Run all unit tests:
```vendor/bin/codecept run Unit```
* Run unit tests without a database:
```vendor/bin/codecept run Unit --skip-group=database```
* Run a single acceptance-test: 
```vendor/bin/codecept run Acceptance motions/CreateCept```

### phpstan

1. Before changing code, generate a baseline.

```bash
php -d memory_limit=1G vendor/bin/phpstan.phar analyse --configuration=phpstan.neon --generate-baseline
```

Suggested output: `[OK] Baseline generated with 123 errors.`

2. Verify that your changes do not introduce new problems.

```bash
php -d memory_limit=1G vendor/bin/phpstan.phar analyse --configuration=phpstan.use-baseline.neon
```

Suggested output: `[OK] No errors`

Reporting security issues
-------------------------

If you found a security problem with Antragsgrün, please report it to: tobias@hoessl.eu. If you want to encrypt the mail, you can use this [PGP-Key](https://www.hoessl.eu/PGP-Key-tobias-hoessl-eu-99C2D2A2.txt).


[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
