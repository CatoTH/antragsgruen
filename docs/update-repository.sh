#!/usr/bin/env bash

# Relevant Environment variables:
# - ANTRAGSGRUEN_CONFIG : sets the location of the configuration json. If empty, falls back to config/config.json
# - ANTRAGSGRUEN_INSTALL_SIMPLESAML : if set to "true", SimpleSAML is installed

# Go to parent directory of this script and make sure there are no uncommitted changes

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
PARENT_DIR=$(dirname "$SCRIPT_DIR")

cd $PARENT_DIR

if ! git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
    echo "This is not a git repository. Aborting."
    exit 1
fi

if ! git diff-index --quiet HEAD --; then
    echo "Uncommitted changes detected. Please commit or stash your changes before proceeding."
    exit 1
fi

# Update Antragsgrün Code (only if code has changed)

echo "Pulling the latest changes from the remote repository..."
GIT_OUTPUT=$(git pull 2>&1)

# Check if there were incoming changes
if echo "$GIT_OUTPUT" | grep -q "Already up to date"; then
    echo "No incoming changes. Skipping composer/npm install."
else
  composer install
  npx ci
  npx gulp
fi

# Update Antragsgrün Migrations

echo "Performing database migrations..."

./yii migrate

# If relevant: install SimpleSAML

if [ "$ANTRAGSGRUEN_INSTALL_SIMPLESAML" == "true" ]; then
  composer require simplesamlphp/simplesamlphp:^2
  git checkout composer.json composer.lock

  if [ ! -L "vendor/simplesamlphp/simplesamlphp/public/simplesaml" ]; then
    cd vendor/simplesamlphp/simplesamlphp/public
    ln -s . simplesaml
    cd ../../../../
  fi
fi
