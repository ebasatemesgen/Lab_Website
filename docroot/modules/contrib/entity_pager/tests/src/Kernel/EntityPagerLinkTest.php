<?php

namespace Drupal\Tests\entity_pager\Kernel;

use Drupal\entity_pager\EntityPagerLink;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @coversDefaultClass \Drupal\entity_pager\EntityPagerLink
 * @group entity_pager
 */
class EntityPagerLinkTest extends EntityKernelTestBase {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * @covers ::__construct
   * @covers ::getLink
   */
  public function testGetLink() {
    $text = $this->randomMachineName();
    $build = (new EntityPagerLink("<b>$text</b>"))->getLink();
    $this->setRawContent($this->renderer->renderPlain($build));

    $elements = $this->xpath('//span[@class="inactive"]/b[text()=:text]', [':text' => $text]);
    $this->assertCount(1, $elements, 'Rendered link result with no entity.');

    $text = $this->randomMachineName();
    $entity = EntityTest::create();
    $entity->save();

    $build = (new EntityPagerLink("<i>$text</i>", $entity))->getLink();
    $this->setRawContent($this->renderer->renderPlain($build));

    $elements = $this->cssSelect('a');
    $this->assertCount(1, $elements, 'Link rendered.');

    /** @var \SimpleXMLElement $link */
    $link = reset($elements);
    $href = (string) $link->attributes()->href;
    $this->assertEquals($entity->toUrl()->toString(), $href, 'Link goes to given entity.');
    $this->assertCount(1, $link->xpath("./i[text()='$text']"), 'Rendered link content.');
  }

}
