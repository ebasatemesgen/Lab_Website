<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\auditfiles\Event\AuditFilesDeleteFileEntityEvent;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Batch Worker to handle Deleting entity records from file_managed table.
 */
final class AuditFilesDeleteFileEntityBatchProcess {

  use AuditFilesBatchTrait;

  /**
   * Class constructor.
   */
  final protected function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly FileEntityReference $reference,
  ) {
  }

  /**
   * Batch process to delete file entities from file_managed not in file_usage.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $reference
   *   The file to process.
   * @param array $context
   *   Batch context.
   */
  public static function create(FileEntityReference $reference, array &$context): void {
    (new static(
      static::getDispatcher(), $reference,
    ))->dispatch($context);
  }

  /**
   * Processes removal of files from file_managed not in file_usage.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatch(array &$context): void {
    $event = new AuditFilesDeleteFileEntityEvent($this->reference);
    $this->eventDispatcher->dispatch($event);
    $context['message'] = \t('Processed file ID %file_id.', ['%file_id' => $this->reference->getId()]);

    $tArgs = ['%fid' => $event->reference->getId()];
    ($event->wasDeleted === TRUE)
      ? $this::messenger()->addStatus(\t('Successfully deleted File ID : %fid from the file_managed table.', $tArgs))
      : $this::messenger()->addError(\t('There was a problem deleting the record with file ID %fid from the file_managed table. Check the logs for more information.', $tArgs));
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
