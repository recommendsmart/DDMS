<?php

namespace Drupal\user_manual\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the User Manual Type.
 *
 * @ConfigEntityType(
 *   id = "user_manual_type",
 *   label = @Translation("User Manual type"),
 *   bundle_of = "user_manual",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_prefix = "user_manual_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_manual\UserManualTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\user_manual\Form\UserManualTypeEntityForm",
 *       "add" = "Drupal\user_manual\Form\UserManualTypeEntityForm",
 *       "edit" = "Drupal\user_manual\Form\UserManualTypeEntityForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer user_manual types",
 *   links = {
 *     "canonical" = "/admin/structure/user_manual_type/{user_manual_type}",
 *     "add-form" = "/admin/structure/user_manual_type/add",
 *     "edit-form" = "/admin/structure/user_manual_type/{user_manual_type}/edit",
 *     "delete-form" = "/admin/structure/user_manual_type/{user_manual_type}/delete",
 *     "collection" = "/admin/structure/user_manual_type",
 *   }
 * )
 */
class UserManualType extends ConfigEntityBundleBase  implements UserManualTypeEntityInterface {

  /**
   * The machine name of the practical type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the practical type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the practical type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
