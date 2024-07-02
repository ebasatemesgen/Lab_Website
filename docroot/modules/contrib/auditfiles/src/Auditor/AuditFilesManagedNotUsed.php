<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Service managed not used functions.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 *  @template R of \Drupal\auditfiles\Reference\FileEntityReference
 */
final class AuditFilesManagedNotUsed implements AuditFilesAuditorInterface {

  use MessengerTrait;

  /**
   * Constructs a new AuditFilesManagedNotUsed.
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
    $scheme = $this->auditFilesConfig->getFileSystemPath();
    $fu_query = $this->connection->select('file_usage', 'fu')->fields('fu', ['fid'])->execute()->fetchCol();
    $query = $this->connection->select('file_managed', 'fm')
      ->fields('fm', ['fid'])
      ->condition('fm.uri', $scheme . '://%', 'LIKE');

    if (count($fu_query) > 0) {
      $query->condition('fm.fid', $fu_query, 'NOT IN');
    }

    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    if ($maximumRecords !== 0) {
      $query->range(0, $maximumRecords);
    }

    foreach ($query->execute()->fetchCol() as $fid) {
      yield FileEntityReference::create((int) $fid);
    }
  }

}
