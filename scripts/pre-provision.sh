#!/bin/bash
update-alternatives --set php /usr/bin/php7.4
composer self-update --1
if [[ -e /vagrant/web/sites/default  ]]; then
    chmod 755 /vagrant/web/sites/default /vagrant/web/sites/default/settings.php
fi

exit 0
