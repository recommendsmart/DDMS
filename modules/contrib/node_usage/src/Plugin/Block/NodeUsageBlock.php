<?php

namespace Drupal\node_usage\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node_usage\NodeUsageTable;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Includes user account block plus custom text before it.
 *
 * @Block(
 *   id = "node_usage",
 *   admin_label = @Translation("Node usage"),
 *   category = @Translation("Development"),
 * )
 */
class NodeUsageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The service to build the table
   *
   * @var \Drupal\node_usage\NodeUsageTable
   */
  protected NodeUsageTable $node_table;

  /**
   * Node table constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\node_usage\NodeUsageTable $node_table
   *   The node usage table service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeUsageTable $node_table) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->node_table = $node_table;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('node_usage.table')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => $this->node_table->GetNodeUsages(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view site reports');
  }
}

