#!/bin/bash

# Set the default directory when accessing the vm and add vendor bin to PATH.
if [[ ! -e /home/vagrant/.bash_profile ]]; then
    echo "cd /var/www/journal-cms" >> /home/vagrant/.bash_profile
    echo "export PATH=\"/var/www/journal-cms/vendor/bin:\$PATH\"" >> /home/vagrant/.bash_profile
    echo "if [[ ! -e ~/.console ]]; then" >> /home/vagrant/.bash_profile
    echo "    drupal init" >> /home/vagrant/.bash_profile
    echo "fi" >> /home/vagrant/.bash_profile
    chown vagrant:vagrant /home/vagrant/.bash_profile
fi

exit 0
