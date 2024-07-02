<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\auditfiles\Event\AuditFilesAddUsageForFileFieldReferenceEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileFieldReferenceEvent;
use Drupal\auditfiles\Reference\FileFieldReference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Process batch files.
 */
final class AuditFilesReferencedNotUsedBatchProcess {

  use AuditFilesBatchTrait;

  /**
   * Constructs a new AuditFilesReferencedNotUsedBatchProcess.
   */
  final protected function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly FileFieldReference $reference,
  ) {
  }

  /**
   * The batch process for adding files to the file_usage table.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference $reference
   *   The file field reference to process.
   * @param array $context
   *   Batch context.
   */
  public static function createAdd(FileFieldReference $reference, array &$context): void {
    (new static(
      static::getDispatcher(), $reference,
    ))->dispatchAdd($context);
  }

  /**
   * Adds files to the file_usage table.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatchAdd(array &$context): void {
    $event = new AuditFilesAddUsageForFileFieldReferenceEvent($this->reference);
    $this->eventDispatcher->dispatch($event);
    $context['message'] = \t('Processed file ID %file_id.', ['%file_id' => $this->reference->getFileReference()->getId()]);
    if ($event->wasAdded === TRUE) {
      static::getMessenger()->addMessage(\t('Usage added for file ID %fid successfully', [
        '%fid' => $event->reference->getFileReference()->getId(),
      ]));
    }
  }

  /**
   * The batch process for deleting file references from their content.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference $reference
   *   The file field reference to process.
   * @param array $context
   *   Batch context.
   */
  public static function createDelete(FileFieldReference $reference, array &$context): void {
    (new static(
      static::getDispatcher(), $reference,
    ))->dispatchDelete($context);
  }

  /**
   * Deletes file references from their content.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatchDelete(array &$context): void {
    $event = new AuditFilesDeleteFileFieldReferenceEvent($this->reference);
    $this->eventDispatcher->dispatch($event);
    $context['message'] = \t('Processed file ID %file_id.', ['%file_id' => $this->reference->getFileReference()->getId()]);
    if ($event->wasDeleted === TRUE) {
      static::getMessenger()->addMessage(\t('File ID %fid deleted successfully', [
        '%fid' => $event->reference->getFileReference()->getId(),
      ]));
    }
    else {
      static::getMessenger()->addMessage(\t('There was a problem deleting the reference to file ID %fid in the %entity_type with ID %eid. Check the logs for more information.', [
        '%entity_type' => $event->reference->getSourceEntityTypeId(),
        '%eid' => $event->reference->getSourceEntityId(),
        '%fid' => $event->reference->getFileReference()->getId(),
      ]));
    }
  }

  /**
   * Get the event dispatcher.
   */
  protected static function getDispatcher(): EventDispatcherInterface {
    return \Drupal::service('event_dispatcher');
  }

}
