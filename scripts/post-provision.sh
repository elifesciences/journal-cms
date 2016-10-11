#!/bin/bash

# If a source database is available, import it.
LEGACY_CMS_IMPORTED=/etc/legacy_cms_imported.cnf

if [[ -e /vagrant/scripts/legacy_cms.sql.gz && ! -e "$LEGACY_CMS_IMPORTED" ]]; then
    echo "[client]" | sudo tee -a $LEGACY_CMS_IMPORTED
    echo "user = legacy_cms" | sudo tee -a $LEGACY_CMS_IMPORTED
    echo "password = legacy_cms" | sudo tee -a $LEGACY_CMS_IMPORTED
    echo "host = localhost" | sudo tee -a $LEGACY_CMS_IMPORTED

    zcat /vagrant/scripts/legacy_cms.sql.gz | mysql --defaults-extra-file=$LEGACY_CMS_IMPORTED legacy_cms
fi

# Set the default directory when accessing the vm and add vendor bin to PATH.
if [[ ! -e /home/vagrant/.bash_profile ]]; then
    echo "cd /var/www/journal-cms" >> /home/vagrant/.bash_profile
    echo "export PATH=\"/var/www/journal-cms/vendor/bin:\$PATH\"" >> /home/vagrant/.bash_profile
    echo "if [[ ! -e ~/.console ]]; then" >> /home/vagrant/.bash_profile
    echo "    drupal init" >> /home/vagrant/.bash_profile
    echo "fi" >> /home/vagrant/.bash_profile
    chown vagrant:vagrant /home/vagrant/.bash_profile
fi

if [[ ! `which puli` ]]; then
  wget https://github.com/puli/cli/releases/download/1.0.0-beta10/puli.phar
  chmod +x puli.phar
  mv puli.phar ./vendor/bin/puli
  ln -s ./vendor/bin/puli puli
fi

exit 0
