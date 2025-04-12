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
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure mysqli \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-configure opcache \
    && docker-php-ext-configure curl \
    && docker-php-ext-configure xml \
    && docker-php-ext-configure mbstring \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure iconv

RUN docker-php-ext-install exif gd intl mysqli pdo_mysql opcache curl xml mbstring zip iconv

RUN apt-get install -y weasyprint imagemagick poppler-utils

#RUN apt-get install -y vim

RUN mkdir /tmp/viewcache && chown www-data:www-data /tmp/viewcache

WORKDIR /var/www/antragsgruen

