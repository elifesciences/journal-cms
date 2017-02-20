#!/bin/bash

if [[ ! -e /etc/init.d/goaws ]]; then
    cd ~
    # Install Go if it isn't installed.
    command version go >/dev/null 2>&1 || { sudo apt install golang-go -y >&2; }
    export GOPATH=$HOME/go
    export PATH=$PATH:$GOPATH/bin
    # Get GoAWS, move to bin.
    go get github.com/p4tin/goaws
    mv $GOPATH/bin/goaws /usr/bin/goaws
    # Make GoAWS a startup task.
    cp /var/www/journal-cms/config/aws/goaws /etc/init.d/goaws
    sudo chmod +x /etc/init.d/goaws
    sudo update-rc.d goaws defaults
    # Download, install and configure AWS CLI.
    curl "https://s3.amazonaws.com/aws-cli/awscli-bundle.zip" -o "awscli-bundle.zip"
    unzip -u awscli-bundle.zip
    sudo ./awscli-bundle/install -i /usr/local/aws -b /usr/local/bin/aws
    sudo ln -sfn /var/www/journal-cms/config/aws /var/www/.aws
    sudo ln -sfn /var/www/journal-cms/config/aws /home/vagrant/.aws
    sudo ln -sfn /var/www/journal-cms/config/aws /root/.aws
    # Start the service.
    sudo service goaws start
fi
