<?php

namespace Drupal\maestro;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Maestro Production Assignments entity.
 *
 */
class MaestroProductionAssignmentsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    
    //return AccessResult::neutral();
    
    
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view maestro production assignment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit maestro production assignment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete maestro production assignment entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add maestro production assignment entities');
  }

}
