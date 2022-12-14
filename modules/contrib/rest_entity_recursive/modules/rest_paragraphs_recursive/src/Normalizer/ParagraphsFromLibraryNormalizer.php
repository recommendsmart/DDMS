<?php

namespace Drupal\rest_paragraphs_recursive\Normalizer;

/**
 * Customized normalizer for paragraph type 'from_library'.
 */
class ParagraphsFromLibraryNormalizer extends ParagraphsNormalizer {

  /**
   * Array of supported paragraph types.
   *
   * @var array
   */
  protected $supportedParagraphTypes = [
    'from_library',
  ];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    // Add the current entity as a cacheable dependency to make Drupal flush
    // the cache when the media entity gets updated.
    $this->addCacheableDependency($context, $entity->field_reusable_paragraph->entity);

    /* @var $entity \Drupal\Core\Entity\FieldableEntityInterface */
    $referenced_paragraphs = $entity->field_reusable_paragraph->entity->paragraphs;
    $normalized_values = $this->serializer->normalize($referenced_paragraphs, $format, $context);

    // Drop unnecesary array if there is only one element in it.
    if (is_array($normalized_values) && count($normalized_values) === 1) {
      return $normalized_values[0];
    }
    else {
      return $normalized_values;
    }
  }

}
