<?php

namespace Drupal\dropzonejs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\dropzonejs\UploadException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles requests that dropzone issues when uploading files.
 */
class ChunkedUploadController extends UploadController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dropzonejs.chunked_upload_handler'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Finalize a chunked upload.
   */
  public function finalizeUpload() {
    if (
      !$this->request->request->has('filename') ||
      !$this->request->request->has('dzuuid') ||
      !$this->request->request->has('dztotalfilesize') ||
      !$this->request->request->has('dztotalchunkcount')
    ) {
      throw new AccessDeniedHttpException();
    }

    try {
      // Return JSON-RPC response.
      return new AjaxResponse([
        'jsonrpc' => '2.0',
        'result' => basename($this->uploadHandler->assembleChunks()),
        'id' => 'id',
      ]);
    }
    catch (UploadException $e) {
      return $e->getErrorResponse();
    }
  }

}
