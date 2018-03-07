#!/bin/bash
set -e

echo "coder_sniffer"
phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
phpcs --standard=Drupal ./src

echo "PHPUnit tests"
export SIMPLETEST_DB=sqlite://localhost/sites/default/files/.ht.sqlite

vendor/bin/phpunit --log-junit build/phpunit.xml
