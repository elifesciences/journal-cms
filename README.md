## Instructions

```
$ composer install
```

Following instructions to [setup drupal-vm](https://github.com/geerlingguy/drupal-vm/blob/3.0.0/README.md)

Below is the summary:

- Install vagrant
- Install ansible
- Install ansible dependencies `sudo ansible-galaxy install -r ./vendor/geerlingguy/drupal-vm/provisioning/requirements.yml --force`
- Install hostsupdater `vagrant plugin install vagrant-hostsupdater`
- Run `vagrant up`


Once it is setup, visit `http://elife-2.0-website.dev`.
