#!/bin/bash
set -e

echo "coder_sniffer"
vendor/bin/phpcs --ignore=/src/modules/jcms_ckeditor/ckeditor5_plugins/,/src/modules/jcms_ckeditor/build/,/src/modules/jcms_ckeditor/js,/src/modules/jcms_ckeditor/node_modules,/src/modules/jcms_ckeditor/webpack.config.js --standard=Drupal ./src

echo "PHPUnit tests"
export SIMPLETEST_DB=sqlite://localhost/sites/default/files/.ht.sqlite

vendor/bin/phpunit --log-junit build/phpunit.xml
