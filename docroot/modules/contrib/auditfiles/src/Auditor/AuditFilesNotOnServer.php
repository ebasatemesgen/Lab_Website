<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;

/**
 * Managed files but file on disk is missing.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 * @template R of \Drupal\auditfiles\Reference\FileEntityReference
 */
final class AuditFilesNotOnServer implements AuditFilesAuditorInterface {

  /**
   * Constructs a new AuditFilesNotOnServer.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly Connection $connection,
    protected readonly FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(): \Generator {
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $query = $this->connection->select('file_managed', 'fm');
    $query
      ->orderBy('changed', 'DESC')
      ->range(0, $maximumRecords)
      ->fields('fm', ['fid', 'uri']);
    /** @var array<object{uri: string, fid: string}> $results */
    $results = $query->execute()->fetchAll();
    foreach ($results as $result) {
      $target = $this->fileSystem->realpath($result->uri);
      if ($target !== FALSE && !file_exists($target)) {
        yield FileEntityReference::create((int) $result->fid);
      }
    }
  }

}
