#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush --nocolor -y en devel_generate

echo "Creating content type subjects"
../vendor/bin/drush --nocolor generate-terms subjects 20 --kill

# labs_experiment is actually published as /labs-posts
for type in blog_article labs_experiment person event podcast_episode interview collection cover press_package annual_report job_advert; do
    echo "Creating content type $type"
    ../vendor/bin/drush --nocolor generate-content 5 --types=$type --kill
done
# community is missing
# highlights is missing
