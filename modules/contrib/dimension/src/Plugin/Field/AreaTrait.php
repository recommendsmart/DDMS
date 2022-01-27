<?php

namespace Drupal\dimension\Plugin\Field;

trait AreaTrait {

  /**
   * {@inheritdoc}
   */
  public static function fields(): array {
    return [
      'width' => t('Width'),
      'height' => t('Height'),
    ];
  }

}
