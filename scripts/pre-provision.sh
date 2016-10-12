#!/bin/bash

if [[ -e /vagrant/web/sites/default  ]]; then
    chmod 755 /vagrant/web/sites/default /vagrant/web/sites/default/settings.php
fi

exit 0
