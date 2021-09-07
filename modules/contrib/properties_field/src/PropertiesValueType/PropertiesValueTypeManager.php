<?php

namespace Drupal\properties_field\PropertiesValueType;

use Drupal\properties_field\PropertiesValueType\Annotation\PropertiesValueType;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for the properties value types.
 */
class PropertiesValueTypeManager extends DefaultPluginManager {

  /**
   * Class constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/PropertiesValueType',
      $namespaces,
      $module_handler,
      PropertiesValueTypeInterface::class,
      PropertiesValueType::class
    );

    $this->alterInfo('properties_value_type');
    $this->setCacheBackend($cache_backend, 'properties_value_type');
  }

}
