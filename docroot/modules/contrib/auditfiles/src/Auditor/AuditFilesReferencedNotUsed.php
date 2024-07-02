<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\FileFieldReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * List all methods used in referenced not used functionality.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 * @template R of \Drupal\auditfiles\Reference\FileFieldReference
 */
final class AuditFilesReferencedNotUsed implements AuditFilesAuditorInterface {

  /**
   * Constructs a new AuditFilesReferencedNotUsed.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly Connection $connection,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return \Generator<\Drupal\auditfiles\Reference\FileFieldReference>
   */
  public function getReferences(): \Generator {
    $files_referenced = [];
    // Get a list of all files referenced in content.
    $fields = $field_data = [];
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('image');
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('file');
    $count = 0;
    foreach ($fields as $value) {
      foreach ($value as $table_prefix => $entity_type) {
        foreach ($entity_type as $key1 => $value1) {
          $field_data[$count]['table'] = $table_prefix . '__' . $key1;
          $field_data[$count]['column'] = $key1 . '_target_id';
          $field_data[$count]['entity_type'] = $table_prefix;
          $count++;
        }
      }
    }
    foreach ($field_data as $value) {
      $table = $value['table'];
      $column = $value['column'];
      $entity_type = $value['entity_type'];
      if ($this->connection->schema()->tableExists($table)) {
        $fu_query = $this->connection->select('file_usage', 'fu')->fields('fu', ['fid'])->execute()->fetchCol();
        $query = $this->connection->select($table, 't');
        $query->addField('t', 'entity_id');
        $query->addField('t', 'bundle');
        $query->addField('t', $column);
        if (!empty($fu_query)) {
          $query->condition($column, $fu_query, 'NOT IN');
        }
        $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
        if ($maximumRecords !== 0) {
          $query->range(0, $maximumRecords);
        }
        $file_references = $query->execute()->fetchAll();
        foreach ($file_references as $file_reference) {
          $files_referenced[] = FileFieldReference::create(
            $table,
            $column,
            $file_reference->entity_id,
            (int) $file_reference->{$column},
            $entity_type,
            $file_reference->bundle,
          );
        }
      }
    }

    yield from $files_referenced;
  }

}
