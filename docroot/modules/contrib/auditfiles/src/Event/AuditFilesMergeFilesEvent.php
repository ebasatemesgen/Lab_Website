<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Event;

use Drupal\auditfiles\Reference\FileEntityReference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents merging one file into another.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesMergeFilesEvent extends Event {

  /**
   * Constructs a new AuditFilesMergeFilesEvent.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $canonicalFile
   *   The file to keep.
   * @param \Drupal\auditfiles\Reference\FileEntityReference $mergedFile
   *   The file to merge.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $messages
   *   Messages to output after dispatch is complete.
   */
  public function __construct(
    public readonly FileEntityReference $canonicalFile,
    public readonly FileEntityReference $mergedFile,
    public array $messages = [],
  ) {
  }

}
