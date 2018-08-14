#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush --nocolor -y en devel_generate

echo "Creating content type subjects"
../vendor/bin/drush migrate-import jcms_subjects_json

# labs_experiment is actually published as /labs-posts
for type in blog_article labs_experiment person event interview collection annual_report job_advert podcast_episode cover press_package; do
    echo "Creating content type $type"
    ../vendor/bin/drush --nocolor generate-content 5 --types=$type --kill
done
# community is missing
# highlights is missing
