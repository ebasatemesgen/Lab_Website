<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\auditfiles\Event\AuditFilesDeleteFileUsageEvent;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Batch worker to handle deleting file usage.
 */
final class AuditFilesDeleteFileUsageBatchProcess {

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
   * Batch process to delete file usage.
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
    $event = new AuditFilesDeleteFileUsageEvent($this->reference);
    $this->eventDispatcher->dispatch($event);
    $context['message'] = \t('Processed file ID %file_id.', ['%file_id' => $this->reference->getId()]);

    $tArgs = ['%fid' => $event->reference->getId()];
    ($event->wasDeleted === TRUE)
      ? $this::messenger()->addStatus(\t('Successfully deleted usage for file ID: %fid', $tArgs))
      : $this::messenger()->addError(\t('There was a problem deleting file usage for file ID: %fid', $tArgs));
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
