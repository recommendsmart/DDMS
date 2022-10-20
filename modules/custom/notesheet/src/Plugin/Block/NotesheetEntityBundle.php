<?php

namespace Drupal\notesheet\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\filecover\DknContextEntityTrait;

class NotesheetEntityBundle extends BlockBase {

  use DknContextEntityTrait;

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];

    // If displayed in layout builder node isn't presented.
    if ($entity = $this->getEntity($this->getContexts(), 'entity')) {
      $build['content_type'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'entity_bundle_label', 'entity_bundle_label--' . $entity->bundle(),
          ],
        ],
        '#value' => $entity->bundle() == 'article' ? $this->t('News') : $entity->bundle(),
      ];
    }

    return $build;
  }

}

