<?php

namespace Drupal\Tests\lightning_media_slideshow\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * @covers lightning_media_slideshow_update_9001
 *
 * @group lightning_media_slideshow
 * @group lightning_media
 */
class Update9001Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'blazy',
    'lightning_media_slideshow',
    'media',
    'slick',
  ];

  /**
   * Tests that the update hook downloads the Slick library.
   */
  public function testSlickLibraryDownloaded(): void {
    // Ensure that the system doesn't think the library is installed.
    $this->container->set('library.libraries_directory_file_finder', new class () {

      public function find() {
        return FALSE;
      }

    });

    // Mock the HTTP client so it returns our fake zip file when downloading
    // Dropzone.
    $tar = fopen(__DIR__ . '/../../fixtures/slick.tar.gz', 'rb');
    $this->assertIsResource($tar);

    $handler = new MockHandler([
      new Response(200, ['Content-Type' => 'application/tar+gzip'], $tar),
    ]);
    $client = new Client([
      'handler' => HandlerStack::create($handler),
    ]);
    $this->container->set('http_client', $client);

    $sandbox = [
      'lightning_media_slideshow_update_9001' => 'public://libraries',
    ];
    $this->container->get('module_handler')
      ->loadInclude('lightning_media_slideshow', 'install');
    lightning_media_slideshow_update_9001($sandbox);

    $this->assertFileExists('public://libraries/slick-carousel/README.txt');

    // If the library file finder detects Dropzone, confirm that it is not
    // installed again.
    $this->container->set('library.libraries_directory_file_finder', new class () {

      public function find() {
        return 'public://libraries/slick-carousel';
      }

    });
    $client = $this->prophesize(ClientInterface::class);
    $client->request(Argument::cetera())->shouldNotBeCalled();
    $this->container->set('http_client', $client->reveal());

    lightning_media_slideshow_update_9001($sandbox);
  }

}
