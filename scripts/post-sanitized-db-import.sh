#!/usr/bin/env bash
set -e

# Sanitized db with this command: ../vendor/bin/drush sql-dump --gzip --structure-tables-list="user__roles,user__user_picture,users,users_data,users_field_data"

cd $(dirname $0)

cd ../web

UUID="$(uuidgen)"

../vendor/bin/drush sqlq "INSERT INTO users (uid, uuid, langcode) VALUES (0,'$UUID','en')"
../vendor/bin/drush sqlq "INSERT INTO users_field_data (uid, langcode, preferred_langcode, preferred_admin_langcode, name, pass, mail, timezone, status, created, changed, access, login, init, default_langcode) VALUES (0,'en','en',NULL,'',NULL,NULL,'',0,1493210495,1493210495,0,0,NULL,1)"
../vendor/bin/drush user-create admin --mail="admin@example.com" --password="$(date +%s | sha256sum | base64 | head -c 10)";
../vendor/bin/drush sqlq "UPDATE users_field_data SET uid = 1 WHERE uid > 0"
../vendor/bin/drush sqlq "UPDATE users SET uid = 1 WHERE uid > 0"
../vendor/bin/drush sqlq "UPDATE node_field_data SET uid = 1 WHERE uid > 1"
../vendor/bin/drush cr
