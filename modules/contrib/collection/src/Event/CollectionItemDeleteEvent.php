<?php

namespace Drupal\collection\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\collection\Entity\CollectionItemInterface;

/**
 * Event that is fired when a collection_item is deleted.
 */
class CollectionItemDeleteEvent extends Event {

  /**
   * The collection item.
   *
   * @var \Drupal\collection\Entity\CollectionItemInterface
   */
  public $collectionItem;

  /**
   * Constructs the object.
   *
   * @param \Drupal\collection\Entity\CollectionItemInterface $collection_item
   *   The collection_item being deleted.
   */
  public function __construct(CollectionItemInterface $collection_item) {
    $this->collectionItem = $collection_item;
  }

}
