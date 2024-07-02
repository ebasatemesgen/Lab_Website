<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\FileFieldReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents removing a file field reference.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesDeleteFileFieldReferenceEvent extends Event {

  /**
   * Constructs a new AuditFilesDeleteFileFieldReference.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference $reference
   *   The file field reference.
   * @param bool|null $wasDeleted
   *   Whether the file field references were deleted successfully, or NULL if
   *   no attempt has been made.
   */
  public function __construct(
    public readonly FileFieldReference $reference,
    public ?bool $wasDeleted = NULL,
  ) {
  }

}
