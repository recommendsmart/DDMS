<?php

namespace Drupal\user_manual;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\user_manual\Entity\UserManual;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field value loading _all_ manual.
 */
class DocsListing extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the values.
   */
  protected function computeValue() {
    $site_docs = UserManual::loadMultiple();

    foreach ($site_docs as $delta => $value) {
      $this->list[$delta] = $this->createItem($delta, $value);
    }

  }

}
