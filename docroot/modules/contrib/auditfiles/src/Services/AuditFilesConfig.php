<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Audit Files config.
 *
 * @internal
 *   There is no extensibility promise for this class. Use service decorators to
 *   customise.
 */
final class AuditFilesConfig implements AuditFilesConfigInterface {

  /**
   * Constructs a new AuditFilesConfig.
   */
  final public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFileSystemPath(): string {
    return $this->configFactory->get('auditfiles.settings')->get('auditfiles_file_system_path');
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludeFiles(): array {
    $excludeFiles = ($this->configFactory->get('auditfiles.settings')->get('auditfiles_exclude_files') ?? '');
    return !empty($excludeFiles) ? explode(';', $excludeFiles) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludeExtensions(): array {
    $excludeExtensions = ($this->configFactory->get('auditfiles.settings')->get('auditfiles_exclude_extensions') ?? '');
    return !empty($excludeExtensions) ? explode(';', $excludeExtensions) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludePaths(): array {
    $excludePaths = ($this->configFactory->get('auditfiles.settings')->get('auditfiles_exclude_paths') ?? '');
    return !empty($excludePaths) ? explode(';', $excludePaths) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludeDomains(): string {
    return $this->configFactory->get('auditfiles.settings')->get('auditfiles_include_domains');
  }

  /**
   * {@inheritdoc}
   */
  public function getReportOptionsDateFormat(): string {
    return $this->configFactory->get('auditfiles.settings')->get('auditfiles_report_options_date_format');
  }

  /**
   * {@inheritdoc}
   */
  public function getReportOptionsItemsPerPage(): int {
    return (int) $this->configFactory->get('auditfiles.settings')->get('auditfiles_report_options_items_per_page');
  }

  /**
   * {@inheritdoc}
   */
  public function getReportOptionsMaximumRecords(): int {
    return (int) $this->configFactory->get('auditfiles.settings')->get('auditfiles_report_options_maximum_records');
  }

  /**
   * {@inheritdoc}
   */
  public function isMergeFileReferencesShowSingleFileNames(): bool {
    return (bool) $this->configFactory->get('auditfiles.settings')->get('auditfiles_merge_file_references_show_single_file_names');
  }

}
