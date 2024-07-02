<?php

namespace Drupal\lightning_media_image;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helps query and configure various display settings.
 *
 * @internal
 *   This is an internal part of Lightning Media Image and may be changed or
 *   removed at any time without warning. External code should not use this
 *   class.
 */
final class DisplayHelper implements ContainerInjectionInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * DisplayHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns the components newly added to a display.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The display config entity.
   *
   * @return array
   *   The newly added components.
   */
  private function getNewComponents(EntityDisplayInterface $display): array {
    if (isset($display->original)) {
      return array_diff_key($display->getComponents(), $display->original->getComponents());
    }
    else {
      return [];
    }
  }

  /**
   * Returns newly added field components, optionally filtered by a function.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The display config entity.
   * @param callable $filter
   *   (optional) The function on which to filter the fields, accepting the
   *   field storage definition as an argument.
   *
   * @return array
   *   The newly added components.
   */
  public function getNewFields(EntityDisplayInterface $display, callable $filter = NULL): array {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions(
      $display->getTargetEntityTypeId()
    );
    if ($filter) {
      $fields = array_filter($fields, $filter);
    }
    return array_intersect_key($this->getNewComponents($display), $fields);
  }

}
