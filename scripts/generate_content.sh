#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush --nocolor -y en devel_generate

echo "Creating content type subjects"
../vendor/bin/drush --nocolor generate-terms subjects 5 --kill

# labs_experiment is actually published as /labs-posts
for type in blog_article labs_experiment person event; do
    echo "Creating content type $type"
    ../vendor/bin/drush --nocolor generate-content 5 --types=$type
done
