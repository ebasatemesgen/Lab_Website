<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Auditor;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Reference\DiskReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\auditfiles\Services\AuditFilesExclusions;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;

/**
 * Define all methods used on Files not in database functionality.
 *
 * @internal
 *   There is no extensibility promise for this class.
 *
 *  @template R of \Drupal\auditfiles\Reference\DiskReference
 */
final class AuditFilesNotInDatabase implements AuditFilesAuditorInterface {

  /**
   * Constructs a new AuditFilesNotInDatabase.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly AuditFilesExclusions $exclusions,
    protected readonly Connection $connection,
    protected readonly FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(): \Generator {
    $exclusions = $this->exclusions->getExclusions();
    /** @var array<array{file_name: string, path_from_files_root: string}> $report_files */
    $report_files = [];
    $this->getFilesForReport('', $report_files, $exclusions);

    foreach ($report_files as $report_file) {
      // Check to see if the file is in the database.
      $file_to_check = empty($report_file['path_from_files_root'])
        ? $report_file['file_name']
        : $report_file['path_from_files_root'] . DIRECTORY_SEPARATOR . $report_file['file_name'];

      // If the file is not in the database, add to the list for displaying.
      if (!$this->isFileInDatabase($file_to_check)) {
        yield DiskReference::create($this->auditfilesBuildUri($file_to_check));
      }
    }
  }

  /**
   * Get files for report.
   */
  private function getFilesForReport(string $path, array &$report_files, string $exclusions): void {
    $file_system_stream = $this->auditFilesConfig->getFileSystemPath();
    $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    if ($maximumRecords !== 0 && count($report_files) < $maximumRecords) {
      $new_files = $this->auditfilesNotInDatabaseGetFiles($path, $exclusions);
      if (!empty($new_files)) {
        foreach ($new_files as $file) {
          // Check if the current item is a directory or a file.
          $item_path_check = empty($file['path_from_files_root'])
            ? $real_files_path . DIRECTORY_SEPARATOR . $file['file_name']
            : ($real_files_path . DIRECTORY_SEPARATOR . $file['path_from_files_root'] . DIRECTORY_SEPARATOR . $file['file_name']);

          if (is_dir($item_path_check)) {
            // The item is a directory, so go into it and get any files there.
            $file_path = (empty($path))
              ? $file['file_name']
              : $path . DIRECTORY_SEPARATOR . $file['file_name'];
            $this->getFilesForReport($file_path, $report_files, $exclusions);
          }
          else {
            // The item is a file, so add it to the list.
            $file['path_from_files_root'] = $this->auditfilesNotInDatabaseFixPathSeparators($file['path_from_files_root']);
            $report_files[] = $file;
          }
        }
      }
    }
  }

  /**
   * Checks if the specified file is in the database.
   *
   * @param string $filePathName
   *   The path and filename, from the "files" directory, of the file to check.
   *
   * @return bool
   *   Returns TRUE if the file was found in the database, or FALSE, if not.
   */
  private function isFileInDatabase(string $filePathName): bool {
    $uri = $this->auditfilesBuildUri($filePathName);
    $fid = $this->connection->select('file_managed', 'fm')
      ->condition('fm.uri', $uri)
      ->fields('fm', ['fid'])
      ->execute()
      ->fetchField();
    return $fid !== FALSE;
  }

  /**
   * Retrieves a list of files in the given path.
   *
   * @param string $path
   *   The path to search for files in.
   * @param string $exclusions
   *   The imploded list of exclusions from configuration.
   *
   * @return array
   *   The list of files and diretories found in the given path.
   */
  private function auditfilesNotInDatabaseGetFiles(string $path, string $exclusions): array {
    $file_system_stream = $this->auditFilesConfig->getFileSystemPath();
    $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');
    // The variable to store the data being returned.
    $file_list = [];
    $scan_path = empty($path) ? $real_files_path : $real_files_path . DIRECTORY_SEPARATOR . $path;
    // Get the files in the specified directory.
    $files = array_diff(scandir($scan_path), ['..', '.']);
    foreach ($files as $file) {
      // Check to see if this file should be included.
      if ($this->auditfilesNotInDatabaseIncludeFile($real_files_path . DIRECTORY_SEPARATOR . $path, $file, $exclusions)) {
        // The file is to be included, so add it to the data array.
        $file_list[] = [
          'file_name' => $file,
          'path_from_files_root' => $path,
        ];
      }
    }
    return $file_list;
  }

  /**
   * Corrects the separators of a file system's file path.
   *
   * Changes the separators of a file path, so they are match the ones
   * being used on the operating system the site is running on.
   *
   * @param string $path
   *   The path to correct.
   *
   * @return string
   *   The corrected path.
   */
  private function auditfilesNotInDatabaseFixPathSeparators($path): string {
    $path = preg_replace('@\/\/@', DIRECTORY_SEPARATOR, $path);
    $path = preg_replace('@\\\\@', DIRECTORY_SEPARATOR, $path);
    return $path;
  }

  /**
   * Checks to see if the file is being included.
   *
   * @param string $path
   *   The complete file system path to the file.
   * @param string $file
   *   The name of the file being checked.
   * @param string $exclusions
   *   The list of files and directories not to be included in the
   *   list of files to check.
   *
   * @return bool
   *   Returns TRUE, if the path or file is being included, or FALSE,
   *   if the path or file has been excluded.
   *
   * @todo Possibly add other file streams on the system but not the one
   *   being checked to the exclusions check.
   */
  private function auditfilesNotInDatabaseIncludeFile($path, $file, string $exclusions): bool {
    if (empty($exclusions)) {
      return TRUE;
    }
    elseif (!preg_match('@' . $exclusions . '@', $file) && !preg_match('@' . $exclusions . '@', rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file)) {
      return TRUE;
    }
    // This path and/or file are being excluded.
    return FALSE;
  }

  /**
   * Returns the internal path to the given file.
   */
  private function auditfilesBuildUri(string $filePathname): string {
    return sprintf('%s://%s', $this->auditFilesConfig->getFileSystemPath(), $filePathname);
  }

}
