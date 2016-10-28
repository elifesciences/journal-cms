#!/usr/bin/env bash

cd ./web
../vendor/bin/drush -y en devel_generate
../vendor/bin/drush generate-terms subjects 5 --kill
../vendor/bin/drush generate-terms labs_experiment 5
