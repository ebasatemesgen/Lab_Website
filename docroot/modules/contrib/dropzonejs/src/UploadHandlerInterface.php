<?php

namespace Drupal\dropzonejs;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface UploadHandlerInterface.
 */
interface UploadHandlerInterface {

  /**
   * Sanitizes the filename of the uploaded file.
   *
   * @param string $original_name
   *   The original filename.
   *
   * @return string
   *   The sanitized filename.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  public function getFilename($original_name);

  /**
   * Handles an uploaded file.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The uploaded file.
   *
   * @return string
   *   URI of the uploaded file.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  public function handleUpload(UploadedFile $file);

}
