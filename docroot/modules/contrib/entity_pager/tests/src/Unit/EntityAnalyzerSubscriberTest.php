<?php

namespace Drupal\Tests\entity_pager\Unit;

use Drupal\entity_pager\EntityPagerInterface;
use Drupal\entity_pager\Event\EntityPagerAnalyzeEvent;
use Drupal\entity_pager\EventSubscriber\EntityAnalyzerSubscriber;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_pager\EventSubscriber\EntityAnalyzerSubscriber
 * @group entity_pager
 */
class EntityAnalyzerSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onEntityPagerAnalyze
   * @dataProvider onEntityPagerAnalyzeTestCases
   */
  public function testEntityPagerAnalyze($entity, $logs) {
    $entity_pager = $this->createMock(EntityPagerInterface::class);
    $entity_pager->method('getEntity')->willReturn($entity);

    $event = new EntityPagerAnalyzeEvent($entity_pager);
    (new EntityAnalyzerSubscriber())->onEntityPagerAnalyze($event);
    $this->assertEquals($logs, $event->getLogs());
  }

  /**
   * Test cases for testOnEntityPagerAnalyze().
   */
  public function onEntityPagerAnalyzeTestCases() {
    return [
      'No entity' => [NULL, ['No Entity on page.']],
      'Has entity' => [$this->createMock(EntityTest::class), []],
    ];
  }

}
