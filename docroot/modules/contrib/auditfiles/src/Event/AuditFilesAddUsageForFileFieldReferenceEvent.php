<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\FileFieldReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents creating file usage data for a file field reference.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesAddUsageForFileFieldReferenceEvent extends Event {

  /**
   * Constructs a new AuditFilesAddUsageForFileFieldReference.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference $reference
   *   The file field reference.
   * @param bool|null $wasAdded
   *   Whether the file entity usages were added successfully, or NULL if no
   *   attempt has been made.
   */
  public function __construct(
    public readonly FileFieldReference $reference,
    public ?bool $wasAdded = NULL,
  ) {
  }

}
