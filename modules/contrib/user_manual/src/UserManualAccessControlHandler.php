<?php

namespace Drupal\user_manual;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the user manual entity.
 */
class UserManualAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Administer access permission value to user manual.
   */
  const ADMINISTER_USER_MANUAL_TYPES_PERMISSION = 'administer user_manual types';

  /**
   * Administer access permission value to user manual.
   */
  const ADMINISTER_USER_MANUAL_PERMISSION = 'administer user_manual';

  /**
   * View user manual permission value.
   */
  const VIEW_USER_MANUAL_PERMISSION = 'view user_manual';

  /**
   * Create user manual permission value.
   */
  const CREATE_USER_MANUAL_PERMISSION = 'create user_manual';

  /**
   * Edit own user manual permission value.
   */
  const EDIT_OWN_USER_MANUAL_PERMISSION = 'edit own user_manual';

  /**
   * Edit any user manual permission value.
   */
  const EDIT_ANY_USER_MANUAL_PERMISSION = 'edit any user_manual';

  /**
   * Delete own user manual permission value.
   */
  const DELETE_OWN_USER_MANUAL_PERMISSION = 'delete own user_manual';

  /**
   * Delete own user manual permission value.
   */
  const DELETE_ANY_USER_MANUAL_PERMISSION = 'delete any user_manual';

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if ($account->hasPermission(self::ADMINISTER_USER_MANUAL_PERMISSION)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, self::VIEW_USER_MANUAL_PERMISSION);

      case 'update':
        $permissions = [
          "edit user_manual in {$entity->bundle()}",
          self::EDIT_ANY_USER_MANUAL_PERMISSION
        ];
        if ($account->id() == $entity->getOwnerId()) {
          $permissions[] = self::EDIT_OWN_USER_MANUAL_PERMISSION;
        }

        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');

      case 'delete':
        $permissions = [
          "delete user_manual in {$entity->bundle()}",
          self::DELETE_ANY_USER_MANUAL_PERMISSION
        ];
        if ($account->id() == $entity->getOwnerId()) {
          $permissions[] = self::DELETE_OWN_USER_MANUAL_PERMISSION;
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      self::CREATE_USER_MANUAL_PERMISSION,
      self::ADMINISTER_USER_MANUAL_PERMISSION,
      "create user_manual in $entity_bundle",
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
