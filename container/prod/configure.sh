#!/bin/bash
# run as the www-data user. no access to su or sudo
set -e

# this should be redundent, but just in case
cd /srv/journal-cms/web

# determine if the site is installed. 
# if it looks like it's installed we can skips some steps
rm -f site-was-installed.flag
site_installed=false
if ../vendor/bin/drush cget system.site name; then
    touch site-was-installed.flag
    site_installed=true
fi

if ! $site_installed; then
    ../vendor/bin/drush site-install config_installer -y
    redis-cli -h redis flushall
fi

../vendor/bin/drush updatedb -y
../vendor/bin/drush config-import -y
../vendor/bin/drush cache-rebuild

if [[ $(../vendor/bin/drush php-eval "print node_access_needs_rebuild()") == "1" ]]; then
    ../vendor/bin/drush php-eval "node_access_rebuild();"
fi

rm -f /tmp/drush-migrate.log
../vendor/bin/drush mi jcms_subjects_json 2>&1 | tee --append /tmp/drush-migrate.log
cat /tmp/drush-migrate.log | ../check-drush-migrate-output.sh

function create_user {
    user_name=$1
    user_email=$2
    user_pass=$3
    if ! ../vendor/bin/drush user-information "$user_name"; then
        ../vendor/bin/drush user-create "$user_name" --mail="$user_email" --password="$user_pass"
    else
        echo "user '$user_name' exists"
    fi
}

create_user "admin" "admin@example.org" "example-password-do-not-use"

../smoke_tests.sh app 80 redis 6379

