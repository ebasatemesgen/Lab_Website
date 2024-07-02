<?php

namespace Drupal\dropzonejs;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles chunked files uploaded by Dropzone.
 *
 * The uploaded file will be stored in the configured tmp folder and will be
 * added a chunk indentifier. Further filename processing will be
 * done in Drupal\dropzonejs\Element::valueCallback. This means that the final
 * filename will be provided only after that callback.
 */
class ChunkedUploadHandler extends UploadHandler {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs dropzone upload controller route controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   LanguageManager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory, TransliterationInterface $transliteration, LanguageManagerInterface $language_manager, FileSystemInterface $file_system) {
    parent::__construct($request_stack, $config_factory, $transliteration, $language_manager);
    $this->fileSystem = $file_system;
  }

  /**
   * Get the path of the chunks temporary directory.
   *
   * @param string $uuid
   *   The uuid of the Dropzone chunked file upload.
   *
   * @return string
   *   The chunks temporary directory path.
   */
  public function getChunksDirectory($uuid) {
    return $this->dropzoneSettings->get('tmp_upload_scheme') . '://' . $uuid;
  }

  /**
   * Get a chunk's filename.
   *
   * @param string $original_name
   *   The original name of the uploaded file.
   * @param int $chunk_index
   *   The chunk's index.
   *
   * @return string
   *   The chunk's filename.
   */
  public function getChunkFilename($original_name, $chunk_index) {
    $filename = $this->getFilename($original_name);
    return "{$filename}.part{$chunk_index}";
  }

  /**
   * {@inheritdoc}
   */
  public function handleUpload(UploadedFile $file) {
    if (!$this->request->request->has('dzuuid')) {
      // This is not a chunked upload.
      return parent::handleUpload($file);
    }

    $error = $file->getError();
    if ($error != UPLOAD_ERR_OK) {
      // Check for file upload errors and return FALSE for this file if a lower
      // level system error occurred. For a complete list of errors:
      // See http://php.net/manual/features.file-upload.errors.php.
      switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $message = $this->t('The file could not be saved because it exceeds the maximum allowed size for uploads.');
          break;

        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          $message = $this->t('The file could not be saved because the upload did not complete.');
          break;

        // Unknown error.
        default:
          $message = $this->t('The file could not be saved. An unknown error has occurred.');
          break;
      }

      throw new UploadException(UploadException::FILE_UPLOAD_ERROR, $message);
    }

    $uuid = $this->request->request->get('dzuuid');
    $chunk_index = $this->request->request->getInt('dzchunkindex');
    $chunks_directory = $this->getChunksDirectory($uuid);
    $destination = $chunks_directory . '/' . $this->getChunkFilename($file->getClientOriginalName(), $chunk_index);

    // Prepere directory.
    if (!$this->fileSystem->prepareDirectory($chunks_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    // Move uploaded chunk to the chunks directory.
    if (!$this->fileSystem->moveUploadedFile($file->getFileInfo()->getRealPath(), $destination)) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    return $destination;
  }

  /**
   * Handles chunks assembling.
   *
   * @return string
   *   URI of the assembled file.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  public function assembleChunks() {
    $filename = $this->request->request->get('filename');
    $uuid = $this->request->request->get('dzuuid');
    $total_size = $this->request->request->getInt('dztotalfilesize');
    $total_chunk_count = $this->request->request->getInt('dztotalchunkcount');

    $chunks_directory = $this->getChunksDirectory($uuid);

    // Open temp file.
    $tmp = $this->dropzoneSettings->get('tmp_upload_scheme') . '://' . $this->getFilename($filename);
    if (!($out = fopen($tmp, $this->request->request->get('chunk', 0) ? 'ab' : 'wb'))) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    // Assemble chunks.
    for ($i = 0; $i < $total_chunk_count; $i++) {
      // Read binary input stream from chunk.
      $chunk_uri = $chunks_directory . '/' . $this->getChunkFilename($filename, $i);
      if (!($in = fopen($chunk_uri, 'rb'))) {
        throw new UploadException(UploadException::INPUT_ERROR);
      }

      // Append input stream to temp file.
      while ($buff = fread($in, 4096)) {
        fwrite($out, $buff);
      }

      fclose($in);
    }

    fclose($out);

    // Delete the chunk files and directory.
    $this->fileSystem->deleteRecursive($chunks_directory);

    // Check that the assembled file has the correct filesize.
    if (filesize($tmp) !== $total_size) {
      throw new UploadException(UploadException::ASSEMBLE_CHUNKS_ERROR);
    }

    return $tmp;
  }

}
