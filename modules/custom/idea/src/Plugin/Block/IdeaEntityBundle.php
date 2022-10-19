<?php

namespace Drupal\idea\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\challenge\ContextEntityTrait;

/**
 * Provides a IdeaEntityBundle class.
 *
 * @Block(
 *   id = "idea_node_bundle",
 *   admin_label = @Translation("Entity bundle"),
 *   category = @Translation("Idea"),
 *   context = {
 *      "entity" = @ContextDefinition(
 *       "entity",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class IdeaEntityBundle extends BlockBase {

  use ContextEntityTrait;

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];

    // If displayed in layout builder node isn't presented.
   // if ($entity = $this->getEntity($this->getContexts(), 'entity')) {
    //  $build['content_type'] = [
     //   '#type' => 'html_tag',
      //  '#tag' => 'div',
      //  '#attributes' => [
      //    'class' => [
       //     'entity_bundle_label', 'entity_bundle_label--' . $entity->bundle(),
      //    ],
      //  ],
    //   '#value' => $entity->bundle() == 'article' ? $this->t('News') : $entity->bundle(),
    //  ];
    }

    return $build;
  }

}
