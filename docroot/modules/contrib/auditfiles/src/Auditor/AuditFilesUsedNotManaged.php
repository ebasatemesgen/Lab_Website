<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\FileUsageReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;

/**
 * Form for Files used not managed functionality.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 * @template R of \Drupal\auditfiles\Reference\FileUsageReference
 */
final class AuditFilesUsedNotManaged implements AuditFilesAuditorInterface {

  /**
   * Constructs a new AuditFilesUsedNotManaged.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly Connection $connection,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(): \Generator {
    // Get all the file IDs in the file_usage table not in the
    // file_managed table.
    $fm_query = $this->connection->select('file_managed', 'fm')->fields('fm', ['fid'])->execute()->fetchCol();
    $query = $this->connection->select('file_usage', 'fu')
      ->fields('fu', ['fid', 'module', 'type', 'id', 'count']);
    if (!empty($fm_query)) {
      $query->condition('fu.fid', $fm_query, 'NOT IN');
    }
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    if ($maximumRecords !== 0) {
      $query->range(0, $maximumRecords);
    }

    $result = $query->execute();
    while ($row = $result->fetch()) {
      yield FileUsageReference::createFromRow($row);
    }
  }

}
