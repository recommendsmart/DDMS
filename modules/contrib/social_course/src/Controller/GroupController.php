<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class GroupController.
 */
class GroupController extends EntityController {

  /**
   * Callback function of group page.
   */
  public function canonical(GroupInterface $group) {
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();
    $url = Url::fromRoute('social_group.stream', [
      'group' => $group->id(),
    ]);

    if (!in_array($group->bundle(), $bundles) && $url->access()) {
      return new RedirectResponse($url->toString());
    }

    return $this->redirect('view.group_information.page_group_about', [
      'group' => $group->id(),
    ]);
  }

  /**
   * Access callback of the group page.
   */
  public function access(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $access = AccessResult::forbidden();

    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();

    // Allow if group doesn't have field that regulates access or is published.
    if (!$group->hasField('status') || $group->get('status')->value) {
      $access = AccessResult::allowed();
    }
    // Allow if user has the 'bypass group access' permission.
    elseif ($account->hasPermission('bypass group access')) {
      $access = AccessResult::allowed();
    }
    // Allow if user has access to all unpublished groups.
    elseif ($account->hasPermission('view unpublished groups')) {
      $access = AccessResult::allowed();
    }
    // Allow if user is an author of the group and has access to view
    // own unpublished groups.
    elseif ($account->hasPermission('view own unpublished groups')) {
      if ($group->getOwnerId() === $account->id()) {
        $access = AccessResult::allowed();
      }
    }

    return $access
      ->cachePerPermissions()
      ->cachePerUser();
  }

}
