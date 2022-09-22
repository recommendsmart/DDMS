<?php

namespace Drupal\node_usage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node_usage\NodeUsageTable;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Provides route responses for the node_usage module.
 */
class NodeUsageController extends ControllerBase {

  /**
   * The service to build the table
   *
   * @var \Drupal\node_usage\NodeUsageTable
   */
  protected NodeUsageTable $node_table;

  /**
   * Node table constructor.
   *
   * @param \Drupal\node_usage\NodeUsageTable $node_table
   *   The node table service.
   */
  public function __construct(NodeUsageTable $node_table) {
    $this->node_table = $node_table;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): NodeUsageController {
    return new static(
      $container->get('node_usage.table')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function NodeUsagePage(): array {
    return [
      '#markup' => $this->node_table->GetNodeUsages(),
    ];
  }
}
