# eLife Journal CMS

[![Build Status](http://ci--alfred.elifesciences.org/buildStatus/icon?job=test-journal-cms)](http://ci--alfred.elifesciences.org/job/test-journal-cms/)

## Preparation

Ensure that you have the following installed:

- VirtualBox
- Vagrant
- Hostupdater (`vagrant plugin install vagrant-hostsupdater`)
- Composer

## Instructions

```
composer install
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
vagrant box remove geerlingguy/ubuntu2004
composer run-script clean-up
```

## Goaws

The setup of goaws has been temporarily disabled. Most development is unimpacted by this but we have created a ticket to restore this functionality.
