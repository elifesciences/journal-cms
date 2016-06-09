#!/bin/bash

# If a source database is available, import it.
ELIFE_1_0_IMPORTED=/etc/elife_1_0_imported.cnf

if [[ -e /vagrant/scripts/elife_1_0.sql.gz && ! -e "$ELIFE_1_0_IMPORTED" ]]; then
    echo "[client]" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "user = elife_1_0" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "password = elife_1_0" | sudo tee -a $ELIFE_1_0_IMPORTED
    echo "host = localhost" | sudo tee -a $ELIFE_1_0_IMPORTED

    zcat /vagrant/scripts/elife_1_0.sql.gz | mysql --defaults-extra-file=$ELIFE_1_0_IMPORTED elife_1_0
fi

# Set the default directory when accessing the vm and add vendor bin to PATH.
if [[ ! -e /home/vagrant/.bashrc ]]; then
    echo "cd /var/www/elife-2.0-website" >> /home/vagrant/.bashrc
    echo "export PATH=\"/var/www/elife-2.0-website/vendor/bin:$PATH\"" >> /home/vagrant/.bashrc
fi

exit 0
