<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\FileEntityReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents deleting all usages for a file entity.
 *
 * The file entity may no longer exist.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesDeleteFileUsageEvent extends Event {

  public ?int $affectedUsages = NULL;

  /**
   * Constructs a new AuditFilesDeleteFileUsageEvent.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $reference
   *   A file entity reference.
   * @param bool $wasDeleted
   *   Whether the file entity usages were deleted successfully, or NULL if no
   *   attempt has been made.
   */
  public function __construct(
    public readonly FileEntityReference $reference,
    public ?bool $wasDeleted = NULL,
  ) {
  }

}
