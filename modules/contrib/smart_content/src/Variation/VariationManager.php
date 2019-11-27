<?php

namespace Drupal\smart_content\Variation;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\smart_content\Entity\SmartVariationSetInterface;

/**
 * Provides the Smart variation plugin manager.
 */
class VariationManager extends DefaultPluginManager {

  /**
   * Constructor for SmartVariationManager objects.
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
    parent::__construct('Plugin/smart_content/Variation', $namespaces, $module_handler, 'Drupal\smart_content\Variation\VariationInterface', 'Drupal\smart_content\Annotation\SmartVariation');

    $this->alterInfo('smart_content_smart_variation_info');
    $this->setCacheBackend($cache_backend, 'smart_content_smart_variation_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = [], SmartVariationSetInterface $entity = NULL) {
    $plugin = parent::createInstance($plugin_id, $configuration);
    if (is_null($entity)) {
      throw new \InvalidArgumentException('$entity is required');
    }
    $plugin->entity = $entity;
    return $plugin;
  }

}
