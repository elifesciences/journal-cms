#!/bin/bash
set -e

export SIMPLETEST_DB=sqlite://localhost/sites/default/files/.ht.sqlite

# no tests executed yet, but ready for the future
vendor/bin/phpunit --log-junit build/phpunit.xml
