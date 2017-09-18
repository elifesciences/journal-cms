#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush --nocolor -y en devel_generate
../vendor/bin/drush --nocolor generate-terms subjects 5 --kill
# this is actually published as /labs-posts
../vendor/bin/drush --nocolor generate-content 5 --types='labs_experiment'
../vendor/bin/drush --nocolor generate-content 5 --types='event'
