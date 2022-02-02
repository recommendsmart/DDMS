<?php

namespace Drupal\Core\Flood;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the memory flood backend. This is used for testing.
 */
class MemoryBackend implements FloodInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * An array holding flood events, keyed by event name and identifier.
   */
  protected $events = [];

  /**
   * Construct the MemoryBackend.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $time = $this->getCurrentMicroTime();
    $this->events[$name][$identifier][] = [
      'time' => $time,
      'expire' => $time + $window,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    unset($this->events[$name][$identifier]);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    if (!isset($this->events[$name][$identifier])) {
      return $threshold > 0;
    }
    $limit = $this->getCurrentMicroTime() - $window;
    $number = count(array_filter(
      $this->events[$name][$identifier],
      function ($timestamp) use ($limit) {
        return $timestamp['time'] > $limit;
      }
    ));
    return ($number < $threshold);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $time = $this->getCurrentMicroTime();
    foreach ($this->events as $name => $identifiers) {
      foreach (array_keys($identifiers) as $identifier) {
        $this->events[$name][$identifier] = array_filter(
          $this->events[$name][$identifier],
          function (array $event) use ($time): bool {
            // Keep events where expiration is after current time.
            return $event['expire'] > $time;
          }
        );
      }
    }
  }

  /**
   * Return current Unix timestamp with microseconds.
   *
   * @return float
   *   The current time in seconds with microseconds.
   */
  protected function getCurrentMicroTime(): float {
    return microtime(TRUE);
  }

}
