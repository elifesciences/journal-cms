#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush --nocolor -y en devel_generate

echo "Creating content type subjects"
../vendor/bin/drush migrate-import jcms_subjects_json

echo "Import some articles"
../vendor/bin/drush article-import-all --limit=20

echo "Import some digests"
../vendor/bin/drush digest-import-all --limit=10

for type in research_focuses research_organisms; do
    echo "Creating taxonomy $type"
    ../vendor/bin/drush --nocolor generate-terms --bundles=$type 10 --kill
done

# labs_experiment is actually published as /labs-posts
for type in blog_article labs_experiment person event interview podcast_chapter podcast_episode collection promotional_collection annual_report job_advert cover press_package; do
    echo "Creating content type $type"
    ../vendor/bin/drush --nocolor generate-content 5 --bundles=$type --kill
done

echo "Populate covers list"
../vendor/bin/drush jcms-covers-random

echo "Creating content type highlight_item"
    ../vendor/bin/drush --nocolor generate-content 15 --bundles=highlight_item --kill

echo "Creating content type highlight_list"
    ../vendor/bin/drush --nocolor generate-content 3 --bundles=highlight_list --kill

../vendor/bin/drush --nocolor -y pm-uninstall devel_generate
