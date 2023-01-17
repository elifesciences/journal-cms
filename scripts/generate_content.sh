#!/usr/bin/env bash
set -ex

cd ./web
../vendor/bin/drush --no-ansi -y en devel_generate

echo "Creating content type subjects"
../vendor/bin/drush migrate-import jcms_subjects_json

echo "Creating some articles"
../vendor/bin/drush --no-ansi devel-generate-content 20 --bundles=article --kill

echo "Creating some digests"
../vendor/bin/drush --no-ansi devel-generate-content 10 --bundles=digest --kill

for type in research_focuses research_organisms; do
    echo "Creating taxonomy $type"
    ../vendor/bin/drush --no-ansi devel-generate-terms --bundles=$type 10 --kill
done

# labs_experiment is actually published as /labs-posts
for type in blog_article labs_experiment person event interview podcast_chapter podcast_episode collection promotional_collection annual_report job_advert cover press_package; do
    echo "Creating content type $type"
    ../vendor/bin/drush --no-ansi devel-generate-content 5 --bundles=$type --kill
done

echo "Populate covers list"
../vendor/bin/drush jcms-covers-random

echo "Creating content type highlight_item"
    ../vendor/bin/drush --no-ansi devel-generate-content 15 --bundles=highlight_item --kill

echo "Creating content type highlight_list"
    ../vendor/bin/drush --no-ansi devel-generate-content 3 --bundles=highlight_list --kill

../vendor/bin/drush --no-ansi -y pm-uninstall devel_generate
