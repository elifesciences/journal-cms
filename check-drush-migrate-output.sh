#!/bin/bash
set -e

exit "$(grep -c SQLSTATE -)"
