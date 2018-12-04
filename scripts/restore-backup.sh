#!/bin/bash
set -e
# Usage: TMP=FOLDER ROOT=FOLDER scripts/restore-backup.sh
# Example: TMP=/ext/tmp ROOT=/srv/journal-cms scripts/restore-backup.sh

# arguments and environment variables to influence behavior
TMP="${TMP:-/ext/tmp}"
ROOT="${ROOT:-/srv/journal-cms}"

# cannot use double quotes to allow bash to resolve * wildcards
# shellcheck disable=SC2086
filesArchive=$(find $TMP/*-archive-*.tar.gz)
# shellcheck disable=SC2086
databaseArchive=$(find $TMP/*-elife_2_0-mysql.gz)

echo "Extracting $filesArchive"
tar -xzf "$filesArchive" -C $TMP

echo "Restoring $filesArchive"
rm -rf $ROOT/web/sites/default/files
mv $TMP/srv/journal-cms/web/sites/default/files $ROOT/web/sites/default/
sudo chown -R www-data:www-data $ROOT/web/sites/default/files
rm -rf $TMP/srv
rm -f "$filesArchive"

echo "Restoring $databaseArchive"
cd $ROOT/web
../vendor/bin/drush sql-drop -y
zcat "$databaseArchive" | ../vendor/bin/drush sql-cli
rm "$databaseArchive"
