#!/bin/bash

ELIFE_1_0_IMPORTED=/etc/elife_1_0_imported.cnf

if [[ -e /vagrant/scripts/elife_1_0.sql.gz && ! -e "$ELIFE_1_0_IMPORTED" ]]; then
    echo "[client]" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "user = elife_1_0" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "password = elife_1_0" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "host = localhost" | sudo tee -a $ELIFE_1_0_IMPORTED

    zcat /vagrant/scripts/elife_1_0.sql.gz | mysql --defaults-extra-file=$ELIFE_1_0_IMPORTED elife_1_0
else
    exit 0
fi