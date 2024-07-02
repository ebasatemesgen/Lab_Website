<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Files in usage table, but no reference from field field tables.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 * @template R of \Drupal\auditfiles\Reference\FileEntityReference
 */
final class AuditFilesUsedNotReferenced implements AuditFilesAuditorInterface {

  /**
   * Constructs a new AuditFilesUsedNotReferenced.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly Connection $connection,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(): \Generator {
    $maximum_records = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $query = $this->connection->select('file_usage', 'fu')
      ->fields('fu', ['fid'])
      ->distinct();
    if ($maximum_records > 0) {
      $query->range(0, $maximum_records);
    }
    $files_in_file_usage = $query->execute()->fetchCol();
    $field_data = [];
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('image');
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('file');
    $count = 0;
    foreach ($fields as $value) {
      foreach ($value as $table_prefix => $entity_type) {
        foreach ($entity_type as $key1 => $value1) {
          if ($this->entityTypeManager->getStorage($table_prefix)->getEntityType()->isRevisionable()) {
            $field_data[$count]['table'] = $table_prefix . '_revision__' . $key1;
          }
          else {
            $field_data[$count]['table'] = $table_prefix . '__' . $key1;
          }
          $field_data[$count]['column'] = $key1 . '_target_id';
          $count++;
        }
      }
    }
    foreach ($field_data as ['table' => $table, 'column' => $column]) {
      if (!$this->connection->schema()->tableExists($table)) {
        continue;
      }

      $query = "SELECT t.$column FROM {{$table}} AS t INNER JOIN {file_usage} AS f ON f.fid = t.$column";
      $result = $this->connection->query($query)->fetchCol();
      // Exclude files which are in use.
      $files_in_file_usage = array_diff($files_in_file_usage, $result);
    }

    // Return unused files.
    yield from array_map(function (string $fid): FileEntityReference {
      return FileEntityReference::create((int) $fid);
    }, $files_in_file_usage);
  }

}
