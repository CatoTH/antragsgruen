#!/usr/bin/env bash

# Releasing:
# - Increase version number in config/defines.php
# - Write Changelog
# - Update SECURITY.md
# - Commit this changes to repository
# - Execute this script (docs/create-dist.sh)
# - rsync --progress -a -v local/cdn/ [CDN-LOCATION]
# - docker buildx create --use desktop-linux
# - docker buildx build --platform linux/amd64,linux/arm64 --build-arg APP_ARCHIVE=releases/antragsgruen-4.17.0-rc1.tar.bz2 --target full -t tobiashoessl/antragsgruen:4.17.0-rc1-full -t tobiashoessl/antragsgruen:latest-full -t tobiashoessl/antragsgruen:latest -f Dockerfile --push .
# - docker buildx build --platform linux/amd64,linux/arm64 --build-arg APP_ARCHIVE=releases/antragsgruen-4.17.0-rc1.tar.bz2 --target minimal -t tobiashoessl/antragsgruen:4.17.0-rc1 -t tobiashoessl/antragsgruen:latest-minimal -f Dockerfile --push .
# - Create the new release on Github, attaching the .tar.bz2- and the .zip-file

if [[ ! -d ./controllers ]]; then
    echo "Please run this script from the project's root directory"
    exit
fi

export ANTRAGSGRUEN_VERSION=$(cat config/defines.php | grep "ANTRAGSGRUEN_VERSION" | cut -d \' -f 4)

if [[ -d ./local/antragsgruen-$ANTRAGSGRUEN_VERSION ]]; then
    rm -R ./local/antragsgruen-$ANTRAGSGRUEN_VERSION
fi

mkdir ./local
mkdir ./local/antragsgruen-$ANTRAGSGRUEN_VERSION
if [[ ! -d ./local/antragsgruen-$ANTRAGSGRUEN_VERSION ]]; then
    echo "Could not create the temporary directory"
    exit
fi

pnpm ci
pnpm run build

rsync -av \
  --exclude='local' --exclude='/dist' --exclude='/updates' \
  --exclude='/plugins' --exclude='node_modules' --exclude='runtime' \
  --exclude='web_src' --exclude='/docker' --exclude='vendor' --exclude='.git' \
  . ./local/antragsgruen-$ANTRAGSGRUEN_VERSION

cd local/antragsgruen-$ANTRAGSGRUEN_VERSION

curl -sS https://getcomposer.org/installer | php
./composer.phar install --no-dev

./docs/create-dist-clean-repository.sh

mkdir plugins runtime
chmod 775 runtime web/assets

cp ../../plugins/*php plugins/
mv web/index-production.php web/index.php
touch config/INSTALLING
cp config/.htaccess runtime/
cp config/.htaccess plugins/
cp config/.htaccess vendor/
sed -i -e 's/repository\-source/dist/g' config/defines.php
rm -f config/defines.php-e

docs/create-static-resources.php v$ANTRAGSGRUEN_VERSION

cd ..
mv antragsgruen-$ANTRAGSGRUEN_VERSION/local/cdn/v$ANTRAGSGRUEN_VERSION ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION-cdn
rmdir antragsgruen-$ANTRAGSGRUEN_VERSION/local/cdn
rmdir antragsgruen-$ANTRAGSGRUEN_VERSION/local
tar cfj ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.tar.bz2 antragsgruen-$ANTRAGSGRUEN_VERSION
zip -r ../dist/antragsgruen-$ANTRAGSGRUEN_VERSION.zip antragsgruen-$ANTRAGSGRUEN_VERSION
mv antragsgruen-$ANTRAGSGRUEN_VERSION ../dist/
