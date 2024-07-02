<?php

declare(strict_types=1);

namespace Drupal\auditfiles;

use Drupal\auditfiles\Event\AuditFilesAddFileOnDiskEvent;
use Drupal\auditfiles\Event\AuditFilesAddUsageForFileFieldReferenceEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileEntityEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileFieldReferenceEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileOnDiskEvent;
use Drupal\auditfiles\Event\AuditFilesDeleteFileUsageEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Default event listeners.
 *
 * To override, choose from:
 *  - Listen to events, set weight so your listener happens before this.
 *  - Alter subscribed events.
 *  - Utilize event stopPropagation
 *  - Set was* property on events to non-NULL value.
 *  - Remove this service entirely.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesListener implements EventSubscriberInterface {

  /**
   * Constructs a new AuditFilesListener.
   */
  final public function __construct(
    protected readonly Connection $connection,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly MimeTypeGuesserInterface $fileMimeTypeGuesser,
    protected readonly TimeInterface $time,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * An event subscriber for adding a file reference to the file_usage table.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  final public function listenerAddUsage(AuditFilesAddUsageForFileFieldReferenceEvent $event): void {
    if ($event->wasAdded !== NULL) {
      return;
    }

    $reference = $event->reference;

    // Make sure the file is not already in the database.
    $result = (int) $this->connection
      ->select('file_usage')
      ->condition('fid', $reference->getFileReference()->getId())
      // @todo This is hard coded for now, but need to determine how to figure out
      // which module needs to be here.
      ->condition('module', 'file')
      ->condition('type', $reference->entityTypeId)
      ->condition('id', $reference->entityId)
      ->countQuery()
      ->execute()
      ->fetchField();

    if (0 !== $result) {
      // The file is already in the file_usage table.
      $event->wasAdded = FALSE;
      return;
    }

    // The file is not already in the database, so add it.
    $this->connection
      ->insert('file_usage')
      ->fields([
        'fid' => $reference->getFileReference()->getId(),
        // @todo This is hard coded for now, but need to determine how to figure out
        // which module needs to be here.
        'module' => 'file',
        'type' => $reference->entityTypeId,
        'id' => $reference->entityId,
        'count' => 1,
      ])
      ->execute();

    $event->wasAdded = TRUE;
  }

  /**
   * An event subscriber for creating a file.
   *
   * @internal
   *   There is no extensibility promise for this method: Use events instead:
   *   set the file property in a listener with a weight before this listener.
   */
  final public function listenerCreateFile(AuditFilesAddFileOnDiskEvent $event): void {
    // Exit earlier if a file was created before this listener.
    if ($event->file !== NULL) {
      return;
    }

    $uri = $event->reference->getUri();
    $realFilenamePath = $this->fileSystem->realpath($uri);

    $file = $this->entityTypeManager
      ->getStorage('file')
      ->create();

    $file
      ->set('langcode', 'en')
      ->set('created', $this->time->getCurrentTime())
      ->setChangedTime($this->time->getCurrentTime());
    $file->setFilename(trim(basename($uri)));
    $file->setFileUri($uri);
    $file->setMimeType($this->fileMimeTypeGuesser->guessMimeType($realFilenamePath));
    $file->setSize(filesize($realFilenamePath));
    $file->setPermanent();

    $event->file = $file;
  }

  /**
   * An event subscriber for creating a file.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  final public function listenerDeleteFile(AuditFilesDeleteFileOnDiskEvent $event): void {
    if ($event->wasDeleted !== NULL) {
      return;
    }

    $event->wasDeleted = $this->fileSystem->delete(
      $event->reference->getUri(),
    );
  }

  /**
   * An event subscriber for deleting a file from the file_managed table.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  final public function listenerDeleteFileEntity(AuditFilesDeleteFileEntityEvent $event): void {
    if ($event->wasDeleted !== NULL) {
      return;
    }

    $event->reference->getFile()?->delete();
    $event->wasDeleted = TRUE;
  }

  /**
   * An event subscriber for deleting a file reference.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  final public function listenerDeleteFileFieldReference(AuditFilesDeleteFileFieldReferenceEvent $event): void {
    if ($event->wasDeleted !== NULL) {
      return;
    }

    $reference = $event->reference;
    $affected = (int) $this->connection->delete($reference->table)
      ->condition($reference->column, $reference->getFileReference()->getId())
      ->execute();
    $event->wasDeleted = ($affected !== 0);
  }

  /**
   * An event subscriber for deleting usage for a file.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  final public function listenerDeleteUsage(AuditFilesDeleteFileUsageEvent $event): void {
    if ($event->wasDeleted !== NULL) {
      return;
    }

    $event->wasDeleted = TRUE;
    $event->affectedUsages = (int) $this->connection
      ->delete('file_usage')
      ->condition('fid', $event->reference->getId())
      ->execute();
  }

  /**
   * An event subscriber for saving the file from listenerCreateFile.
   *
   * @internal
   *   There is no extensibility promise for this method: Use events instead:
   *   nullify the file property in a listener with a weight before this
   *   listener.
   */
  final public function listenerSaveFile(AuditFilesAddFileOnDiskEvent $event): void {
    if ($event->file !== NULL) {
      $event->file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AuditFilesAddFileOnDiskEvent::class => [
        ['listenerCreateFile', 0],
        ['listenerSaveFile', -1000],
      ],
      AuditFilesAddUsageForFileFieldReferenceEvent::class => ['listenerAddUsage'],
      AuditFilesDeleteFileEntityEvent::class => ['listenerDeleteFileEntity'],
      AuditFilesDeleteFileFieldReferenceEvent::class => ['listenerDeleteFileFieldReference'],
      AuditFilesDeleteFileOnDiskEvent::class => ['listenerDeleteFile'],
      AuditFilesDeleteFileUsageEvent::class => ['listenerDeleteUsage'],
    ];
  }

}
