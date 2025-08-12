# eLife Journal CMS

## DDev setup (Preferred development environment)

First you need to install DDev, installation instruction for most platforms available here: `https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/`

```bash
ddev composer install
ddev start
ddev drush si minimal --existing-config -y
ddev add-on get ddev/ddev-redis
```

Visit http://journal-cms.ddev.site:8080.

If you want to completely replay the set up of this project locally then you can run the following commands:

```bash
ddev stop --remove-data
ddev composer run-script clean-up
```
