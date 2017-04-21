<?php

/**
 * @file
 * Contains \JCMSDrupalProject\composer\ScriptHandler.
 */

namespace JCMSDrupalProject\composer;

use Composer\Script\Event;
use DrupalProject\composer\ScriptHandler as DrupalScriptHandler;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler extends DrupalScriptHandler {

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $root = getcwd();
    $drupal_root = $root . '/web';
    $config_root = $root . '/config';
    $src_root = $root . '/src';
    parent::createRequiredFiles($event);

    if ($fs->exists($config_root . '/settings.php')) {
      if ($fs->exists($drupal_root . '/sites/default/settings.php')) {
        $fs->chmod($drupal_root . '/sites/default', 0755);
        $fs->remove($drupal_root . '/sites/default/settings.php');
      }
      $fs->copy($config_root . '/settings.php', $drupal_root . '/sites/default/settings.php');
      $fs->chmod($drupal_root . '/sites/default/settings.php', 0666);
      $event->getIO()->write('Create a sites/default/settings.php file from local config with chmod 0666');
    }

    if ($fs->exists($config_root . '/services.yml')) {
      if ($fs->exists($drupal_root . '/sites/default/services.yml')) {
        $fs->chmod($drupal_root . '/sites/default', 0755);
        $fs->remove($drupal_root . '/sites/default/services.yml');
      }
      $fs->copy($config_root . '/services.yml', $drupal_root . '/sites/default/services.yml');
      $fs->chmod($drupal_root . '/sites/default/services.yml', 0666);
      $event->getIO()->write('Create a sites/default/services.yml file from local config with chmod 0666');
    }

    if (!$fs->exists($config_root . '/local.settings.php')) {
      $fs->copy($config_root . '/drupal-vm.settings.php', $config_root . '/local.settings.php');
    }

    // Create symlink to custom modules folder.
    if ($fs->exists($src_root . '/modules') && !$fs->exists($drupal_root . '/modules/custom')) {
      $fs->symlink('../../src/modules', $drupal_root . '/modules/custom');
    }
  }

}
