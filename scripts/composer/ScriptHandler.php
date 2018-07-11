<?php

/**
 * @file
 * Contains \JCMSDrupalProject\composer\ScriptHandler.
 */

namespace JCMSDrupalProject\composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $root = getcwd();
    $drupal_root = $root . '/web';
    $config_root = $root . '/config';
    $src_root = $root . '/src';

    foreach (['libraries', 'modules', 'profiles', 'themes'] as $dir) {
      if (!$fs->exists($drupal_root . '/'. $dir)) {
        $fs->mkdir($drupal_root . '/'. $dir);
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupal_root . '/sites/default/settings.php') and $fs->exists($drupal_root . '/sites/default/default.settings.php')) {
      $fs->copy($drupal_root . '/sites/default/default.settings.php', $drupal_root . '/sites/default/settings.php');
      require_once $drupal_root . '/core/includes/bootstrap.inc';
      require_once $drupal_root . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($config_root . '/sync', $drupal_root),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupal_root . '/sites/default/settings.php');
      $fs->chmod($drupal_root . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupal_root . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupal_root . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }

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

    // Create symlink to custom modules and themes folder.
    foreach (['modules', 'themes'] as $folder) {
      if ($fs->exists($src_root . '/'.$folder) && !$fs->exists($drupal_root . '/'.$folder.'/custom')) {
        $fs->symlink('../../src/'.$folder, $drupal_root . '/'.$folder.'/custom');
      }
    }

    // Create symlink to custom modules folder.
    if ($fs->exists($root . '/private')) {
      $fs->symlink('../../src/modules', $drupal_root . '/modules/custom');
    }

    // Download ckeditor jquery adaptor.
    if (!$fs->exists($drupal_root . '/libraries/ckeditor/adaptors/adaptors/jquery.js')) {
      if (!$fs->exists($drupal_root . '/libraries/ckeditor/adaptors')) {
        $fs->mkdir($drupal_root . '/libraries/ckeditor/adaptors');
      }
      file_put_contents($drupal_root . '/libraries/ckeditor/adaptors/jquery.js', file_get_contents('https://raw.githubusercontent.com/ckeditor/ckeditor-releases/standard/4.9.2/adapters/jquery.js'));
    }
  }

}
