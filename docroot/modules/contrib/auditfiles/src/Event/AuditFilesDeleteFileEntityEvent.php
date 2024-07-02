<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\FileEntityReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents deleting a file entity.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesDeleteFileEntityEvent extends Event {

  /**
   * Constructs a new AuditFilesDeleteFileEntityEvent.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $reference
   *   A file entity reference.
   * @param bool $wasDeleted
   *   Whether the file entity was deleted successfully, or NULL if no attempt
   *   has been made.
   */
  public function __construct(
    public readonly FileEntityReference $reference,
    public ?bool $wasDeleted = NULL,
  ) {
  }

}
