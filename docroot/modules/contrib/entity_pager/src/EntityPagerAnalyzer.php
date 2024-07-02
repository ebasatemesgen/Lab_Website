<?php

namespace Drupal\entity_pager;

use Drupal\entity_pager\Event\EntityPagerAnalyzeEvent;
use Drupal\entity_pager\Event\EntityPagerEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A class for analyzing an entity pager and providing feedback.
 */
class EntityPagerAnalyzer implements EntityPagerAnalyzerInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new EntityPagerAnalyzer object.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function analyze(EntityPagerInterface $entityPager) {
    $event = new EntityPagerAnalyzeEvent($entityPager);
    $this->eventDispatcher->dispatch($event, EntityPagerEvents::ENTITY_PAGER_ANALYZE);
    $logs = $event->getLogs();

    foreach ($logs as $message) {
      \Drupal::logger('entity_pager')->notice($message);
    }
  }

}
