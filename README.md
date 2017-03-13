[![Coverage Status](https://coveralls.io/repos/github/elifesciences/journal-cms/badge.svg?branch=develop)](https://coveralls.io/github/elifesciences/journal-cms?branch=develop)

## Preparation

Ensure the you have the following installed:

- VirtualBox
- Vagrant
- Ansible
- Hostupdater (`vagrant plugin install vagrant-hostsupdater`)
- Composer

## Instructions

If you have been given the legacy database and files then move them to the following location:

- scripts/legacy_cms.sql.gz
- scripts/legacy_cms_files

There are alternative files which can be used for some of the migrated content. They are available at:

- https://s3.eu-west-2.amazonaws.com/prod-elife-legacy-cms-images/

But if you want to reduce the time to migrate these files you can download them to:

- scripts/legacy_cms_files_alt (the following folders should exist: annual_reports, collections, covers, episodes, labs and subjects)

```
$ COMPOSER=composer-setup.json composer install
$ vagrant up
``` 


Once it is setup, visit `http://journal-cms.local`.

To trigger the migration of all legacy content:

```
$ drush @journal-cms.local mi --all --execute-dependencies
```
