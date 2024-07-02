<?php

namespace Drupal\entity_pager;

/**
 * An interface for an Entity Pager Analyzer.
 */
interface EntityPagerAnalyzerInterface {

  /**
   * Analyzes the given entity pager.
   *
   * @param \Drupal\entity_pager\EntityPagerInterface $entityPager
   *   The entity pager to analyze.
   */
  public function analyze(EntityPagerInterface $entityPager);

}
