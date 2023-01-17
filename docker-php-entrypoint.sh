#!/bin/bash
# run as root
set -m # 'User Portability Utilities', allows us to use fg and bg

# drops to www-data
service nginx start

# drops to www-data
php-fpm &

# configure anything that needs a database and mounted volumes
./configure.sh

# bring php-fpm back
fg

echo "done"
