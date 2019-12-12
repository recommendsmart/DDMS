<?php

namespace Drupal\dashboards_matomo\Plugin\Dashboard;

use Drupal\dashboards\Plugin\DashboardBase;

/**
 * Show account info.
 *
 * @Dashboard(
 *   id = "matomo_countries",
 *   label = @Translation("Show per country."),
 *   category = @Translation("Matomo"),
 * )
 */
class Country extends MatomoBase {

  /**
   * Lazy build callback.
   *
   * @param \Drupal\dashboards\Plugin\DashboardBase $plugin
   *   Matomo base plugin.
   * @param array $configuration
   *   Configuration.
   */
  public static function lazyBuild(DashboardBase $plugin, array $configuration): array {
    try {
      $response = $plugin->query('UserCountry.getCountry', [
        'filter_limit' => 30,
        'period' => $configuration['period'],
        'date' => $plugin->getDateTranslated($configuration['date']),
        'flat' => 1,
      ]);

      $plugin->buildDateRows($response, t('Time'), ['nb_visits']);
      $plugin->setChartType($configuration['chart_type']);

      return $plugin->renderChart();
    }
    catch (\Exception $ex) {
      return [
        '#markup' => t('Error occured: @error', ['@error' => $ex->getMessage()]),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
