<?php

namespace Drupal\eca\Entity;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\eca\ConfigurableLoggerChannel;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage handler for ECA configurations.
 */
class EcaStorage extends ConfigEntityStorage {

  /**
   * Mapped configurations by event class usage.
   *
   * @var array|null
   */
  protected ?array $configByEvents;

  /**
   * The cache backend for storing prebuilt information.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->setCacheBackend($container->get('cache.default'));
    $instance->setLogger($container->get('logger.channel.eca'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $data = parent::mapToStorageRecord($entity);
    foreach (['events', 'conditions', 'actions', 'gateways'] as $type) {
      if (!isset($data[$type])) {
        $data[$type] = [];
      }
      foreach ($data[$type] as &$item) {
        if (isset($item['fields'])) {
          $fields = [];
          foreach ($item['fields'] as $key => $value) {
            $fields[] = [
              'key' => $key,
              'value' => $value,
            ];
          }
          $item['fields'] = $fields;
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    foreach ($records as &$record) {
      foreach (['events', 'conditions', 'actions', 'gateways'] as $type) {
        if (!isset($record[$type])) {
          $record[$type] = [];
        }
        foreach ($record[$type] as &$item) {
          if (isset($item['fields'])) {
            $fields = [];
            foreach ($item['fields'] as $field) {
              if (!isset($field['key']) && !isset($field['value'])) {
                // This is the old schema, nothing to convert.
                break 3;
              }
              $fields[$field['key']] = $field['value'];
            }
            $item['fields'] = $fields;
          }
        }
      }
    }
    return parent::mapFromStorageRecords($records);
  }

  /**
   * Loads all ECA configurations that make use of the given event.
   *
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   The event object.
   * @param string $event_name
   *   The name of the event.
   *
   * @return \Drupal\eca\Entity\Eca[]
   *   The configurations, keyed by entity ID.
   */
  public function loadByEvent(Event $event, string $event_name): array {
    if (!isset($this->configByEvents)) {
      $cid = 'eca:storage:events';
      if ($cached = $this->cacheBackend->get($cid)) {
        $this->configByEvents = $cached->data;
      }
      else {
        $this->configByEvents = [];
        $entities = $this->loadMultiple();
        // Sort the configurations by weight and label.
        uasort($entities, [$this->entityType->getClass(), 'sort']);
        /** @var \Drupal\eca\Entity\Eca $eca */
        foreach ($entities as $eca) {
          if (!$eca->status()) {
            continue;
          }
          foreach ($eca->getUsedEvents() as $ecaEvent) {
            $eca_id = $eca->id();
            $plugin = $ecaEvent->getPlugin();
            $drupal_id = $plugin->drupalId();
            $wildcard = $plugin->lazyLoadingWildcard($eca_id, $ecaEvent);
            if (!isset($this->configByEvents[$drupal_id])) {
              $this->configByEvents[$drupal_id] = [$eca_id => [$wildcard]];
            }
            elseif (!isset($this->configByEvents[$drupal_id][$eca_id])) {
              $this->configByEvents[$drupal_id][$eca_id] = [$wildcard];
            }
            elseif (!in_array($wildcard, $this->configByEvents[$drupal_id][$eca_id], TRUE)) {
              $this->configByEvents[$drupal_id][$eca_id][] = $wildcard;
            }
          }
        }
        $this->cacheBackend->set($cid, $this->configByEvents, CacheBackendInterface::CACHE_PERMANENT, ['config:eca_list']);
        $this->logger->debug('Rebuilt cache array for EcaStorage::loadByEvent().');
      }
    }
    if (empty($this->configByEvents[$event_name])) {
      return [];
    }
    $context = ['%event' => $event_name];
    if ($event instanceof ConditionalApplianceInterface) {
      $eca_ids = [];
      foreach ($this->configByEvents[$event_name] as $eca_id => $wildcards) {
        $wildcard_passed = FALSE;
        $context['%ecaid'] = $eca_id;
        foreach ($wildcards as $wildcard) {
          if ($wildcard_passed = $event->appliesForLazyLoadingWildcard($wildcard)) {
            $eca_ids[] = $eca_id;
            $this->logger->debug('Lazy appliance check for event %event regarding ECA ID %ecaid resulted to apply.', $context);
            break;
          }
        }
        if (!$wildcard_passed) {
          $this->logger->debug('Lazy appliance check for event %event regarding ECA ID %ecaid resulted to not apply.', $context);
        }
      }
    }
    else {
      $eca_ids = array_keys($this->configByEvents[$event_name]);
    }
    if ($eca_ids) {
      $context['%eca_ids'] = implode(', ', $eca_ids);
      $this->logger->debug('Loading ECA configurations for event %event: %eca_ids.', $context);
      return $this->loadMultiple($eca_ids);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL): void {
    $this->configByEvents = NULL;
    parent::resetCache($ids);
  }

  /**
   * Set the cache backend for storing prebuilt information.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend): void {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Set the logger.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function setLogger(LoggerChannelInterface $logger): void {
    $this->logger = $logger;
  }

}
