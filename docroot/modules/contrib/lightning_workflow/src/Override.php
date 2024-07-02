<?php

namespace Drupal\lightning_workflow;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Helps tweak and override implementations of various things.
 *
 * @internal
 *   This is an internal part of Lightning Workflow and may be changed or
 *   removed at any time without warning. External code should not interact with
 *   this class.
 */
final class Override {

  /**
   * Overrides the class used for an entity handler.
   *
   * The replacement class is only used if its immediate parent is the handler
   * class specified by the entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $handler_type
   *   The handler type.
   * @param string $replacement_class
   *   The class to use.
   */
  public static function entityHandler(EntityTypeInterface $entity_type, $handler_type, $replacement_class) {
    if (get_parent_class($replacement_class) == $entity_type->getHandlerClass($handler_type)) {
      $entity_type->setHandlerClass($handler_type, $replacement_class);
    }
  }

}
