<?php

namespace Drupal\node_usage;

use Drupal;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class NodeUsageTable {

  use StringTranslationTrait;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Node table constructor.
   *
   */
  public function __construct() {
    $this->database = Drupal::database();
  }


  /**
   * Create node usage display; may be used as a page or a block
   *
   * @return mixed content for page or block, rendered as string
   *   content for page or block, rendered as string
   */
  public function GetNodeUsages() {
    // get list of all node types
    $entityTypeManager = \Drupal::service('entity_type.manager');

    $types = [];
    $contentTypes = $entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }

    // find counts of node types
    $query = $this->database->select('node', 'n');
    $query->addField('n', 'type', 'Type');
    $query->addExpression('COUNT(type)', 'node_count');
    $query->groupBy('type');

    $result = $query->execute();
    $counts = [];
    foreach ($result as $record) {
      $type = $record->Type;
      if (!is_null($type)) {
        $counts[$type] = $record->node_count;
      }
    }

    // Build output
    $header = [
      'Content type'         => t('Content type'),
      'Count'                => t('Count'),
    ];

    $rows = array();
    foreach ($types as $type => $name) {
      $count = $counts[$type] ?? 0;
      $rows[] = array(
        array('data' => $name,  'class' => array('c-name')),
        array('data' => $count, 'class' => array('c-count')),
      );
    }

    $output = [
      '#type' => 'table',
      '#id' => 'node-usage-table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('class' => array('node-usage')),
      '#attached' => ['library' => ['node_usage/node_usage']],
    ];

    return Drupal::service('renderer')->render($output);
  }

}
