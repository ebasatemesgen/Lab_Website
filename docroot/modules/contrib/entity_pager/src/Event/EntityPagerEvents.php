<?php

namespace Drupal\entity_pager\Event;

/**
 * Define events for the Entity Pager module.
 */
final class EntityPagerEvents {

  /**
   * Analyzing event name.
   *
   * This is fired when analyzing an entity pager, before the feedback is logged
   * to Drupal. It is intended to be use to register feedback for the user.
   *
   * @see \Drupal\entity_pager\Event\EntityPagerAnalyzeEvent
   */
  const ENTITY_PAGER_ANALYZE = 'entity_pager.analyze';

}
