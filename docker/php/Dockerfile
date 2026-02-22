## Image with libraries necessary for actual deployment

FROM php:8.5-fpm as production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libonig-dev \
        libzip-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install exif gd intl mysqli pdo_mysql curl xml mbstring zip iconv sysvsem

RUN apt-get install -y weasyprint imagemagick poppler-utils

RUN mkdir /tmp/viewcache && chown www-data:www-data /tmp/viewcache

WORKDIR /var/www/antragsgruen

##  Image with additional packages for development

FROM production AS development

RUN apt-get install -y --no-install-recommends \
    mariadb-client \
    vim \
    zsh

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer
