<?php

/**
 * @file
 * Contains \eLifeDrupalProject\composer\ScriptHandler.
 */

namespace eLifeDrupalProject\composer;

use Composer\Script\Event;
use DrupalProject\composer\ScriptHandler as DrupalScriptHandler;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler extends DrupalScriptHandler {

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $root = getcwd();
    $drupal_root = static::getDrupalRoot($root);
    $config_root = $root . '/config';
    $src_root = $root . '/src';
    parent::createRequiredFiles($event);
    if ($fs->exists($config_root . '/settings.php')) {
      if ($fs->exists($drupal_root . '/sites/default/settings.php')) {
        $fs->chmod($drupal_root . '/sites/default', 0755);
        $fs->remove($drupal_root . '/sites/default/settings.php');
      }
      $fs->copy($config_root . '/settings.php', $drupal_root . '/sites/default/settings.php');
      $fs->chmod($drupal_root . '/sites/default/settings.php', 0755);
      $event->getIO()->write('Create a sites/default/settings.php file from local config with chmod 0666');
    }

    if (!$fs->exists($config_root . '/local.settings.php')) {
      $fs->copy($config_root . '/drupal-vm.settings.php', $config_root . '/local.settings.php');
    }

    // Create symlink to custom modules folder.
    if ($fs->exists($src_root . '/modules') && !$fs->exists($drupal_root . '/modules/custom')) {
      $fs->symlink('../../src/modules', $drupal_root . '/modules/custom');
    }

    if (!$fs->exists($config_root . '/config.yml')) {
      $fs->copy($config_root . '/example.config.yml', $config_root . '/config.yml');

      $config_yaml = file_get_contents($config_root . '/config.yml');
      $config_yaml = preg_replace('/%DRUPAL_PWD%/', $root, $config_yaml);
      file_put_contents($config_root . '/config.yml', $config_yaml);
    }
  }

}
