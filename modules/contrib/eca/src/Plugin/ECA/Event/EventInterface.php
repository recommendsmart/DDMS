<?php

namespace Drupal\eca\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\EcaInterface;

/**
 * Interface for ECA event plugins.
 */
interface EventInterface extends EcaInterface {

  /**
   * Returns the fully-qualified class name of the according system event.
   *
   * @return string
   *   The fully-qualified class name.
   */
  public function drupalEventClass(): string;

  /**
   * Returns a wildcard for lazy loading.
   *
   * The pattern may be arbitrarily contracted between the system event class
   * and an event plugin. It's their responsibility that they properly match up.
   *
   * Wildcards are being used for a pre-emptive reduction of loading ECA
   * configurations. Their outcome should be as small as possible and their
   * calculation should be as fast as possible.
   *
   * Wildcards will be stored alongside ECA config IDs within the according
   * cache blob, which will be used to determine which ECA configurations need
   * to be loaded for a system event.
   *
   * @param string $eca_config_id
   *   The ID of the ECA configuration entity.
   * @param \Drupal\eca\Entity\Objects\EcaEvent $ecaEvent
   *   The modeled event of the ECA configuration entity.
   *
   * @return string
   *   The generated wildcard.
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string;

}
