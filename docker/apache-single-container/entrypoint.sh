#!/bin/sh

set -e

# config/ is a mounted volume typically, to have config.json changes survive container rebuilds.
# This means, that updated files in the image will be hidden. Hence, we keep a copy of those files
# in config-defaults in the image build and restore them into config/ on container startup.
# Without such a volume the files are already in place and nothing is written here.
for f in /var/www/html/config-defaults/*; do
  fname=$(basename "$f")
    target="/var/www/html/config/$fname"

    # Just copy it when it actually changed - not on every container start
    if [ ! -f "$target" ] || [ "$(md5sum < "$f")" != "$(md5sum < "$target")" ]; then
      echo "Updating $target"
      cp "$f" "$target"
    fi
done

echo "Initialized config."

# Create the database schema and the first site if AUTO_INIT asks for it; a no-op otherwise.
# See docs/environment-variables.md. Runs as www-data so that anything it leaves behind in
# runtime/ stays writable for the web server.
if [ "$(id -u)" = "0" ]; then
  setpriv --reuid=www-data --regid=www-data --clear-groups php /var/www/html/yii app/auto-init
else
  php /var/www/html/yii app/auto-init
fi

echo "Starting $@"
exec "$@"
