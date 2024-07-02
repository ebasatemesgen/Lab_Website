<?php

namespace Drupal\lightning_media;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Helps tweak and override implementations of various things.
 *
 * @internal
 *   This is an internal part of Lightning Media and may be changed or removed
 *   at any time without warning. External code should not use this class in
 *   any way!
 */
final class Override {

  /**
   * Overrides the class implementation specified in a plugin definition.
   *
   * The replacement class is only used if its immediate parent is the class
   * specified by the plugin definition.
   *
   * @param array $plugin_definition
   *   The plugin definition.
   * @param string $replacement_class
   *   The class to use.
   */
  public static function pluginClass(array &$plugin_definition, $replacement_class) {
    if (get_parent_class($replacement_class) == $plugin_definition['class']) {
      $plugin_definition['class'] = $replacement_class;
    }
  }

  /**
   * Overrides the class used for an entity form.
   *
   * The replacement class is only used if its immediate parent is the form
   * class used for the specified operation.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $replacement_class
   *   The class to use.
   * @param string $operation
   *   (optional) The entity operation.
   */
  public static function entityForm(EntityTypeInterface $entity_type, $replacement_class, $operation = 'default') {
    if (get_parent_class($replacement_class) == $entity_type->getFormClass($operation)) {
      $entity_type->setFormClass($operation, $replacement_class);
    }
  }

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
