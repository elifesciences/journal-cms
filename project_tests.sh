#!/bin/bash
set -e

echo "coder_sniffer"
vendor/bin/phpcs --ignore=/src/modules/jcms_ckeditor/ckeditor/ --standard=Drupal ./src

echo "PHPUnit tests"
export SIMPLETEST_DB=sqlite://localhost/sites/default/files/.ht.sqlite

vendor/bin/phpunit --log-junit build/phpunit.xml
