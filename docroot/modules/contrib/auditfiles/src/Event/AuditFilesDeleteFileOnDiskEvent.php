<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\DiskReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents a file-on-disk deletion.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesDeleteFileOnDiskEvent extends Event {

  /**
   * Constructs a new AuditFilesAddFileOnDisk.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference $reference
   *   The file on disk reference.
   * @param bool $wasDeleted
   *   Whether the file was deleted successfully, or NULL if no attempt has been
   *   made.
   */
  public function __construct(
    public readonly DiskReference $reference,
    public ?bool $wasDeleted = NULL,
  ) {
  }

}
