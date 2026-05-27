# docker buildx create --use desktop-linux
# Base
# docker buildx build --platform linux/amd64,linux/arm64 --build-arg APP_ARCHIVE=releases/antragsgruen-4.17.0-b9.tar.bz2 --target base -t tobiashoessl/antragsgruen:4.17.0-b9 -t tobiashoessl/antragsgruen:latest -f Dockerfile --push .
# Full
# docker buildx build --platform linux/amd64,linux/arm64 --build-arg APP_ARCHIVE=releases/antragsgruen-4.17.0-b9.tar.bz2 --target full -t tobiashoessl/antragsgruen:4.17.0-b9-full -t tobiashoessl/antragsgruen:latest-full -f Dockerfile --push .

FROM php:8.5-apache AS minimal

ARG APP_ARCHIVE=antragsgruen.tar.bz2

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install exif gd intl mysqli pdo_mysql curl xml mbstring zip iconv sysvsem \
    && apt-get purge -y --auto-remove \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libonig-dev \
        libzip-dev \
   && apt-get install -y --no-install-recommends libicu76 libzip5 \
   && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite remoteip

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY enable-installer.sh /root/enable-installer.sh
COPY php.ini /usr/local/etc/php/conf.d/zz-antragsgruen.ini

# Copy and extract the application archive (single top-level directory is stripped)
COPY ${APP_ARCHIVE} /tmp/app.tar.bz2
RUN mkdir -p /var/www/html \
    && tar -xjf /tmp/app.tar.bz2 --strip-components=1 -C /var/www/html \
    && rm /tmp/app.tar.bz2 \
    && rm /var/www/html/config/INSTALLING

# Allow Static View Caches
RUN mkdir /tmp/viewcache && chown www-data:www-data /tmp/viewcache

# Set correct ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod 775 /var/www/html/yii \
    # Yii2 runtime and assets directories need to be writable
    && chmod -R 775 /var/www/html/runtime 2>/dev/null || true \
    && chmod -R 775 /var/www/html/web/assets 2>/dev/null || true \
    && chown -R www-data /var/cache/fontconfig

EXPOSE 80

CMD ["apache2-foreground"]


FROM minimal AS full

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        weasyprint \
        imagemagick \
        poppler-utils \
        qpdf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
