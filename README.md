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
COMPOSER=composer-setup.json composer install
vagrant up
```

Once it is setup, visit `http://journal-cms.local`.

## Running the Tests

First, ssh into the VM.

```
vagrant ssh
cd /var/www/journal-cms
```

Next, you need to install the dev dependencies.

```
composer install
```

Then, you can run the project tests...

```
./project_tests.sh
```

Or you can run the smoke tests.

```
./smoke_tests.sh
```

## Git hooks

To install the Git precommit that prevents committing large files, run:

```
cp .git-hooks-pre-commit .git/hooks/pre-commit
```

## Project reset

If you want to completely replay the set up of this project locally then you can run the following commands:

```
vagrant destroy -f
vagrant box remove geerlingguy/drupal-vm
composer run-script clean-up
```
