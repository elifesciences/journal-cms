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
    }

    if (!$fs->exists($config_root . '/local.settings.php')) {
      $fs->copy($config_root . '/drupal-vm.settings.php', $config_root . '/local.settings.php');
    }

    // Create private folder, with known sub-folders.
    $local_settings = str_replace('"', "'", file_get_contents($config_root . '/local.settings.php'));
    if (preg_match("/'file_private_path'[^']+'(?P<file_private_path>[^']+)/", $local_settings, $match)) {
      $file_private_path = rtrim($match['file_private_path'], '/');

      $file_private_path = preg_replace('~^\./~', '/', $file_private_path);

      if (substr($match['file_private_path'], 0, 1) != '/') {
        $file_private_path = $drupal_root . $file_private_path;
      }

      if (!$fs->exists($file_private_path)) {
        $fs->mkdir($file_private_path, 0755);
      }

      $private_subfolders = ['monolog'];
      foreach ($private_subfolders as $folder) {
        if (!$fs->exists($file_private_path . '/' . $folder)) {
          $fs->mkdir($file_private_path . '/' . $folder, 0755);
        }
      }
    }

    if (!$fs->exists($config_root . '/local.services.yml')) {
      $fs->copy($config_root . '/drupal-vm.services.yml', $config_root . '/local.services.yml');
    }

    // Create symlink to custom modules folder.
    if ($fs->exists($src_root . '/modules') && !$fs->exists($drupal_root . '/modules/custom')) {
      $fs->symlink('../../src/modules', $drupal_root . '/modules/custom');
    }

    // Create symlink to custom modules folder.
    if ($fs->exists($root . '/private')) {
      $fs->symlink('../../src/modules', $drupal_root . '/modules/custom');
    }
  }

}
