FROM php:8.4-fpm

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

RUN docker-php-ext-install exif gd intl mysqli pdo_mysql opcache curl xml mbstring zip iconv sysvsem

RUN apt-get install -y weasyprint imagemagick poppler-utils

RUN apt-get install -y vim zsh # Developer experience

RUN mkdir /tmp/viewcache && chown www-data:www-data /tmp/viewcache

WORKDIR /var/www/antragsgruen

