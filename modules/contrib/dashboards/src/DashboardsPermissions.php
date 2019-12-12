<?php

namespace Drupal\dashboards;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard permissions.
 */
class DashboardsPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a TaxonomyViewsIntegratorPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Get permissions for Taxonomy Views Integrator.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions(): array {
    $permissions = [];

    foreach ($this->entityManager->getStorage('dashboard')->loadMultiple() as $dashboard) {
      $permissions += [
        'can view ' . $dashboard->id() . ' dashboard' => [
          'title' => $this->t('Can view %dashboard dashboard.', ['%dashboard' => $dashboard->label()]),
        ],
      ];

      $permissions += [
        'can override ' . $dashboard->id() . ' dashboard' => [
          'title' => $this->t('Can override %dashboard dashboard', ['%dashboard' => $dashboard->label()]),
        ],
      ];

    }

    return $permissions;
  }

}
