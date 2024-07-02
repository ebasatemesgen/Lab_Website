<?php

namespace Drupal\entity_pager;

/**
 * Defines an interface for an entity pager.
 */
interface EntityPagerInterface {

  /**
   * Gets the view for the entity pager.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view object.
   */
  public function getView();

  /**
   * Gets an array of entity pager link render arrays.
   *
   * @return array[]
   *   The link render arrays.
   */
  public function getLinks();

  /**
   * Get result count word.
   *
   * Get the word associated with the count of results.
   * i.e. one, many
   * The number in the result converted to a summary word for privacy.
   *
   * @return string
   *   Get a text representation the number of records e.g. none, one or many.
   */
  public function getCountWord();

  /**
   * Gets the entity object this entity pager is for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or NULL if no entity found.
   */
  public function getEntity();

  /**
   * Returns the options this entity pager was created with.
   *
   * @return array
   *   The options array.
   */
  public function getOptions();

}
