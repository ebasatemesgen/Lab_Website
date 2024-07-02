<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Services;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Service for getting exclusions.
 *
 * @internal
 *   There is no extensibility promise for this class.
 */
final class AuditFilesExclusions {

  /**
   * Constructs a new AuditFilesNotInDatabase.
   */
  final public function __construct(
    protected readonly AuditFilesConfigInterface $auditFilesConfig,
    protected readonly StreamWrapperManagerInterface $streamWrapperManager,
    protected readonly FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * Creates an exclusion string.
   *
   * This function creates a list of file and/or directory exclusions to be used
   * with a preg_* function.
   *
   * @return string
   *   The exclusions.
   */
  public function getExclusions(): string {
    $exclusions_array = [];
    $exclude_files = $this->auditFilesConfig->getExcludeFiles();
    if (count($exclude_files) > 0) {
      foreach ($exclude_files as $key => $value) {
        $exclude_files[$key] = $this->auditFilesEscapePreg($value, FALSE);
      }
      $exclusions_array = array_merge($exclusions_array, $exclude_files);
    }

    $exclude_paths = $this->auditFilesConfig->getExcludePaths();
    if (count($exclude_paths) > 0) {
      foreach ($exclude_paths as $key => $value) {
        $exclude_paths[$key] = $this->auditFilesEscapePreg($value, TRUE);
      }
      $exclusions_array = array_merge($exclusions_array, $exclude_paths);
    }

    // Exclude other file streams that may be defined and in use.
    $exclude_streams = [];
    $auditfiles_file_system_path = $this->auditFilesConfig->getFileSystemPath();
    $file_system_paths = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
    foreach ($file_system_paths as $file_system_path_id => $file_system_path) {
      if (in_array($file_system_path_id, [$auditfiles_file_system_path, 'assets', TRUE])) {
        continue;
      }
      $wrapper = $this->streamWrapperManager->getViaUri($file_system_path_id . '://');
      if ($wrapper && $wrapper->realpath()) {
        $exclude_streams[] = $wrapper->realpath();
      }
    }
    foreach ($exclude_streams as $key => $value) {
      $exclude_streams[$key] = $this->auditFilesEscapePreg($value, FALSE);
    }
    $exclusions_array = array_merge($exclusions_array, $exclude_streams);

    // Create the list of requested extension exclusions. (This is a little more
    // complicated).
    $exclude_extensions = $this->auditFilesConfig->getExcludeExtensions();
    if (count($exclude_extensions) > 0) {
      foreach ($exclude_extensions as $key => $value) {
        $exclude_extensions[$key] = $this->auditFilesEscapePreg($value, FALSE);
      }
      $extensions = implode('|', $exclude_extensions);
      $extensions = '(' . $extensions . ')$';
      $exclusions_array[] = $extensions;
    }

    // Implode exclusions array to a string.
    $exclusions = implode('|', $exclusions_array);
    // Return prepared exclusion string.
    return $exclusions;
  }

  /**
   * Escapes any possible regular expression characters in the specified string.
   *
   * @param string $element
   *   The string to escape.
   * @param bool $makefilepath
   *   Set to TRUE to change elements to file paths at the same time.
   */
  private function auditFilesEscapePreg(string $element, bool $makefilepath = FALSE): string {
    if ($makefilepath) {
      $file_system_stream = $this->auditFilesConfig->getFileSystemPath();
      if ($this->fileSystem->realpath("$file_system_stream://$element")) {
        return preg_quote($this->fileSystem->realpath("$file_system_stream://$element"));
      }
    }
    return preg_quote($element);
  }

}
