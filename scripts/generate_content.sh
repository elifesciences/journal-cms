#!/usr/bin/env bash

cd ./web
../vendor/bin/drush -y en devel_generate
../vendor/bin/drush generate-terms subjects 4 --kill
