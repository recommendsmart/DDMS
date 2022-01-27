<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Entity list sortable filter plugin manager.
 */
class EntityListSortableFilterManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityListDisplayManager object.
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
    parent::__construct('Plugin/EntityListSortableFilter', $namespaces, $module_handler, 'Drupal\entity_list\Plugin\EntityListSortableFilterInterface', 'Drupal\entity_list\Annotation\EntityListSortableFilter');

    $this->alterInfo('entity_list_entity_list_sortable_filter_info');
    $this->setCacheBackend($cache_backend, 'entity_list_entity_list_sortable_filter_form');
  }

}
