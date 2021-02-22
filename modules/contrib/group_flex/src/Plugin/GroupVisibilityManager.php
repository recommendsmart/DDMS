<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group_flex\Annotation\GroupVisibility;

/**
 * Provides the Group visibility plugin manager.
 */
class GroupVisibilityManager extends DefaultPluginManager {

  /**
   * Constructs a new GroupVisibilityManager object.
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
    parent::__construct('Plugin/GroupVisibility', $namespaces, $module_handler, GroupVisibilityInterface::class, GroupVisibility::class);

    $this->alterInfo('group_flex_group_visibility_info');
    $this->setCacheBackend($cache_backend, 'group_flex_group_visibility_plugins');
  }

  /**
   * Get all plugins.
   *
   * @return \Drupal\group_flex\Plugin\GroupFlexPluginCollection
   *   The plugin collection.
   */
  public function getAll(): GroupFlexPluginCollection {
    if (!isset($this->allPlugins)) {
      $collection = new GroupFlexPluginCollection($this, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($this->getDefinitions() as $plugin_id => $unUsedPluginInfo) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      $this->allPlugins = $collection->sort();
    }

    return $this->allPlugins;
  }

  /**
   * Get all plugins as array.
   *
   * @return array
   *   An array of plugin implementation.
   */
  public function getAllAsArray(): array {
    $plugins = [];
    foreach ($this->getAll() as $id => $pluginInstance) {
      $plugins[$id] = $pluginInstance;
    }
    return $plugins;
  }

  /**
   * Get all plugins as array to be used on a Group form.
   *
   * @return array
   *   An array of plugin implementation.
   */
  public function getAllAsArrayForGroup(): array {
    $plugins = [];
    foreach ($this->getAll() as $id => $pluginInstance) {
      $plugins[$id] = $pluginInstance;
    }
    // We only need the flex visibility type on the groupType form.
    if (isset($plugins[GROUP_FLEX_TYPE_VIS_FLEX])) {
      unset($plugins[GROUP_FLEX_TYPE_VIS_FLEX]);
    }
    return $plugins;
  }

}
