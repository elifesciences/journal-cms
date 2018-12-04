#!/bin/bash
set -e

TMP="${TMP:-/ext/tmp}"
# cannot use double quotes to allow bash to resolve * wildcards
# shellcheck disable=SC2086
filesArchive=$(find $TMP/*-archive-*.tar.gz)
# shellcheck disable=SC2086
databaseArchive=$(find $TMP/*-elife_2_0-mysql.gz)

echo "Extracting $filesArchive"
tar -xzf "$filesArchive" -C /ext/tmp

echo "Restoring $filesArchive"
rm -rf /srv/journal-cms/web/sites/default/files
mv /ext/tmp/srv/journal-cms/web/sites/default/files web/sites/default/
sudo chown -R www-data:www-data /srv/journal-cms/web/sites/default/files
rm -rf /ext/tmp/srv
rm -f "$filesArchive"

echo "Restoring $databaseArchive"
cd web
../vendor/bin/drush sql-drop -y
zcat "$databaseArchive" | ../vendor/bin/drush sql-cli
rm "$databaseArchive"
