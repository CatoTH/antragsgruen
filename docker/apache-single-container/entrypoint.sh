#!/bin/sh

# config/ is a mounted volume typically, to have config.json changes survive container rebuilds.
# This means, that updated files in the image will be hidden. Hence, we move those files to config-defaults in the image build
# and copy them into config/ on container startup.
for f in /var/www/html/config-defaults/*; do
  fname=$(basename "$f")
    target="/var/www/html/config/$fname"

    # Just copy it when it actually changed - not on every container start
    if [ ! -f "$target" ] || [ "$(md5sum < "$f")" != "$(md5sum < "$target")" ]; then
      echo "Updating $target"
      cp "$f" "$target"
    fi
done


echo "Initialized config. Starting $@"
exec "$@"
