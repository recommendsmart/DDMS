<?php

namespace Drupal\user_manual;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of User Manual entities.
 *
 * @ingroup user_manual
 */
class UserManualListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('User Manual ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\user_manual\Entity\UserManual $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.user_manual.canonical',
      ['user_manual' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $ops = parent::getDefaultOperations($entity);
    if ($entity->access('view')) {
      $ops['view'] = [
        'title' => $this->t('View'),
        'weight' => 0,
        'url' => $this->ensureDestination($entity->toUrl('canonical')),
      ];
    }
    return $ops;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#prefix'] = Link::createFromRoute(
      $this->t('Add User Manual'),
      'entity.user_manual.add_page', [], ['attributes' => ['class' => 'button button--action button--primary']])->toString();
    return $build + parent::render();
  }

}
