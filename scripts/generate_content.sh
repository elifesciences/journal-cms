#!/usr/bin/env bash
set -e

cd ./web
../vendor/bin/drush -y en devel_generate
../vendor/bin/drush generate-terms 5 subjects --kill
../vendor/bin/drush generate-content 5 --types='labs_experiment'
../vendor/bin/drush generate-content 5 --types='event'
