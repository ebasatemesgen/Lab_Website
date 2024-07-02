<?php

namespace Drupal\lightning_media;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a specialized storage handler for media types.
 *
 * @internal
 *   This is an internal part of Lightning Media and may be changed or removed
 *   at any time without warning. External code should not interact directly
 *   with this class.
 */
final class MediaTypeStorage extends ConfigEntityStorage {

  /**
   * The access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  private $accessHandler;

  /**
   * MediaTypeStorage constructor.
   *
   * @param \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_handler
   *   The access control handler.
   * @param mixed ...$arguments
   *   Additional arguments to pass to the parent constructor.
   */
  public function __construct(EntityAccessControlHandlerInterface $access_handler, ...$arguments) {
    parent::__construct(...$arguments);
    $this->accessHandler = $access_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager')->getAccessControlHandler('media'),
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, $check_access = FALSE) {
    if ($check_access) {
      $ids = array_filter(
        $ids ?: $this->getQuery()->execute(),
        [$this->accessHandler, 'createAccess']
      );
    }
    return parent::loadMultiple($ids);
  }

}
