<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\DiskReference;
use Drupal\file\FileInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents creating a file entity from a file on disk.
 *
 * Use file->isNew() === FALSE to determine success.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesAddFileOnDiskEvent extends Event {

  /**
   * Constructs a new AuditFilesAddFileOnDisk.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference $reference
   *   The file on disk reference.
   * @param \Drupal\file\FileInterface|null $file
   *   Represents a file being constructed.
   */
  public function __construct(
    public readonly DiskReference $reference,
    public ?FileInterface $file = NULL,
  ) {
  }

}
