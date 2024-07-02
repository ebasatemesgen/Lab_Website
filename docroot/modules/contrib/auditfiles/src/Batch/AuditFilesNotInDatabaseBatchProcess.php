<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\auditfiles\Event\AuditFilesAddFileOnDiskEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileOnDiskEvent;
use Drupal\auditfiles\Reference\DiskReference;
use Drupal\Component\Utility\Html;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Process batch files.
 */
final class AuditFilesNotInDatabaseBatchProcess {

  use AuditFilesBatchTrait;

  /**
   * Constructs a new AuditFilesNotInDatabaseBatchProcess.
   */
  final protected function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly DiskReference $reference,
  ) {
  }

  /**
   * The batch process for adding the file.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference $reference
   *   The file to process.
   * @param array $context
   *   Batch context.
   */
  public static function createAdd(DiskReference $reference, array &$context): void {
    (new static(
      static::getDispatcher(), $reference,
    ))->dispatchAdd($context);
  }

  /**
   * Adds filenames referenced in content in file_managed but not in file_usage.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatchAdd(array &$context): void {
    $event = new AuditFilesAddFileOnDiskEvent($this->reference);
    $this->eventDispatcher->dispatch($event);

    $file = $event->file;
    $path = $event->reference->getUri();
    if ($file === NULL) {
      $this->messenger()->addStatus(\t('File %file was not created', ['%file' => $path]));
      return;
    }

    $link = match (TRUE) {
      $file->hasLinkTemplate('canonical') && $file->toUrl(rel: 'canonical')->access() => $file->toLink(rel: 'canonical'),
      $file->hasLinkTemplate('edit-form') && $file->toUrl(rel: 'edit-form')->access() => $file->toLink(rel: 'edit-form'),
      default => NULL,
    };

    // When file_entity.module is available a link (page) may be present.
    (NULL !== $link)
      ? $this->messenger()->addStatus(\t('Successfully added %file to the database.', ['%file' => $link->toString()]))
      : $this->messenger()->addError(\t('Successfully added %file to the database.', ['%file' => $path]));

    $context['results'][] = Html::escape($path);
    $context['message'] = \t('Created file from %filename.', ['%filename' => $path]);
  }

  /**
   * The batch process for deleting the file.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference $reference
   *   File name that needs to be processed.
   * @param array $context
   *   Batch context.
   */
  public static function createDelete(DiskReference $reference, array &$context): void {
    (new static(
      static::getDispatcher(), $reference,
    ))->dispatchDelete($context);
  }

  /**
   * Deletes filenames referenced in content frm file_managed not in file_usage.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatchDelete(array &$context): void {
    $event = new AuditFilesDeleteFileOnDiskEvent($this->reference);
    $this->eventDispatcher->dispatch($event);
    $path = $event->reference->getUri();

    $tArgs = ['%file' => $path];
    ($event->wasDeleted === TRUE)
      ? $this::messenger()->addStatus(\t('Successfully deleted %file from the server.', $tArgs))
      : $this::messenger()->addError(\t('Failed to delete %file from the server.', $tArgs));

    $context['results'][] = Html::escape($path);
    $context['message'] = \t('Deleted file %filename.', ['%filename' => $path]);
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
