<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\auditfiles\Event\AuditFilesMergeFilesEvent;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Process batch files.
 */
final class AuditFilesMergeFileReferencesBatchProcess {

  use AuditFilesBatchTrait;

  /**
   * Constructs a new AuditFilesMergeFileReferencesBatchProcess.
   */
  final protected function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly FileEntityReference $canonicalFile,
    protected readonly FileEntityReference $mergedFile,
  ) {
  }

  /**
   * The batch process for merging file references.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $canonicalFile
   *   The file to keep.
   * @param \Drupal\auditfiles\Reference\FileEntityReference $mergedFile
   *   The file to merge.
   * @param array $context
   *   Used by the Batch API to keep track of and pass data from one operation
   *   to the next.
   */
  public static function create(FileEntityReference $canonicalFile, FileEntityReference $mergedFile, array &$context): void {
    (new static(
      static::getDispatcher(), $canonicalFile, $mergedFile,
    ))->dispatch($context);
  }

  /**
   * Processes the file IDs to delete and merge.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatch(array &$context): void {
    $event = new AuditFilesMergeFilesEvent($this->canonicalFile, $this->mergedFile);
    $this->eventDispatcher->dispatch($event);

    $context['message'] = \t(
      'Merged file ID %file_being_merged into file ID %file_being_kept.',
      [
        '%file_being_kept' => $this->canonicalFile->getId(),
        '%file_being_merged' => $this->mergedFile->getId(),
      ],
    );

    foreach ($event->messages as $message) {
      $this::messenger()->addMessage($message);
    }
  }

  /**
   * Get the event dispatcher.
   */
  protected static function getDispatcher(): EventDispatcherInterface {
    return \Drupal::service('event_dispatcher');
  }

  /**
   * Messenger service.
   */
  protected static function messenger(): MessengerInterface {
    return \Drupal::service('messenger');
  }

}
