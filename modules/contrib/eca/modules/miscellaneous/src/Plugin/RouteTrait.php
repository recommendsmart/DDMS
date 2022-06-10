<?php

namespace Drupal\eca_misc\Plugin;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Trait for route related actions and conditions.
 *
 * @see \Drupal\eca_misc\Plugin\Action\TokenLoadRouteParameter
 * @see \Drupal\eca_misc\Plugin\ECA\Condition\RouteMatch
 */
trait RouteTrait {

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'request') {
      return [
        RouteInterface::ROUTE_CURRENT => $this->t('current'),
        RouteInterface::ROUTE_PARENT => $this->t('parent'),
        RouteInterface::ROUTE_MAIN => $this->t('main'),
      ];
    }
    return parent::getOptions($id);
  }

  /**
   * Builds and returns the route match depending on the plugin configuration.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match applicable to the current configuration.
   */
  protected function getRouteMatch(): RouteMatchInterface {
    /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch */
    $currentRouteMatch = \Drupal::service('current_route_match');
    switch ($this->configuration['request']) {
      case RouteInterface::ROUTE_MAIN:
        return $currentRouteMatch->getMasterRouteMatch();

      case RouteInterface::ROUTE_PARENT:
        return $currentRouteMatch->getParentRouteMatch();

      case RouteInterface::ROUTE_CURRENT:
      default:
        return $currentRouteMatch;

    }
  }

  /**
   * Provides a form field for ECA modellers to select the request type.
   *
   * Builds the configuration form for route related plugins to decide, which
   * request (main, parent or current) should be used for route matches.
   *
   * @param array $form
   *   The form to which the config field should be added.
   */
  protected function requestFormField(array &$form): void {
    $form['request'] = [
      '#type' => 'select',
      '#title' => $this->t('Request'),
      '#default_value' => $this->configuration['request'],
      '#options' => $this->getOptions('request'),
      '#weight' => -11,
    ];
  }

}
