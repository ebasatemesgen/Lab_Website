<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Event\AuditFilesMergeFilesEvent;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Define all methods that used in merge file references functionality.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 * @template R of \Drupal\auditfiles\Reference\FileEntityReference
 */
final class AuditFilesMergeFileReferences implements AuditFilesAuditorInterface, EventSubscriberInterface {

  /**
   * Constructs a new AuditFilesMergeFileReferences.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly Connection $connection,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * Yields all files with duplicate file names.
   */
  public function getReferences(): \Generator {
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $query = $this->connection->select('file_managed', 'fm')
      ->fields('fm', ['fid', 'filename'])
      ->orderBy('filename', 'ASC');

    if ($maximumRecords !== 0) {
      $query->range(0, $maximumRecords);
    }
    $files = $query->execute()->fetchAllKeyed();
    $show_single_file_names = $this->auditFilesConfig->isMergeFileReferencesShowSingleFileNames();
    foreach ($files as $file_id => $file_name) {
      if ($show_single_file_names) {
        yield FileEntityReference::create((int) $file_id);
      }
      else {
        $query2 = 'SELECT COUNT(fid) count FROM {file_managed} WHERE filename = :filename AND fid != :fid';
        $results2 = $this->connection->query(
          $query2,
          [
            ':filename' => $file_name,
            ':fid' => $file_id,
          ],
        )->fetchField();
        if ($results2 > 0) {
          yield FileEntityReference::create((int) $file_id);
        }
      }
    }
  }

  /**
   * An event subscriber for merging files.
   *
   * @internal
   *   There is no extensibility promise for this method; Use events instead.
   */
  public function listenerMergeFiles(AuditFilesMergeFilesEvent $event): void {
    $file_being_kept = $event->canonicalFile->getId();
    $file_being_merged = $event->mergedFile->getId();
    $file_being_kept_results = $this->connection->select('file_usage', 'fu')
      ->fields('fu', ['module', 'type', 'id', 'count'])
      ->condition('fid', $file_being_kept)
      ->execute()
      ->fetchAll();
    if (count($file_being_kept_results) === 0) {
      $event->messages[] = \t('There was no file usage data found for the file you choose to keep. No changes were made.');
      return;
    }

    $file_being_kept_data = reset($file_being_kept_results);
    $file_being_merged_results = $this->connection->select('file_usage', 'fu')
      ->fields('fu', ['module', 'type', 'id', 'count'])
      ->condition('fid', $file_being_merged)
      ->execute()
      ->fetchAll();
    if (count($file_being_merged_results) === 0) {
      $event->messages[] = \t('There was an error retrieving the file usage data from the database for file ID %fid. Please check the files in one of the other reports. No changes were made for this file.', ['%fid' => $file_being_merged]);
      return;
    }

    $file_being_merged_data = reset($file_being_merged_results);
    $file_being_merged_uri_results = $this->connection->select('file_managed', 'fm')
      ->fields('fm', ['uri'])
      ->condition('fid', $file_being_merged)
      ->execute()
      ->fetchAll();
    $file_being_merged_uri = reset($file_being_merged_uri_results);
    if ($file_being_kept_data->id == $file_being_merged_data->id) {
      $file_being_kept_data->count += $file_being_merged_data->count;
      // Delete the unnecessary entry from the file_usage table.
      $this->connection->delete('file_usage')
        ->condition('fid', $file_being_merged)
        ->execute();
      // Update the entry for the file being kept.
      $this->connection->update('file_usage')
        ->fields(['count' => $file_being_kept_data->count])
        ->condition('fid', $file_being_kept)
        ->condition('module', $file_being_kept_data->module)
        ->condition('type', $file_being_kept_data->type)
        ->condition('id', $file_being_kept_data->id)
        ->execute();
    }
    else {
      // Update any fields that might be pointing to the file being merged
      // before modifying the file_usage table.
      $this->auditfilesMergeFileReferencesUpdateFileFields($file_being_kept, $file_being_merged);

      $this->connection->update('file_usage')
        ->fields(['fid' => $file_being_kept])
        ->condition('fid', $file_being_merged)
        ->condition('module', $file_being_merged_data->module)
        ->condition('type', $file_being_merged_data->type)
        ->condition('id', $file_being_merged_data->id)
        ->execute();
    }
    // Delete the unnecessary entries from the file_managed table.
    $this->connection->delete('file_managed')
      ->condition('fid', $file_being_merged)
      ->execute();
    // Delete the duplicate file.
    if (!empty($file_being_merged_uri->uri)) {
      $this->fileSystem->delete($file_being_merged_uri->uri);
    }
  }

  /**
   * Updates any fields that might be pointing to the file being merged.
   *
   * @param int $file_being_kept
   *   The file ID of the file to merge the other into.
   * @param int $file_being_merged
   *   The file ID of the file to merge.
   */
  private function auditfilesMergeFileReferencesUpdateFileFields(int $file_being_kept, int $file_being_merged): void {
    $file_being_merged_fields = $this->fileGetFileReferences(
      $this->getFileStorage()->load($file_being_merged),
      NULL,
      EntityStorageInterface::FIELD_LOAD_REVISION,
      NULL,
    );

    foreach ($file_being_merged_fields as $field_name => $field_references) {
      foreach ($field_references as $entity_type => $type_references) {
        foreach ($type_references as $id => $reference) {
          $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
          if ($entity instanceof ContentEntityInterface) {
            $field_items = $entity->get($field_name)->getValue();
            $alt = $field_items[0]['alt'];
            $title = $field_items[0]['title'] ? $field_items[0]['title'] : '';
            foreach ($field_items as $item) {
              if ($item['target_id'] == $file_being_merged) {
                $file_object_being_kept = $this->getFileStorage()->load($file_being_kept);
                if (!empty($file_object_being_kept) && $entity->get($field_name)->getValue() != $file_being_kept) {
                  $entity->set('field_name', [
                    'target_id' => $file_object_being_kept->id(),
                    'alt' => $alt,
                    'title' => $title,
                  ]);
                }
                $entity->save();
                break;
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AuditFilesMergeFilesEvent::class => ['listenerMergeFiles'],
    ];
  }

  /**
   * Get the file storage.
   */
  private function getFileStorage(): FileStorageInterface {
    return $this->entityTypeManager->getStorage('file');
  }

  /**
   * Proxy to get file references.
   */
  private function fileGetFileReferences(FileInterface $file, FieldDefinitionInterface $field = NULL, string $age = EntityStorageInterface::FIELD_LOAD_REVISION, ?string $field_type = 'file'): array {
    // \file_get_file_references() $age is incorrectly typehinted as int.
    // @phpstan-ignore-next-line
    return \file_get_file_references($file, $field, $age, $field_type);
  }

}
