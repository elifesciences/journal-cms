[![Coverage Status](https://coveralls.io/repos/github/elifesciences/journal-cms/badge.svg?branch=develop)](https://coveralls.io/github/elifesciences/journal-cms?branch=develop)

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
