[![Build Status](https://travis-ci.org/elifesciences/journal-cms.svg?branch=develop)](https://travis-ci.org/elifesciences/journal-cms)

## Preparation

Ensure the you have the following installed:

- VirtualBox
- Vagrant
- Ansible
- Hostupdater (`vagrant plugin install vagrant-hostsupdater`)
- Composer

## Instructions

```
$ COMPOSER=composer-setup.json composer install
$ vagrant up
``` 

Once it is setup, visit `http://journal-cms.local`.
