<?php

namespace Drupal\Tests\lightning_media_bulk_upload\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * @covers lightning_media_bulk_upload_update_9001
 *
 * @group lightning_media_bulk_upload
 * @group lightning_media
 */
class Update9001Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_media_bulk_upload'];

  /**
   * Tests that the update hook downloads the Dropzone library.
   */
  public function testDropzoneLibraryDownloaded(): void {
    // Ensure that the system doesn't think the library is installed.
    $this->container->set('library.libraries_directory_file_finder', new class () {

      public function find() {
        return FALSE;
      }

    });

    // Mock the HTTP client so it returns our fake zip file when downloading
    // Dropzone.
    $tar = fopen(__DIR__ . '/../../fixtures/dropzone.tar.gz', 'rb');
    $this->assertIsResource($tar);

    $handler = new MockHandler([
      new Response(200, ['Content-Type' => 'application/tar+gzip'], $tar),
    ]);
    $client = new Client([
      'handler' => HandlerStack::create($handler),
    ]);
    $this->container->set('http_client', $client);

    $sandbox = [
      'lightning_media_bulk_upload_update_9001' => 'public://libraries',
    ];
    $this->container->get('module_handler')
      ->loadInclude('lightning_media_bulk_upload', 'install');
    lightning_media_bulk_upload_update_9001($sandbox);

    $this->assertFileExists('public://libraries/dropzone/dist/README.txt');

    // If the library file finder detects Dropzone, confirm that it is not
    // installed again.
    $this->container->set('library.libraries_directory_file_finder', new class () {

      public function find() {
        return 'public://libraries/dropzone';
      }

    });
    $client = $this->prophesize(ClientInterface::class);
    $client->request(Argument::cetera())->shouldNotBeCalled();
    $this->container->set('http_client', $client->reveal());

    lightning_media_bulk_upload_update_9001($sandbox);
  }

}
