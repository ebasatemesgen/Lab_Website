<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Services;

/**
 * Audit Files config.
 */
interface AuditFilesConfigInterface {

  /**
   * Get file system path.
   */
  public function getFileSystemPath(): string;

  /**
   * Get exclude files.
   *
   * @return string[]
   *   Excluded files.
   */
  public function getExcludeFiles(): array;

  /**
   * Get exclude extensions.
   *
   * @return string[]
   *   Excluded extensions.
   */
  public function getExcludeExtensions(): array;

  /**
   * Get exclude paths.
   *
   * @return string[]
   *   Excluded paths.
   */
  public function getExcludePaths(): array;

  /**
   * Get include domains.
   */
  public function getIncludeDomains(): string;

  /**
   * Get report options date format.
   */
  public function getReportOptionsDateFormat(): string;

  /**
   * Get report options items per page.
   */
  public function getReportOptionsItemsPerPage(): int;

  /**
   * Get report options maximum records.
   *
   * @return int<0,max>
   *   Maximum records, or zero for no-limit.
   */
  public function getReportOptionsMaximumRecords(): int;

  /**
   * Get report options maximum records.
   */
  public function isMergeFileReferencesShowSingleFileNames(): bool;

}
