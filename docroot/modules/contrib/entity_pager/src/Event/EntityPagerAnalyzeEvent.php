<?php

namespace Drupal\entity_pager\Event;

use Drupal\entity_pager\EntityPagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Analyze event object.
 */
class EntityPagerAnalyzeEvent extends Event {

  /**
   * The entity pager being analyzed.
   *
   * @var \Drupal\entity_pager\EntityPagerInterface
   */
  protected $entityPager;

  /**
   * The log messages supplied.
   *
   * @var array
   */
  protected $logs = [];

  /**
   * Constructs a new EntityPagerAnalyzeEvent.
   *
   * @param \Drupal\entity_pager\EntityPagerInterface $entityPager
   *   The entity pager.
   */
  public function __construct(EntityPagerInterface $entityPager) {
    $this->entityPager = $entityPager;
  }

  /**
   * Gets the entity pager.
   *
   * @return \Drupal\entity_pager\EntityPagerInterface
   *   The entity pager.
   */
  public function getEntityPager() {
    return $this->entityPager;
  }

  /**
   * Sets the entity pager.
   *
   * @param \Drupal\entity_pager\EntityPagerInterface $entityPager
   *   The entity pager.
   */
  public function setEntityPager(EntityPagerInterface $entityPager) {
    $this->entityPager = $entityPager;
  }

  /**
   * Gets the logs array.
   *
   * @return array
   *   The logs array.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Sets the logs array.
   *
   * @param array $logs
   *   The logs array.
   */
  public function setLogs(array $logs) {
    $this->logs = $logs;
  }

  /**
   * Logs one or more messages.
   *
   * @param array|string $messages
   *   One or more messages to log.
   */
  public function log($messages) {
    foreach ((array) $messages as $message) {
      $this->logs[] = $message;
    }
  }

}
