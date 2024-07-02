<?php

namespace Drupal\Tests\field_redirection\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\field_redirection\FieldRedirectionResult;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a class for testing FieldRedirectionResult.
 *
 * @coversDefaultClass \Drupal\field_redirection\FieldRedirectionResult
 *
 * @group field_redirection
 */
class FieldRedirectionResultUnitTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * @covers ::fromUrl
   */
  public function testFromUrl() {
    $unroutedUrlAssembler = $this->prophesize(UnroutedUrlAssemblerInterface::class);
    $url = 'http://example.com';
    $unroutedUrlAssembler->assemble(Argument::cetera())->willReturn($url);
    $redirect = FieldRedirectionResult::fromUrl(Url::fromUri($url)->setUnroutedUrlAssembler($unroutedUrlAssembler->reveal()));
    $this->assertInstanceOf(FieldRedirectionResult::class, $redirect);
    $this->assertTrue($redirect->shouldRedirect());
    $expected = new RedirectResponse($url);
    $this->assertEquals($expected, $redirect->asRedirectResponse());
    $expected = new RedirectResponse($url, 301);
    $this->assertEquals($expected, $redirect->asRedirectResponse(301));
  }

  /**
   * @covers ::deny
   */
  public function testDeny() {
    $redirect = FieldRedirectionResult::deny();
    $this->assertFalse($redirect->shouldRedirect());
    $this->expectException(\LogicException::class);
    $redirect->asRedirectResponse();
  }

}
