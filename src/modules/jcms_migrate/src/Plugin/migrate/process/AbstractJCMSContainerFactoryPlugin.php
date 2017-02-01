<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for process plugins that need to source destination IDs dynamically.
 */
abstract class AbstractJCMSContainerFactoryPlugin extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManager $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * Convert source ids to migration destination ids.
   *
   * @param string $migration_id
   * @param int|string|array $values
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   * @param \Drupal\migrate\Row $row
   * @param string $destination_property
   * @return array|null
   */
  protected function migrationDestionationIDs($migration_id, $values, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($values)) {
      if (!is_array($values)) {
        $multiple = FALSE;
        $values = [$values];
      }
      else {
        $multiple = TRUE;
      }

      $migration = new Migration(['migration' => $migration_id], $this->pluginId, $this->pluginDefinition, $this->migration, $this->migrationPluginManager, $this->processPluginManager);
      $dest_values = [];
      foreach ($values as $value) {
        if ($dest_value = $migration->transform([$value], $migrate_executable, $row, $destination_property)) {
          if (!is_array($dest_value)) {
            $dest_value = [$dest_value];
          }

          if (!$multiple) {
            return $dest_value[0];
          }
          else {
            $dest_values[] = $dest_value[0];
          }
        }
      }
      if (!empty($dest_values)) {
        return $dest_values;
      }
    }

    return NULL;
  }

}
